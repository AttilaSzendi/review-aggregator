#!/usr/bin/env bash
#
# One-shot deploy of the Review Aggregator demo to the droplet, served at
# http://<ip>:8080 by an isolated systemd service. Idempotent: re-run it to
# update (it pulls the latest main, reinstalls deps, migrates, restarts).
#
# Run from your machine (it executes on the droplet):
#   ssh -i ~/.ssh/swimetric_ed25519 root@209.38.235.53 'bash -s' < deploy/first-deploy.sh
#
set -euo pipefail
export DEBIAN_FRONTEND=noninteractive

APP_DIR=/var/www/review-aggregator
REPO=https://github.com/AttilaSzendi/review-aggregator

echo "==> ensuring unzip + composer"
command -v unzip >/dev/null || apt-get install -y -qq unzip
if ! command -v composer >/dev/null; then
  php -r "copy('https://getcomposer.org/installer', '/tmp/composer-setup.php');"
  php /tmp/composer-setup.php --quiet --install-dir=/usr/local/bin --filename=composer
  rm -f /tmp/composer-setup.php
fi

echo "==> fetching code"
# The checkout is owned by www-data (see chown below) but git here runs as root,
# so mark it a safe directory to avoid git's "dubious ownership" refusal.
git config --global --add safe.directory "$APP_DIR"
if [ -d "$APP_DIR/.git" ]; then
  git -C "$APP_DIR" fetch --depth 1 origin main
  git -C "$APP_DIR" reset --hard origin/main
else
  rm -rf "$APP_DIR"
  git clone --depth 1 "$REPO" "$APP_DIR"
fi
cd "$APP_DIR"

echo "==> installing production dependencies"
# --no-scripts: the composer auto-scripts run cache:clear in the dev env, which
# would try to load dev-only bundles (MakerBundle) that --no-dev did not install.
# We clear the cache explicitly below with APP_ENV=prod instead.
COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader --no-interaction --no-progress --no-scripts

echo "==> app secret"
[ -f .env.local ] || printf 'APP_SECRET=%s\n' "$(openssl rand -hex 16)" > .env.local

echo "==> database + cache (prod)"
mkdir -p var
export APP_ENV=prod APP_DEBUG=0
php bin/console cache:clear
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console app:import-reviews

echo "==> permissions"
chown -R www-data:www-data "$APP_DIR"

echo "==> systemd service"
cp deploy/review-aggregator.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable review-aggregator >/dev/null 2>&1 || true
systemctl restart review-aggregator

echo "==> firewall (open 8080)"
ufw allow 8080/tcp || true

echo "==> verify"
sleep 2
systemctl is-active review-aggregator
for i in $(seq 1 10); do
  code=$(curl -s -o /dev/null -w '%{http_code}' --max-time 5 http://127.0.0.1:8080/api/reviews || true)
  [ "$code" = "200" ] && { echo "smoke: HTTP 200 — live on :8080"; exit 0; }
  sleep 1
done
echo "service is up but smoke test did not return 200 (last: ${code:-none})"
exit 1
