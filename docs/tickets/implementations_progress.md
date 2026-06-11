# AI Chef — Implementation Progress

> Snapshot date: 2026-06-10 (updated). Source plan: `docs/tickets/plan.md`.
> Legend: ✅ done · 🟡 partial · ⬜ not started

## Summary

| Epic | Status | Notes |
|---|---|---|
| 0. Foundation | ✅ done | All 6 tickets implemented |
| 1. Pantry (manual input) | 🟡 partial | 1.1–1.4 done (ingredients + pantry_items migrations, ~170-item seeder, models + Unit enum); 1.5–1.9 UI pending |
| 2. Family members | 🟡 partial | 2.1 migration + 2.2 model + 2.3 list page + 2.4 add/edit form done; 2.5 pending |
| 3. AI Core (recipe generation) | 🟡 partial | 3.1–3.6 done (migrations, `ClaudeService`, prompts+schema, response parser); jobs/cache/UI pending |
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

## Epic 1 — Pantry (manual input) 🟡

Data layer done (1.1–1.4); UI/CRUD pending (1.5–1.9).

- ✅ **1.1 `ingredients` migration** — `database/migrations/2026_06_11_120000_create_ingredients_table.php`: `id`, `name` (indexed for autocomplete), `category` (nullable), `is_custom` (default false), `created_by_user_id` (nullable FK → users, `nullOnDelete`), timestamps.
- ✅ **1.2 Ingredient seeder** — `database/seeders/IngredientSeeder.php` seeds ~170 Ukrainian catalog products across 14 categories (all `is_custom=false`), wired into `DatabaseSeeder`; idempotent (`firstOrCreate` on name).
- ✅ **1.3 `pantry_items` migration** — `database/migrations/2026_06_11_120100_create_pantry_items_table.php`: `id`, `user_id` (FK cascade), `ingredient_id` (FK cascade), `quantity` (decimal 10,3), `unit` (enum `g/kg/ml/l/pcs/tbsp/tsp/cup`), timestamps.
- ✅ **1.4 Models** — `app/Models/Ingredient.php` (`pantryItems()` hasMany, `creator()` belongsTo, scopes `availableTo(int $userId)` and `search(string $term)`, `is_custom` bool cast) and `app/Models/PantryItem.php` (`user()`/`ingredient()` belongsTo, `forUser(int $userId)` scope, casts `quantity`=`decimal:3` + `unit`=`App\Enums\Unit`). `User::pantryItems()` added. New `app/Enums/Unit.php` (8 units + Ukrainian `label()`/`options()`/`values()`). Factories `IngredientFactory` (+`custom()` state) and `PantryItemFactory`; starter `PantryItemSeeder` (6 items for `test@example.com`).
- ⬜ **1.5–1.9 UI/CRUD** — pantry index page, add form with ingredient autocomplete (`/ingredients/search`), custom-ingredient-on-the-fly, edit/delete, validation. Next PR.

Tests: `tests/Feature/PantryModelTest.php` (relations / scopes / casts), `tests/Feature/IngredientCatalogTest.php` (seeder ≥150 + `search`/`availableTo` scopes), `tests/Unit/UnitEnumTest.php`.

Outstanding: 1.5 – 1.9.

---

## Epic 2 — Family members 🟡

