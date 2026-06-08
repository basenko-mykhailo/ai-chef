# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project context

"AI Chef" — hackathon Laravel app that generates recipes from a user's pantry + family-member dietary constraints via the Anthropic Claude API, with photo-based pantry recognition. Consolidated requirements with accepted decisions live in `docs/tickets/prd.md` (Ukrainian, source of truth); ticket-level roadmap and epic breakdown live in `docs/tickets/plan.md`. User-facing locale is Ukrainian; default Laravel locale in `.env` is currently `en` and should be switched to `uk` when localization work starts.

The repository root is **not** a Laravel project — the Laravel app lives in `src/`. The root holds Docker orchestration (`docker-compose.yml`, `docker/`) and product docs (`docs/`).

## Development environment

Everything runs through Docker Compose. There is no host-side PHP/Composer expected.

- `UID=$(id -u) GID=$(id -g) docker compose build` — build the PHP-FPM image with matching host UID/GID (avoids volume permission issues, since `src/` is bind-mounted writable into the `php` container).
- `docker compose up -d` — start `web` (nginx on http://localhost:8080), `php` (php-fpm 8.4-alpine), `db` (postgres, exposed on 5432).
- `docker compose exec php <cmd>` — run anything inside the app container (composer, artisan, npm).

Database connection inside containers: host `db`, db/user `ai_chive`, password `secret`. From the host, the same DB is reachable on `localhost:5432`.

## Common commands

Run inside the `php` container (`docker compose exec php …`):

- `composer install` — install PHP deps.
- `php artisan migrate` / `php artisan migrate:fresh --seed` — run migrations / reset DB with seeders.
- `php artisan tinker` — REPL.
- `php artisan queue:listen --tries=1 --timeout=0` — required for AI generation jobs (see Epic 3 in plan; queue driver is `database`).
- `composer test` — runs `config:clear` then `php artisan test` (PHPUnit, sqlite `:memory:` per `phpunit.xml`).
- `php artisan test --filter=SomeTest` or `vendor/bin/phpunit --filter=SomeTest` — single test.
- `composer dev` — concurrent `serve` + `queue:listen` + `pail` + `vite`. Note: this binds `php artisan serve` to the container's port, **not** the nginx-fronted 8080. For normal dev, use the existing nginx + php-fpm stack and run vite separately if needed.
- `npm install --ignore-scripts && npm run dev` (or `npm run build`) — Vite for Tailwind v4 assets.

Linting: `laravel/pint` is in `require-dev` — run with `vendor/bin/pint`.

## Architecture notes specific to this repo

**Two-tier directory layout.** Anything outside `src/` is infrastructure (compose, nginx config, Dockerfile) or product docs. Application code, migrations, tests, `.env`, `composer.json`, `package.json`, `vendor/` and `node_modules/` all live under `src/`. When pasting absolute paths or running artisan, always anchor on `src/` (or run inside the container where `/var/www/html` == `src/`).

**Bind mounts.** `nginx` mounts `src/` read-only; `php` mounts it read-write. Any file the app writes (storage/, bootstrap/cache/, log files) must be writable by the `app` user inside the container — this is why the build args `UID`/`GID` matter.

**Session/cache/queue all default to `database`** (`src/.env`). Migrations for the `cache`, `jobs`, and `sessions` tables ship in `src/database/migrations/0001_01_01_*`. Don't switch these to `redis`/`file` casually — there is no Redis service in compose.

**Planned domain model** (per `docs/tickets/plan.md`, not yet implemented): `users`, `family_members`, `ingredients` (seeded reference list ≈150 Ukrainian products + `custom` flag), `pantry_items`, `recipes` (with JSON columns for ingredients/steps/kbju and snapshots), `recipe_cache` (key = hash of pantry + selected family-member constraints). AI calls go through a planned `ClaudeService` wrapper and `RecipePromptBuilder`; long-running generation runs in a `RecipeGenerationJob`. The cache lookup happens **before** the API call to dedupe identical pantry+family combos.

**Fixed product rules** (from `prd.md`, easy to get wrong if you don't know them):
- Pantry units are a closed enum: `г, кг, мл, л, шт, ст.л., ч.л., склянка`. No expiry dates in MVP.
- Multiple selected family members combine via **AND** (recipe must satisfy every selected member's allergies/diets). Prompt must instruct the model to interpret allergies broadly (e.g. "горіхи" → exclude all tree nuts + peanut).
- Recipe ingredient units must match the pantry's units. The model is allowed to add 1–2 staples not in the pantry (salt/oil/spices) marked `in_pantry: false`.
- Photo pantry input: ≤3 images per request, ≤5 MB each, jpeg/png/webp only.
- Rate limit: 10 recipe generations per hour per user (Laravel throttle middleware).
- "Cooked" flow is an explicit confirmation screen with editable amounts → atomic decrement of `pantry_items` → row deleted at ≤0; recipe gets `status=cooked` and `cooked_at`.

**Anthropic API.** `ANTHROPIC_API_KEY` is expected in `.env` and surfaced via `config/services.php` (not yet wired). Plan calls for Haiku 4.5 as the default with Sonnet 4.6 as the quality fallback, and a single multimodal model for both recipe generation and photo pantry recognition.

**Disclaimer requirement.** A visible "AI may make mistakes — verify allergens yourself" notice must appear in the global layout — this is a non-negotiable product/safety requirement from the plan, not just polish.
