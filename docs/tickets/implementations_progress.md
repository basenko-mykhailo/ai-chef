# AI Chef — Implementation Progress

> Snapshot date: 2026-06-10 (updated). Source plan: `docs/tickets/plan.md`.
> Legend: ✅ done · 🟡 partial · ⬜ not started

## Summary

| Epic | Status | Notes |
|---|---|---|
| 0. Foundation | ✅ done | All 6 tickets implemented |
| 1. Pantry (manual input) | ✅ done | data layer (1.1–1.4) + full CRUD UI (1.5–1.9): pantry page, add/edit/delete, autocomplete, custom-on-the-fly, validation |
| 2. Family members | ✅ done | 2.1–2.4 + 2.5 delete (per-row button, confirmation, ownership guard) |
| 3. AI Core (recipe generation) | 🟡 partial | 3.1–3.11 done (migrations, `ClaudeService`, prompts+schema, parser, generation page, endpoint+job, cache, recipe card, error handling); rate-limit (3.12) pending. Full suite (136 tests) runs green in the `php` container |
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

## Epic 1 — Pantry (manual input) ✅

All 9 tickets done — data layer (1.1–1.4) + CRUD UI (1.5–1.9).

- ✅ **1.1 `ingredients` migration** — `database/migrations/2026_06_11_120000_create_ingredients_table.php`: `id`, `name` (indexed for autocomplete), `category` (nullable), `is_custom` (default false), `created_by_user_id` (nullable FK → users, `nullOnDelete`), timestamps.
- ✅ **1.2 Ingredient seeder** — `database/seeders/IngredientSeeder.php` seeds ~170 Ukrainian catalog products across 14 categories (all `is_custom=false`), wired into `DatabaseSeeder`; idempotent (`firstOrCreate` on name).
- ✅ **1.3 `pantry_items` migration** — `database/migrations/2026_06_11_120100_create_pantry_items_table.php`: `id`, `user_id` (FK cascade), `ingredient_id` (FK cascade), `quantity` (decimal 10,3), `unit` (enum `g/kg/ml/l/pcs/tbsp/tsp/cup`), timestamps.
- ✅ **1.4 Models** — `app/Models/Ingredient.php` (`pantryItems()` hasMany, `creator()` belongsTo, scopes `availableTo(int $userId)` and `search(string $term)`, `is_custom` bool cast) and `app/Models/PantryItem.php` (`user()`/`ingredient()` belongsTo, `forUser(int $userId)` scope, casts `quantity`=`decimal:3` + `unit`=`App\Enums\Unit`). `User::pantryItems()` added. New `app/Enums/Unit.php` (8 units + Ukrainian `label()`/`options()`/`values()`). Factories `IngredientFactory` (+`custom()` state) and `PantryItemFactory`; starter `PantryItemSeeder` (6 items for `test@example.com`).
- ✅ **1.5 Pantry index** — `PantryController@index` + `resources/views/pantry/index.blade.php`: brand-styled (dark-green/cream from Figma) list of the user's items (name, category, quantity + Ukrainian unit label), empty state, "Додати продукт" CTA, per-row "Змінити"/"Видалити". `/pantry` route now controller-backed (was placeholder).
- ✅ **1.6 Add form + autocomplete** — `pantry/create.blade.php` + shared `pantry/partials/form.blade.php` (Alpine). Ingredient name field autocompletes via new `GET /ingredients/search?q=` (`IngredientController@search` → `Ingredient::availableTo($uid)->search($q)`, JSON, max 10). Quantity + unit `<select>` (from `Unit::options()`).
- ✅ **1.7 Custom ingredient on the fly** — `PantryController::resolveIngredient()`: picked id (validated against `availableTo`) → existing row; else case-insensitive name match; else creates `is_custom=true` + `created_by_user_id` ingredient. Optional category field shown only for new products.
- ✅ **1.8 Edit / delete** — `edit/update/destroy` with ownership guard (`PantryItemRequest::authorize()` for update → 403 before validation; `abort_unless` for edit/destroy). Delete via per-row form + JS confirm.
- ✅ **1.9 Validation** — `app/Http/Requests/PantryItemRequest.php`: `ingredient_name` required ≤100; `quantity` required numeric `gt:0`; `unit` ∈ `Unit::values()`; `category` nullable ≤50; `ingredient_id` nullable exists. Ukrainian messages.