- ✅ **2.1 `family_members` migration** — `database/migrations/2026_05_13_165321_create_family_members_table.php` creates the table with `id`, `user_id` (FK → `users`, cascadeOnDelete), `name` (string), `favorite_products` / `disliked_products` / `allergies_and_diets` (text, nullable), and timestamps. Migration applied to dev DB (`php artisan migrate` → DONE).
- ✅ **2.2 `FamilyMember` Eloquent model** — `src/app/Models/FamilyMember.php` adds the model with `#[Fillable]`, `belongsTo(User)` via `user()`, and `scopeForUser(int $userId)`. `src/app/Models/User.php` adds `familyMembers(): HasMany`. Factory at `src/database/factories/FamilyMemberFactory.php` (Ukrainian sample arrays + `User::factory()` FK). `src/database/seeders/FamilyMemberSeeder.php` creates 5 deterministic members (Тато, Мама, Бабуся, Син, Донька) for `test@example.com`, wired into `DatabaseSeeder`. `composer test --filter=FamilyMemberTest` → 3/3 pass; full suite 28/28.
- ✅ **2.3 List page** — `src/app/Http/Controllers/FamilyMemberController.php` adds `index()` that loads `$request->user()->familyMembers()->orderBy('id')->get()`; `src/routes/web.php` swaps `Route::view('/family', …)` for `Route::get('/family', [FamilyMemberController::class, 'index'])->name('family.index')`. `src/resources/views/family/index.blade.php` renders a 1/2/3-col card grid with name + color-coded chips (`favorite_products` green / `disliked_products` amber / `allergies_and_diets` red), plus an empty-state block with CTA (CTA href is a `#` placeholder until 2.4 lands the create form). Feature test at `src/tests/Feature/FamilyMemberIndexTest.php` covers guest→login redirect, own-vs-other isolation, and empty state. `composer test --filter=FamilyMember` → 31/31 pass. `php artisan route:list --name=family` → `family.index → FamilyMemberController@index`. `php artisan migrate:fresh --seed` → DONE; logging in as `test@example.com` / `password` shows the 5 seeded cards.
- ✅ **2.4 Add/edit form** — `FamilyMemberController` gains `create/store/edit/update`. Routes `family.create` (GET `/family/create`), `family.store` (POST `/family`), `family.edit` (GET `/family/{familyMember}/edit`), `family.update` (PATCH `/family/{familyMember}`) added under `auth`. Validation in `app/Http/Requests/FamilyMemberRequest.php` (name required ≤255; three text fields nullable ≤1000; Ukrainian messages/attributes). Shared Blade form `resources/views/family/partials/form.blade.php` (name input + three placeholder-hinted textareas) used by new `family/create.blade.php` and `family/edit.blade.php`; new reusable `components/textarea.blade.php`. `family/index.blade.php` now has a "Додати члена сім'ї" CTA (header + empty state), per-row "Редагувати" links, and a success flash. Ownership enforced: create binds `user_id` via the relationship (no spoofing); edit/update return 403 for другого юзера (`FamilyMemberRequest::authorize()` runs before validation; `edit()` guards with `abort_unless`). Tests: `tests/Feature/FamilyMemberFormTest.php` (9 cases — view/create/validation/spoof-guard/edit/update/403s). This also satisfies the pre-existing `FamilyMemberIndexTest` empty-state assertions (`Ще немає членів сім'ї` + `Додати члена сім'ї`) that the old placeholder copy did not.
- ⬜ **2.5 Delete flow** — no delete action / confirmation modal.

Outstanding: 2.5.

---

## Epic 3 — AI Core 🟡

- ✅ **3.1 `recipes` migration** — `database/migrations/2026_06_10_165231_create_recipes_table.php` creates the table with `id`, `user_id` (FK → `users`, cascadeOnDelete), `name`, five NOT NULL json columns (`ingredients_json`, `steps_json`, `kbju_json`, `pantry_snapshot_json`, `selected_family_members_json`), `status` enum `generated|cooked` (default `generated`), `is_favorite` (default `false`), nullable `cooked_at`, and timestamps. `php artisan migrate` → DONE; `migrate:rollback --step=1` + re-migrate → DONE; `composer test` → 40/40 pass (sqlite `:memory:` builds the schema). Spec: `docs/tickets/epic-3/task-1.md`.

- ✅ **3.2 `recipe_cache` migration** — `database/migrations/2026_06_10_170218_create_recipe_cache_table.php` creates the table (singular name, per plan) with `cache_key` (string PK), `response_json` (json NOT NULL), `model_used` (string), and `created_at` (`useCurrent()` + index for the future TTL cleanup). No `id`/`updated_at` — insert-only cache rows. `php artisan migrate` → DONE; `migrate:rollback --step=1` + re-migrate → DONE; `composer test` → 40/40 pass. Note for 3.9: the Eloquent model must set `protected $table = 'recipe_cache'`. Spec: `docs/tickets/epic-3/task-2.md`.

- ✅ **3.3 Service `ClaudeService`** — official `anthropic-ai/sdk` (^0.29.1) added via composer. `src/app/Services/ClaudeService.php` wraps it with `generateText(array $messages, ?string $system = null, ?string $model = null): string` and `generateFromImage(array $images, string $prompt, ?string $model = null): string` (typed `ImageBlockParam`/`Base64ImageSource`/`TextBlockParam` blocks). Retry on 429/529/5xx is delegated to the SDK (`maxRetries: 2`, `timeout: 120s` set on the `Anthropic\Client` in `AppServiceProvider` singleton binding, key from `config('services.anthropic.api_key')`); the service adds `Log::error` context (model, status, error type — no key) and rethrows typed exceptions. `tests/Unit/ClaudeServiceTest.php` (5 tests) stubs a PSR-18 transporter to assert wire payloads (model/system/max_tokens, image `media_type` blocks), error logging, and singleton wiring — `composer test` → 45/45. Live tinker smoke through the container returned a real Haiku 4.5 response. Spec: `docs/tickets/epic-3/task-3.md`.

