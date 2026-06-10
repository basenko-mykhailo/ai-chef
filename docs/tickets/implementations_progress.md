# AI Chef — Implementation Progress

> Snapshot date: 2026-05-13 (updated). Source plan: `docs/tickets/plan.md`.
> Legend: ✅ done · 🟡 partial · ⬜ not started

## Summary

| Epic | Status | Notes |
|---|---|---|
| 0. Foundation | ✅ done | All 6 tickets implemented |
| 1. Pantry (manual input) | ⬜ not started | Route is a placeholder view; no models/migrations |
| 2. Family members | 🟡 partial | 2.1 migration + 2.2 model + 2.3 list page + 2.4 add/edit form done; 2.5 pending |
| 3. AI Core (recipe generation) | ⬜ not started | No services, jobs, models, or migrations |
| 4. "Cooked" → pantry deduction | ⬜ not started | Depends on Epic 1 + 3 |
| 5. History & favorites | ⬜ not started | Route is a placeholder view |
| 6. Photo pantry recognition | ⬜ not started | No upload UI or service |
| 7. Polish & deploy | 🟡 partial | Disclaimer in layout (overlaps with 0.6); rest pending |

---

## Epic 0 — Foundation ✅

- ✅ **0.1 Laravel project** — `src/` is a Laravel 13 skeleton (`composer.json`: `laravel/framework ^13.7`). Git/.gitignore/README present.
- ✅ **0.2 Docker compose** — `docker-compose.yml` defines `web` (nginx), `php` (php-fpm 8.4-alpine, built from `docker/php`), `db` (postgres). DB is reachable from container as host `db` / from host on `localhost:5432`.
- ✅ **0.3 Laravel Breeze** — `app/Http/Controllers/Auth/*` and `routes/auth.php` exist; Breeze Blade views live in `resources/views/auth/`. Profile edit/update/destroy routes wired in `routes/web.php`.
- ✅ **0.4 Base layout & navigation** — `resources/views/layouts/app.blade.php` + `layouts/navigation.blade.php` with header links Комора / Сім'я / Рецепти / Історія, footer, Tailwind classes, Alpine-driven mobile menu.
- ✅ **0.5 Anthropic API config** — `config/services.php` has `anthropic` section (`api_key`, `default_model=claude-haiku-4-5`, `quality_model=claude-sonnet-4-6`); `.env` and `.env.example` both expose `ANTHROPIC_API_KEY`, `ANTHROPIC_DEFAULT_MODEL`, `ANTHROPIC_QUALITY_MODEL` (key value itself is empty — needs filling).
- ✅ **0.6 Global AI disclaimer** — Visible amber banner in `layouts/app.blade.php` ("AI може помилитися — самостійно перевіряйте склад страв на алергени…").

**Caveats / gaps surfaced during 0.x review:**
- `APP_LOCALE=en` in `src/.env` — per `CLAUDE.md` this should switch to `uk` once localization begins (Epic 1+).
- The four feature routes (`/pantry`, `/family`, `/recipes`, `/history`) are `Route::view(..., 'placeholder')` stubs in `routes/web.php` — placeholders only, no controllers/models.

---

## Epic 1 — Pantry (manual input) ⬜

Nothing implemented. Evidence:
- `database/migrations/` contains only the three Laravel default migrations (`0001_01_01_*` users / cache / jobs). No `ingredients` or `pantry_items` migrations.
- `database/seeders/` has only `DatabaseSeeder.php` (default). No `IngredientSeeder`.
- `app/Models/` contains only `User.php`. No `Ingredient` or `PantryItem` models.
- `/pantry` route renders the generic placeholder view.

Outstanding: 1.1 – 1.9 (all).

---

## Epic 2 — Family members 🟡

