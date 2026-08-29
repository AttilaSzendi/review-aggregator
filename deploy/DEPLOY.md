# Deploying the demo to the droplet (IP + port 8080)

The app is served at **http://&lt;droplet-ip&gt;:8080** by an isolated systemd
service — no domain, no TLS, and no changes to the existing nginx vhosts, so the
other apps on the box are untouched. Continuous deploys run from GitHub Actions
(`.github/workflows/deploy.yml`).

Run the one-time server setup below **once**; after that every push to `main`
deploys automatically.

## 1. One-time server setup (on the droplet, as root)

```bash
# PHP 8.4 + the extensions Symfony/Doctrine need (Ubuntu 24.04 ships 8.3, so use the PPA)
add-apt-repository -y ppa:ondrej/php && apt update
apt install -y php8.4-cli php8.4-intl php8.4-sqlite3 php8.4-xml \
               php8.4-mbstring php8.4-curl php8.4-opcache

# App directory, owned by the service user
mkdir -p /var/www/review-aggregator/var
chown -R www-data:www-data /var/www/review-aggregator

# Production secret (Symfony reads .env.local on top of the committed .env)
printf 'APP_SECRET=%s\n' "$(openssl rand -hex 16)" > /var/www/review-aggregator/.env.local
chown www-data:www-data /var/www/review-aggregator/.env.local

# Firewall: open 8080. Use whichever applies to this droplet:
ufw allow 8080/tcp 2>/dev/null || true       # if ufw is active
# and/or add an inbound rule for TCP 8080 in the DigitalOcean cloud firewall.

# Install and enable the service (starts serving once code is deployed in step 3)
cp /root/review-aggregator.service /etc/systemd/system/   # or scp deploy/review-aggregator.service here
systemctl daemon-reload
systemctl enable review-aggregator
```

> The service will not fully start until the first deploy has placed the code.

## 2. GitHub repository secrets

Add these to the `review-aggregator` repo (Settings → Secrets and variables →
Actions). They are the same values the swimstat deploy already uses:

| Secret | Value |
|--------|-------|
| `DEPLOY_KEY` | the private SSH key that can `root@` the droplet |
| `KNOWN_HOSTS` | output of `ssh-keyscan <droplet-ip>` |
| `DEPLOY_HOST` | the droplet IP |

## 3. First deploy

Trigger the **Deploy to droplet** workflow (Actions tab → Run workflow), or push
to `main`. It builds prod dependencies, rsyncs the code, runs migrations, imports
the sample reviews, restarts the service, and smoke-tests `:8080`.

Then open **http://&lt;droplet-ip&gt;:8080**.

## Rolling it back / removing it

```bash
systemctl disable --now review-aggregator
rm /etc/systemd/system/review-aggregator.service
rm -rf /var/www/review-aggregator
ufw delete allow 8080/tcp 2>/dev/null || true
```
Nothing else on the box is affected.