Tests: `PantryModelTest`, `IngredientCatalogTest`, `UnitEnumTest` (data layer); `PantryCrudTest` (10: CRUD, custom-on-the-fly, validation, 403s), `IngredientSearchTest` (4). Brand colors added to `resources/css/app.css` `@theme` (`brand`/`cream`/`beige`/`ink`/`muted`). Verified live: `/pantry`, `/pantry/create`, autocomplete all 200.

Outstanding: none — Epic 1 complete.

---

## Epic 2 — Family members ✅

- ✅ **2.1 `family_members` migration** — `database/migrations/2026_05_13_165321_create_family_members_table.php` creates the table with `id`, `user_id` (FK → `users`, cascadeOnDelete), `name` (string), `favorite_products` / `disliked_products` / `allergies_and_diets` (text, nullable), and timestamps. Migration applied to dev DB (`php artisan migrate` → DONE).
- ✅ **2.2 `FamilyMember` Eloquent model** — `src/app/Models/FamilyMember.php` adds the model with `#[Fillable]`, `belongsTo(User)` via `user()`, and `scopeForUser(int $userId)`. `src/app/Models/User.php` adds `familyMembers(): HasMany`. Factory at `src/database/factories/FamilyMemberFactory.php` (Ukrainian sample arrays + `User::factory()` FK). `src/database/seeders/FamilyMemberSeeder.php` creates 5 deterministic members (Тато, Мама, Бабуся, Син, Донька) for `test@example.com`, wired into `DatabaseSeeder`. `composer test --filter=FamilyMemberTest` → 3/3 pass; full suite 28/28.
- ✅ **2.3 List page** — `src/app/Http/Controllers/FamilyMemberController.php` adds `index()` that loads `$request->user()->familyMembers()->orderBy('id')->get()`; `src/routes/web.php` swaps `Route::view('/family', …)` for `Route::get('/family', [FamilyMemberController::class, 'index'])->name('family.index')`. `src/resources/views/family/index.blade.php` renders a 1/2/3-col card grid with name + color-coded chips (`favorite_products` green / `disliked_products` amber / `allergies_and_diets` red), plus an empty-state block with CTA (CTA href is a `#` placeholder until 2.4 lands the create form). Feature test at `src/tests/Feature/FamilyMemberIndexTest.php` covers guest→login redirect, own-vs-other isolation, and empty state. `composer test --filter=FamilyMember` → 31/31 pass. `php artisan route:list --name=family` → `family.index → FamilyMemberController@index`. `php artisan migrate:fresh --seed` → DONE; logging in as `test@example.com` / `password` shows the 5 seeded cards.
- ✅ **2.4 Add/edit form** — `FamilyMemberController` gains `create/store/edit/update`. Routes `family.create` (GET `/family/create`), `family.store` (POST `/family`), `family.edit` (GET `/family/{familyMember}/edit`), `family.update` (PATCH `/family/{familyMember}`) added under `auth`. Validation in `app/Http/Requests/FamilyMemberRequest.php` (name required ≤255; three text fields nullable ≤1000; Ukrainian messages/attributes). Shared Blade form `resources/views/family/partials/form.blade.php` (name input + three placeholder-hinted textareas) used by new `family/create.blade.php` and `family/edit.blade.php`; new reusable `components/textarea.blade.php`. `family/index.blade.php` now has a "Додати члена сім'ї" CTA (header + empty state), per-row "Редагувати" links, and a success flash. Ownership enforced: create binds `user_id` via the relationship (no spoofing); edit/update return 403 for другого юзера (`FamilyMemberRequest::authorize()` runs before validation; `edit()` guards with `abort_unless`). Tests: `tests/Feature/FamilyMemberFormTest.php` (9 cases — view/create/validation/spoof-guard/edit/update/403s). This also satisfies the pre-existing `FamilyMemberIndexTest` empty-state assertions (`Ще немає членів сім'ї` + `Додати члена сім'ї`) that the old placeholder copy did not.
- ✅ **2.5 Delete flow** — `FamilyMemberController@destroy` + `DELETE /family/{familyMember}` (`family.destroy`). Per-row «Видалити» button on `family/index.blade.php` with JS `confirm`; ownership guard (`abort_unless` → 403); `family-member-deleted` flash. Test `tests/Feature/FamilyMemberDeleteTest.php` (guest redirect / owner deletes / 403 for others).