- ✅ **3.4 Service `RecipePromptBuilder`** — `src/app/Services/RecipePromptBuilder.php` adds `build(User $user, array $members, array $pantry): array{system, user}` (Ukrainian prompts). System prompt encodes all fixed product rules: JSON-only response per `RecipeSchema::STRUCTURE`, broad allergy interpretation ("горіхи" → tree nuts + peanut; doubt → exclude), AND-combined member constraints, pantry-unit matching (closed enum), 1-2 staples with `in_pantry: false`, КБЖУ per portion. User prompt lists pantry rows (`name — qty unit`) and per-member non-empty constraints (алергії "суворо виключити" / неулюблене "уникати" / улюблене "бажано врахувати"). Pantry comes in as a plain array (PantryItem doesn't exist yet — 3.8 will map it); `$user` reserved per plan signature. `tests/Unit/RecipePromptBuilderTest.php` (7 tests incl. 0-members and empty-pantry edges) → `composer test` 52/52. Spec: `docs/tickets/epic-3/task-4.md`.

- ✅ **3.5 Recipe JSON schema** — delivered together with 3.4 (user decision): `src/app/Services/RecipeSchema.php` fixes the canonical response structure as the `STRUCTURE` constant (name/description/ingredients[{name,quantity,unit,in_pantry}]/steps[]/kbju{kcal,protein,fat,carbs}/servings) plus contract constants for the 3.6 parser: `REQUIRED_KEYS`, `INGREDIENT_KEYS`, `KBJU_KEYS`, `ALLOWED_UNITS` (г/кг/мл/л/шт/ст.л./ч.л./склянка).

- ✅ **3.6 Парсер відповіді Claude + валідація схеми** — `src/app/Services/RecipeResponseParser.php`: `parse(string $raw): array` зрізає markdown-обгортку (\`\`\`json), декодує JSON (`JSON_THROW_ON_ERROR`) і валідує проти контрактних констант `RecipeSchema` (всі `REQUIRED_KEYS`; `ingredients` непорожній, кожен елемент з `INGREDIENT_KEYS`, `quantity` > 0, `unit` ∈ `ALLOWED_UNITS`, `in_pantry` bool; `steps` непорожній список рядків; `kbju` з числовими `KBJU_KEYS`; `servings` ≥ 1), повертає нормалізований масив (лише 6 ключів, quantity/kbju → float, servings → int). `parseWithRetry(callable $generate, int $maxAttempts = 2)` — retry-цикл на невалідну відповідь (у 3.8 `$generate` буде замиканням навколо `ClaudeService::generateText`; transport-retry робить SDK). Перший кастомний виняток — `src/app/Exceptions/InvalidRecipeResponseException.php` з фабриками `invalidJson`/`missingKey`/`emptyList`/`invalidValue`, що називають причину (для логів 3.11). Чиста логіка без контейнера/мережі: `tests/Unit/RecipeResponseParserTest.php` (15 тестів) на plain PHPUnit TestCase. `composer test` → 67/67; pint чистий. Spec: `docs/tickets/epic-3/task-6.md`.

No `app/Jobs/` directory (so no `RecipeGenerationJob`). `/recipes` is a placeholder route. Queue connection is set to `database` and the jobs table migration exists, so infra is ready when the job is added.

Outstanding: 3.7 – 3.12.

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

Epic 3's pure-logic half is done (3.1–3.6: migrations, `ClaudeService`, prompts+schema, parser with retry). The remaining 3.7–3.10 (generation page + job + cache + recipe card) all want a real pantry to generate from, so the recommended next move is **Epic 1 (pantry)** — start with 1.1–1.2 (`ingredients` migration + seeder ≈150 products) and 1.3–1.4 (`Ingredient`/`PantryItem` models); that also unblocks Epics 4 and 6. Independently, Epic 2 still needs ticket 2.5 (delete a family member with confirmation: `FamilyMemberController@destroy` + `family.destroy` route, delete button with `x-modal`/`x-danger-button` confirmation, ownership guard, feature test). Still worth flipping `APP_LOCALE=en` → `uk` in `src/.env` before deeper UI work.