# Review Aggregator

[![CI](https://github.com/AttilaSzendi/review-aggregator/actions/workflows/ci.yml/badge.svg)](https://github.com/AttilaSzendi/review-aggregator/actions/workflows/ci.yml)

A small Symfony 6.4 app that aggregates customer reviews from multiple external
platforms (Google, Trustpilot, …), exposes them over a REST API, and renders a
Trustindex-style admin with aggregate rating stats — a compact but production-shaped
slice built to show clean, idiomatic Symfony.

## What it demonstrates

- **Symfony idioms**: attribute routing, autowiring & the DI container, tagged-iterator
  service collection, `#[MapRequestPayload]` + Validator, Twig + Form component,
  a console command, Doctrine ORM with migrations.
- **SOLID / clean code**: a provider abstraction (`ReviewProviderInterface`) that new
  platforms plug into without touching the importer (OCP + DIP); a pure, side-effect-free
  `ReviewStatsCalculator`; a single `ReviewCreator` + one `CreateReviewInput` command DTO
  shared by the API and the admin form, so the create logic and the validation rules each
  live in exactly one place; a view layer that keeps entities out of API responses.
- **Testing**: PHPUnit 9 — pure unit tests for the calculator and functional
  `WebTestCase` tests (API + admin) that drive the real HTTP stack against a SQLite test DB,
  with data built by a **zenstruck/foundry** factory (the Symfony analogue of Laravel model
  factories).
- **DevOps**: fully containerised — no PHP needed on the host.

## Architecture

```
Provider (Google, Trustpilot, …)  ──┐
  implements ReviewProviderInterface │  tagged iterator
                                     ▼
ReviewImporter ──► idempotent upsert ──► Review (Doctrine entity, SQLite)

Create path (one shared route to persistence):
   API  #[MapRequestPayload] ─┐
                              ├─► CreateReviewInput ─► ReviewCreator ─► Review
   Admin form (data_class) ───┘        (one validation + create source)

Read path:
   ReviewApiController      ReviewStatsCalculator      ReviewAdminController
   /api/reviews (GET)       (pure aggregation)         /admin/reviews (Twig)
   /api/reviews/stats
```

## Requirements

Just **Docker** — the app image bundles PHP 8.4, the required extensions, and Composer.

## Quick start

```bash
# 1. Build the image
docker build -t review-aggregator:dev .

# 2. Install dependencies + set up the database
docker compose run --rm app composer install
docker compose run --rm app php bin/console doctrine:migrations:migrate --no-interaction

# 3. Import sample reviews from the (mock) providers — idempotent, safe to re-run
docker compose run --rm app php bin/console app:import-reviews

# 4. Run the app
docker compose up
# → Admin UI:  http://localhost:8000/admin/reviews
# → API:       http://localhost:8000/api/reviews
```

## API

| Method | Path                  | Description                                   |
|--------|-----------------------|-----------------------------------------------|
| GET    | `/api/reviews`        | List reviews. Query: `platform`, `minRating`, `page`, `perPage` |
| GET    | `/api/reviews/stats`  | Aggregate stats (total, average, 1–5 distribution) for the same filters |
| POST   | `/api/reviews`        | Create a review (JSON body, validated → 201 or 422) |

```bash
curl "http://localhost:8000/api/reviews/stats?platform=google"
# {"total":5,"average":4.4,"distribution":{"1":0,"2":0,"3":1,"4":1,"5":3}}

# externalId is optional: importers supply the platform's id (kept unique for
# idempotent imports); omit it for manual/API entries and one is generated.
curl -X POST http://localhost:8000/api/reviews -H 'Content-Type: application/json' \
  -d '{"platform":"yelp","authorName":"Jane","rating":5,"content":"Great!"}'
```

## Tests

```bash
docker compose run --rm -e APP_ENV=test app php bin/phpunit
```

## Adding a new platform

1. Implement `App\Provider\ReviewProviderInterface`.
2. That's it — autoconfiguration tags it and `ReviewImporter` picks it up automatically.
   No existing code changes.