Outstanding: none — Epic 2 complete.

---

## Epic 3 — AI Core 🟡

- ✅ **3.1 `recipes` migration** — `database/migrations/2026_06_10_165231_create_recipes_table.php` creates the table with `id`, `user_id` (FK → `users`, cascadeOnDelete), `name`, five NOT NULL json columns (`ingredients_json`, `steps_json`, `kbju_json`, `pantry_snapshot_json`, `selected_family_members_json`), `status` enum `generated|cooked` (default `generated`), `is_favorite` (default `false`), nullable `cooked_at`, and timestamps. `php artisan migrate` → DONE; `migrate:rollback --step=1` + re-migrate → DONE; `composer test` → 40/40 pass (sqlite `:memory:` builds the schema). Spec: `docs/tickets/epic-3/task-1.md`.

- ✅ **3.2 `recipe_cache` migration** — `database/migrations/2026_06_10_170218_create_recipe_cache_table.php` creates the table (singular name, per plan) with `cache_key` (string PK), `response_json` (json NOT NULL), `model_used` (string), and `created_at` (`useCurrent()` + index for the future TTL cleanup). No `id`/`updated_at` — insert-only cache rows. `php artisan migrate` → DONE; `migrate:rollback --step=1` + re-migrate → DONE; `composer test` → 40/40 pass. Note for 3.9: the Eloquent model must set `protected $table = 'recipe_cache'`. Spec: `docs/tickets/epic-3/task-2.md`.

- ✅ **3.3 Service `ClaudeService`** — official `anthropic-ai/sdk` (^0.29.1) added via composer. `src/app/Services/ClaudeService.php` wraps it with `generateText(array $messages, ?string $system = null, ?string $model = null): string` and `generateFromImage(array $images, string $prompt, ?string $model = null): string` (typed `ImageBlockParam`/`Base64ImageSource`/`TextBlockParam` blocks). Retry on 429/529/5xx is delegated to the SDK (`maxRetries: 2`, `timeout: 120s` set on the `Anthropic\Client` in `AppServiceProvider` singleton binding, key from `config('services.anthropic.api_key')`); the service adds `Log::error` context (model, status, error type — no key) and rethrows typed exceptions. `tests/Unit/ClaudeServiceTest.php` (5 tests) stubs a PSR-18 transporter to assert wire payloads (model/system/max_tokens, image `media_type` blocks), error logging, and singleton wiring — `composer test` → 45/45. Live tinker smoke through the container returned a real Haiku 4.5 response. Spec: `docs/tickets/epic-3/task-3.md`.