- ✅ **2.1 `family_members` migration** — `database/migrations/2026_05_13_165321_create_family_members_table.php` creates the table with `id`, `user_id` (FK → `users`, cascadeOnDelete), `name` (string), `favorite_products` / `disliked_products` / `allergies_and_diets` (text, nullable), and timestamps. Migration applied to dev DB (`php artisan migrate` → DONE).
- ✅ **2.2 `FamilyMember` Eloquent model** — `src/app/Models/FamilyMember.php` adds the model with `#[Fillable]`, `belongsTo(User)` via `user()`, and `scopeForUser(int $userId)`. `src/app/Models/User.php` adds `familyMembers(): HasMany`. Factory at `src/database/factories/FamilyMemberFactory.php` (Ukrainian sample arrays + `User::factory()` FK). `src/database/seeders/FamilyMemberSeeder.php` creates 5 deterministic members (Тато, Мама, Бабуся, Син, Донька) for `test@example.com`, wired into `DatabaseSeeder`. `composer test --filter=FamilyMemberTest` → 3/3 pass; full suite 28/28.
- ✅ **2.3 List page** — `src/app/Http/Controllers/FamilyMemberController.php` adds `index()` that loads `$request->user()->familyMembers()->orderBy('id')->get()`; `src/routes/web.php` swaps `Route::view('/family', …)` for `Route::get('/family', [FamilyMemberController::class, 'index'])->name('family.index')`. `src/resources/views/family/index.blade.php` renders a 1/2/3-col card grid with name + color-coded chips (`favorite_products` green / `disliked_products` amber / `allergies_and_diets` red), plus an empty-state block with CTA (CTA href is a `#` placeholder until 2.4 lands the create form). Feature test at `src/tests/Feature/FamilyMemberIndexTest.php` covers guest→login redirect, own-vs-other isolation, and empty state. `composer test --filter=FamilyMember` → 31/31 pass. `php artisan route:list --name=family` → `family.index → FamilyMemberController@index`. `php artisan migrate:fresh --seed` → DONE; logging in as `test@example.com` / `password` shows the 5 seeded cards.
- ✅ **2.4 Add/edit form** — `FamilyMemberController` gains `create/store/edit/update`. Routes `family.create` (GET `/family/create`), `family.store` (POST `/family`), `family.edit` (GET `/family/{familyMember}/edit`), `family.update` (PATCH `/family/{familyMember}`) added under `auth`. Validation in `app/Http/Requests/FamilyMemberRequest.php` (name required ≤255; three text fields nullable ≤1000; Ukrainian messages/attributes). Shared Blade form `resources/views/family/partials/form.blade.php` (name input + three placeholder-hinted textareas) used by new `family/create.blade.php` and `family/edit.blade.php`; new reusable `components/textarea.blade.php`. `family/index.blade.php` now has a "Додати члена сім'ї" CTA (header + empty state), per-row "Редагувати" links, and a success flash. Ownership enforced: create binds `user_id` via the relationship (no spoofing); edit/update return 403 for другого юзера (`FamilyMemberRequest::authorize()` runs before validation; `edit()` guards with `abort_unless`). Tests: `tests/Feature/FamilyMemberFormTest.php` (9 cases — view/create/validation/spoof-guard/edit/update/403s). This also satisfies the pre-existing `FamilyMemberIndexTest` empty-state assertions (`Ще немає членів сім'ї` + `Додати члена сім'ї`) that the old placeholder copy did not.
- ⬜ **2.5 Delete flow** — no delete action / confirmation modal.

Outstanding: 2.5.

---

## Epic 3 — AI Core ⬜

Nothing implemented. No `recipes` / `recipe_cache` migrations, no `app/Services/` directory (so no `ClaudeService`, `RecipePromptBuilder`), no `app/Jobs/` directory (so no `RecipeGenerationJob`). `/recipes` is a placeholder route. Queue connection is set to `database` and the jobs table migration exists, so infra is ready when the job is added.

Outstanding: 3.1 – 3.12 (all).

---

## Epic 4 — "Cooked" → pantry deduction ⬜

Nothing implemented. Blocked on Epic 1 (pantry tables) and Epic 3 (recipes table).

Outstanding: 4.1 – 4.4 (all).

---

## Epic 5 — History & favorites ⬜

Nothing implemented. `/history` is a placeholder route; no recipe table yet to back the history view.

Outstanding: 5.1 – 5.4 (all).

---

## Epic 6 — Photo pantry recognition ⬜

Nothing implemented. No upload form, no `PhotoRecognitionService`, no review/confirm page.

Outstanding: 6.1 – 6.6 (all).

---

## Epic 7 — Polish & deploy 🟡

- 🟡 **Disclaimer** is already live (delivered as part of ticket 0.6) — counts toward Epic 7 polish.
- ⬜ 7.1 Mobile responsiveness pass — not done (and most pages don't exist yet).
- ⬜ 7.2 Empty states — N/A so far (no list pages built).
- ⬜ 7.3 Custom 404/500 — Laravel defaults still in use.
- ⬜ 7.4 Production nginx config — only the dev `docker/nginx/default.conf` exists.
- ⬜ 7.5 Deploy to VPS/PaaS — not done.
- ⬜ 7.6 Demo seeder — only the empty `DatabaseSeeder.php`.
- ⬜ 7.7 Final README — `src/README.md` is the default Laravel one.

---

## Recommended next step

Close Epic 2 with ticket 2.5 — delete a family member with a confirmation step. This needs a `FamilyMemberController@destroy` + `Route::delete('/family/{familyMember}')` (`family.destroy`), a delete button per row in `family/index.blade.php` (reuse the existing `x-modal` / `x-danger-button` components for confirmation), the same ownership guard as edit/update, and a feature test (owner can delete, 403 for others). After Epic 2 is closed, start Epic 1 (pantry) — it's the bigger unblocker for Epics 3/4/6. Still worth flipping `APP_LOCALE=en` → `uk` in `src/.env` before deeper UI work.