- ✅ **3.4 Service `RecipePromptBuilder`** — `src/app/Services/RecipePromptBuilder.php` adds `build(User $user, array $members, array $pantry): array{system, user}` (Ukrainian prompts). System prompt encodes all fixed product rules: JSON-only response per `RecipeSchema::STRUCTURE`, broad allergy interpretation ("горіхи" → tree nuts + peanut; doubt → exclude), AND-combined member constraints, pantry-unit matching (closed enum), 1-2 staples with `in_pantry: false`, КБЖУ per portion. User prompt lists pantry rows (`name — qty unit`) and per-member non-empty constraints (алергії "суворо виключити" / неулюблене "уникати" / улюблене "бажано врахувати"). Pantry comes in as a plain array (PantryItem doesn't exist yet — 3.8 will map it); `$user` reserved per plan signature. `tests/Unit/RecipePromptBuilderTest.php` (7 tests incl. 0-members and empty-pantry edges) → `composer test` 52/52. Spec: `docs/tickets/epic-3/task-4.md`.

- ✅ **3.5 Recipe JSON schema** — delivered together with 3.4 (user decision): `src/app/Services/RecipeSchema.php` fixes the canonical response structure as the `STRUCTURE` constant (name/description/ingredients[{name,quantity,unit,in_pantry}]/steps[]/kbju{kcal,protein,fat,carbs}/servings) plus contract constants for the 3.6 parser: `REQUIRED_KEYS`, `INGREDIENT_KEYS`, `KBJU_KEYS`, `ALLOWED_UNITS` (г/кг/мл/л/шт/ст.л./ч.л./склянка).

- ✅ **3.6 Парсер відповіді Claude + валідація схеми** — `src/app/Services/RecipeResponseParser.php`: `parse(string $raw): array` зрізає markdown-обгортку (\`\`\`json), декодує JSON (`JSON_THROW_ON_ERROR`) і валідує проти контрактних констант `RecipeSchema` (всі `REQUIRED_KEYS`; `ingredients` непорожній, кожен елемент з `INGREDIENT_KEYS`, `quantity` > 0, `unit` ∈ `ALLOWED_UNITS`, `in_pantry` bool; `steps` непорожній список рядків; `kbju` з числовими `KBJU_KEYS`; `servings` ≥ 1), повертає нормалізований масив (лише 6 ключів, quantity/kbju → float, servings → int). `parseWithRetry(callable $generate, int $maxAttempts = 2)` — retry-цикл на невалідну відповідь (у 3.8 `$generate` буде замиканням навколо `ClaudeService::generateText`; transport-retry робить SDK). Перший кастомний виняток — `src/app/Exceptions/InvalidRecipeResponseException.php` з фабриками `invalidJson`/`missingKey`/`emptyList`/`invalidValue`, що називають причину (для логів 3.11). Чиста логіка без контейнера/мережі: `tests/Unit/RecipeResponseParserTest.php` (15 тестів) на plain PHPUnit TestCase. `composer test` → 67/67; pint чистий. Spec: `docs/tickets/epic-3/task-6.md`.

- ✅ **3.7 Сторінка генерації рецепту** — new `app/Http/Controllers/RecipeController.php`: `create()` renders the page from `$user->pantryItems()->with('ingredient')` + `$user->familyMembers()`; stub `generate()` flashes `recipe-generation-pending` and redirects back (`// TODO(3.8)`, no recipe created). Routes `recipes.create` (GET `/recipes/create`) + `recipes.generate` (POST `/recipes/generate`) added under `auth` in `routes/web.php` (placeholder `recipes.index` kept). View `resources/views/recipes/create.blade.php` (brand palette): family-member checkboxes **all checked by default** (`name="members[]"`), read-only pantry list (`Unit::label()` + trimmed quantity), large «Згенерувати рецепт» button. Edge cases: empty pantry → notice + link to `pantry.create` and disabled button; zero members → «для себе» hint with active button; visible "10 генерацій на годину" hint (real throttle is 3.12). Nav «Рецепти» (desktop + mobile in `layouts/navigation.blade.php`) now points to `recipes.create`. Test `tests/Feature/RecipeGenerationPageTest.php` (6 cases: guest redirect, own-vs-other isolation, default-checked, empty-pantry disabled, zero-members hint, stub redirect + `assertDatabaseCount('recipes', 0)`). `composer test` → 102/102; `pint` clean. Spec: `docs/tickets/epic-3/task-7.md`.

- 🟡 **3.8 Endpoint генерації + Queue Job** — *(code complete; `migrate` + `composer test` NOT run on this host — no Docker/PHP runtime available, must be verified in the `php` container.)* Async flow wires 3.1–3.6 together. New migration `database/migrations/2026_06_12_120000_add_generation_status_to_recipes_table.php` (additive — adds `generation_status` default `pending`, `generation_error`, `description`, `servings`; leaves the AI columns NOT NULL, seeded with `''`/`[]` placeholders on the pending row to avoid sqlite column-rebuilds). New `app/Enums/GenerationStatus.php` (pending/processing/completed/failed + Ukrainian `label()`), `app/Models/Recipe.php` (array casts for the 5 json cols, `GenerationStatus` cast, `user()` + `forUser()`), `User::recipes()`, `database/factories/RecipeFactory.php` (+`completed()` state). `app/Jobs/GenerateRecipeJob.php` (`ShouldQueue`, `$tries=1`, `$timeout=180`): `processing` → rebuilds `FamilyMember`s from `selected_family_members_json` snapshot → `RecipePromptBuilder::build` → `RecipeResponseParser::parseWithRetry(fn → ClaudeService::generateText)` → fills name/description/ingredients/steps/kbju/servings + `completed`; `catch (Throwable)` (covers `AnthropicException` timeouts/rate-limits + `InvalidRecipeResponseException`) and `failed()` hook → `failed` + friendly `generation_error`. `RecipeController`: stub `generate()` replaced — validates `members[]` via new `app/Http/Requests/GenerateRecipeRequest.php` (`Rule::exists` scoped to user), rejects empty pantry (422), snapshots pantry (units as `Unit::label()` so they match `RecipeSchema::ALLOWED_UNITS`) + selected members, creates `Recipe(pending)`, dispatches the job, returns `{recipe_id, status_url}`; new `status()` (ownership 403, JSON `{status, error, recipe}` for polling) + minimal `show()`. Routes in `routes/web.php`: `POST /api/recipes/generate` (`api.recipes.generate`), `GET /api/recipes/{recipe}/status` (`api.recipes.status`), `GET /recipes/{recipe}` (`recipes.show`); old `recipes.generate` removed. `resources/views/recipes/create.blade.php` now submits via Alpine `fetch` + CSRF meta, shows a spinner overlay, polls the status endpoint every 2s, redirects to `recipes.show` on `completed` / shows error + retry on `failed`; new minimal `resources/views/recipes/show.blade.php` (3.10 replaces it). Tests authored: `tests/Feature/RecipeGenerationFlowTest.php` (8 — guest 401, pending+snapshot+`Queue::assertPushed`, zero-members, empty-pantry 422, foreign-member 422, status pending/completed/403) and `tests/Feature/GenerateRecipeJobTest.php` (3 — success completes; API failure → failed; unparseable JSON → failed after retry); obsolete stub test removed from `RecipeGenerationPageTest`. Spec: `docs/tickets/epic-3/task-8.md`.

`app/Jobs/` now exists. Queue connection is `database` and the jobs table migration exists, so a `php artisan queue:listen` worker will process `GenerateRecipeJob`.

- ✅ **3.9 Логіка кешу** — `src/app/Services/RecipeCacheKeyBuilder.php` builds a deterministic `sha256` `cache_key` from the recipe's `pantry_snapshot_json` + `selected_family_members_json` (normalized: pantry `usort`ed by `[name, unit, quantity]`, members reduced to constraint-only tuples `{favorite/disliked/allergies_and_diets}` and `usort`ed — **member name excluded**, so identical-constraint members and reordered snapshots collapse to one key). `src/app/Models/RecipeCache.php` targets the existing `recipe_cache` table (string PK `cache_key`, `$incrementing=false`, `$timestamps=false` since there is no `updated_at` — `created_at` comes from the migration's `useCurrent()`, `response_json`→array cast). `GenerateRecipeJob::handle` now injects `RecipeCacheKeyBuilder`: it computes the key and `RecipeCache::find()`s **before** any Claude call — on a **hit** it fills the recipe from `response_json` and returns (no `ClaudeService`/prompt build); on a **miss** it generates as before, then `RecipeCache::create([...,'model_used'=>config('services.anthropic.default_model')])` and fills. The recipe-update block was extracted into a shared `private fillFromResponse(array $parsed)`. Tests: `tests/Unit/RecipeCacheKeyBuilderTest.php` (10 — determinism, pantry/member order-independence, sensitivity to qty/unit/name/constraint changes, name-excluded, empty-members stable key) + 2 cases in `tests/Feature/GenerateRecipeJobTest.php` (`test_cache_hit_fills_recipe_without_calling_claude` asserts `generateText` `->never()`; `test_cache_miss_stores_response_under_the_key` asserts the row is written with `model_used`). `vendor/bin/phpunit --filter='RecipeCacheKeyBuilderTest|GenerateRecipeJobTest'` → 15/15; full `composer test` → 124/124; `pint` clean. No new migration — `recipe_cache` table already shipped in 3.2. Spec: `docs/tickets/epic-3/task-9.md`.
  - *Deviation from the literal plan wording* («sorted selected family member ids, their constraint hashes»): the 3.8 controller never snapshotted member ids, so the key hashes the **constraints themselves** — equivalent for recipe purposes and additionally dedups members that differ only by name.

- ✅ **3.10 Картка рецепту (UI)** — `resources/views/recipes/show.blade.php` rewritten from the minimal 3.8 stub into the full card: name/description/servings, ingredients flagged «є в коморі»/«треба купити», numbered steps, КБЖУ block (per portion, `?? '—'` fallback), plus an action bar with «Приготовано» + «В обране». Three render states kept: not-`completed` → amber "ще готується"; `failed` → error + «Спробувати ще раз»; `completed` → card. Cooked recipes (`status='cooked'`) show a «Приготовано {cooked_at}» badge and a disabled cook button. **«В обране»** is wired to a minimal toggle — new `RecipeController@toggleFavorite` on `PATCH /recipes/{recipe}/favorite` (`recipes.favorite`, route in `routes/web.php`), ownership-guarded (`abort_unless … 403`), flips `is_favorite`, `back()` + `recipe-favorite-flash`; the heart icon reflects state. **«Приготовано»** is rendered as a disabled placeholder («Списання комори — незабаром») — its real confirmation→deduction flow is Epic 4.1; AJAX favoriting on lists is 5.3. Test `tests/Feature/RecipeShowTest.php` (9 — full-card render, both buttons, guest redirect, non-owner 403 on show, pending-state-not-card, owner toggle false→true→false, non-owner/guest toggle blocked, cooked badge + disabled button). `composer test --filter=RecipeShowTest` → 133/133; `pint` clean. No migration (`is_favorite`/`status`/`cooked_at` shipped in 3.1/3.8). Spec: `docs/tickets/epic-3/task-10.md`.

- ✅ **3.11 Обробка помилок генерації** — `app/Jobs/GenerateRecipeJob.php` now maps the failure cause to a friendly Ukrainian `generation_error` (three private consts): `InvalidRecipeResponseException` → «AI повернув некоректну відповідь…»; any other caught `Throwable` (API/network/rate-limit/SDK-timeout) → «Сервіс генерації тимчасово недоступний…»; the `failed()` worker-timeout hook → «Генерація зайняла забагато часу…». Technical detail (exception class + message) stays in `Log::error` only — never in `generation_error`/UI (`markFailed(string $message)` now takes the text). UI retry: `resources/views/recipes/create.blade.php` failed banner gains an explicit «Спробувати ще раз» button (Alpine `retry()` → `requestSubmit()` re-runs `start()`); `resources/views/recipes/show.blade.php` splits the non-completed block so `Failed` renders a red box + brand «Спробувати ще раз» → `recipes.create` (pending/processing keep the amber «ще не готовий» box). Tests: strengthened `GenerateRecipeJobTest` api-failure/invalid-JSON cases (assert friendly text + no technical leak) + new worker-timeout-hook case; new `RecipeShowTest::test_failed_recipe_shows_error_and_retry_button` and `RecipeGenerationPageTest::test_page_renders_retry_button_in_failed_banner`. `composer test` → 136/136; `pint` clean. No migration (`generation_status`/`generation_error` shipped in 3.8). Spec: `docs/tickets/epic-3/task-11.md`.

Outstanding: 3.12 (rate limiting).

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

3.1–3.11 are done and the full suite (136 tests) runs green in the `php` container, so the AI core's data + generation + cache path, the result card, **and** friendly error handling (cause-specific message + «Спробувати ще раз») are complete (still worth a live smoke with a real `ANTHROPIC_API_KEY` + `queue:listen` worker to exercise a real Claude round-trip, confirm a second identical generation hits `recipe_cache`, and force a failure to see the friendly retry path).

The only remaining Epic 3 work is **3.12 rate limiting** (10 gen/hr throttle on `api.recipes.generate` — its 429 can reuse the failed banner from 3.11). After that, **Epic 4.1** will replace the card's disabled «Приготовано» placeholder with the confirmation→deduction flow, and **5.3** will extend the favorite toggle to AJAX hearts on history/list pages. Still worth flipping `APP_LOCALE=en` → `uk` in `src/.env` before deeper UI work.