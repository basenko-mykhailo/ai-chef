# Епік 3.8 — Endpoint генерації + Queue Job

> Заповнено: 2026-06-12 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

Виклик Claude в `RecipeGenerationJob` (бо може зайняти 10+ сек). UI показує спіннер з polling-ом або redirect-ом після завершення.

## 2. Додатковий контекст

**Scope adjustments:**
- Реалізувати polling на фронтенді для перевірки статусу задачі.
- Додати колонку `generation_status` (pending, processing, completed, failed) у таблицю `recipes`.
- Логіка кешу (3.9) — поза скоупом цієї задачі.

**Naming preferences:**
- Клас задачі: `GenerateRecipeJob` (замість `RecipeGenerationJob` з plan.md).
- Поле статусу: `generation_status`.
- Імена маршрутів: `api.recipes.generate` (POST), `api.recipes.status` (GET).
- Шлях polling-ендпоінта: `/api/recipes/{recipe}/status`.

**Edge cases / gotchas:**
- Таймаути та rate-limit-и ClaudeService API → задача має ловити `AnthropicException` і ставити `failed`.
- Невалідний/непарсабельний JSON з `RecipeResponseParser` → `InvalidRecipeResponseException` після retry → `failed`.

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - 3.1 міграція `recipes` (`2026_06_10_165231_create_recipes_table.php`) — є всі JSON-колонки та `status`.
  - 3.3 `ClaudeService::generateText()` (singleton у `AppServiceProvider`, SDK retry на 429/5xx).
  - 3.4 `RecipePromptBuilder::build(User, list<FamilyMember>, list<array{name,quantity,unit}>)`.
  - 3.5 `RecipeSchema` + 3.6 `RecipeResponseParser::parseWithRetry(callable, int)` + `InvalidRecipeResponseException`.
  - 3.7 сторінка генерації (`RecipeController@create`, `recipes/create.blade.php`) + stub `generate()`.
  - Епік 1 (`PantryItem`, `Unit`, `User::pantryItems()`) та Епік 2 (`FamilyMember`, `User::familyMembers()`).
- **Блокує ця задача** (наступні тікети, які чекають):
  - 3.9 кеш (обгорне Claude-виклик у задачі), 3.10 картка рецепту (потребує збереженого `Recipe` + show), 3.11 обробка помилок (будується на стані `failed`), 3.12 rate limiting (обгорне endpoint генерації).
  - Епік 4 («Приготовано») та Епік 5 (історія/обране) — потребують моделі `Recipe`.

## 4. Скоуп

**В скоупі:**
- Нова міграція `add_generation_status_to_recipes_table`: додає `generation_status` (enum/string, default `pending`) та `generation_error` (text, nullable); робить `name`, `ingredients_json`, `steps_json`, `kbju_json` nullable (для рядка, що ще генерується). `pantry_snapshot_json` і `selected_family_members_json` лишаються NOT NULL (відомі при створенні).
- `App\Enums\GenerationStatus` (pending/processing/completed/failed) — у стилі наявного `App\Enums\Unit`.
- Модель `App\Models\Recipe` (casts для JSON-колонок + `generation_status`, `user()` belongsTo, `forUser()` scope) + `User::recipes()` hasMany + `RecipeFactory`.
- `GenerateRecipeJob` (`ShouldQueue`, `$tries=1`, `$timeout`>120s): ставить `processing` → будує prompt зі снапшотів рецепту → `RecipeResponseParser::parseWithRetry(closure → ClaudeService::generateText)` → на успіх заповнює `name/ingredients_json/steps_json/kbju_json` + `completed`; на `AnthropicException`/`InvalidRecipeResponseException`/`Throwable` ставить `failed` + `generation_error` + лог. Метод `failed()` як страхувальна сітка.
- `RecipeController@generate` (web): валідація обраних `members[]` (належність юзеру) через `GenerateRecipeRequest`, захист від порожньої комори, створення `Recipe` (`pending`) зі снапшотами комори та обраних членів, `GenerateRecipeJob::dispatch()`, JSON-відповідь `{recipe_id, status_url}`.
- `RecipeController@status` (web, JSON): ownership-guard (403 для чужого), повертає `{status, recipe|null, error|null}` для polling.
- `RecipeController@show` + **мінімальний** `recipes/show.blade.php` як ціль redirect-у після `completed` (3.10 замінить на повноцінну картку).
- Маршрути: `POST /api/recipes/generate` (`api.recipes.generate`), `GET /api/recipes/{recipe}/status` (`api.recipes.status`), `GET /recipes/{recipe}` (`recipes.show`) — усі під `auth` у `web.php` (session-auth + CSRF; Sanctum немає). Старий `recipes.generate` прибрати.
- Оновити `recipes/create.blade.php`: submit через `fetch` (CSRF з meta-тегу), full-screen спіннер, polling `api.recipes.status` кожні ~2с, redirect на `recipes.show` при `completed`, friendly-помилка + «Спробувати ще раз» при `failed`.

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Логіка кешу `recipe_cache` (3.9) — Claude викликається завжди.
- Повноцінна картка рецепту з КБЖУ/кнопками «Приготовано»/«В обране» (3.10).
- Поглиблена обробка помилок/UX (3.11) — тут лише мінімальний `failed`-стан.
- Rate limiting 10/год (3.12) — endpoint поки без throttle.

## 5. Файли (під `src/`)

- `database/migrations/2026_06_12_*_add_generation_status_to_recipes_table.php` — нова міграція (нові колонки + nullable).
- `app/Enums/GenerationStatus.php` — новий enum.
- `app/Models/Recipe.php` — нова модель.
- `app/Models/User.php` — `recipes(): HasMany`.
- `app/Jobs/GenerateRecipeJob.php` — нова задача (нова тека `app/Jobs/`).
- `app/Http/Controllers/RecipeController.php` — `generate()` (replace stub), `status()`, `show()`.
- `app/Http/Requests/GenerateRecipeRequest.php` — валідація `members[]`.
- `database/factories/RecipeFactory.php` — для тестів.
- `routes/web.php` — нові/змінені маршрути.
- `resources/views/recipes/create.blade.php` — fetch + спіннер + polling.
- `resources/views/recipes/show.blade.php` — мінімальна сторінка результату.
- `tests/Feature/RecipeGenerationFlowTest.php` — endpoint + status (Queue::fake).
- `tests/Feature/GenerateRecipeJobTest.php` — успіх/таймаут/невалідний JSON (mock ClaudeService).

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php php artisan make:migration add_generation_status_to_recipes_table
docker compose exec php php artisan make:job GenerateRecipeJob
docker compose exec php php artisan make:model Recipe -f
docker compose exec php php artisan make:request GenerateRecipeRequest
docker compose exec php php artisan migrate          # expect: DONE
docker compose exec php composer test
```

## 7. Acceptance criteria

- [ ] Міграція додає `generation_status` (default `pending`) + `generation_error` і робить `name`/контентні JSON-колонки nullable; `php artisan migrate` → DONE, rollback+remigrate чисто.
- [ ] POST `api.recipes.generate` з валідними `members[]` створює `Recipe` зі `generation_status=pending`, збереженими `pantry_snapshot_json` і `selected_family_members_json`, диспатчить `GenerateRecipeJob` (assert через `Queue::fake`), повертає JSON з `recipe_id` + `status_url`.
- [ ] Порожня комора та чужі/неіснуючі `member` id відхиляються (422/валідація); GET `api.recipes.status` для чужого рецепту → 403.
- [ ] `GenerateRecipeJob` з підставним `ClaudeService`, що повертає валідний JSON, ставить `generation_status=completed` і заповнює `name/ingredients_json/steps_json/kbju_json` зі схеми `RecipeResponseParser`.
- [ ] `GenerateRecipeJob` ловить `AnthropicException` (таймаут/rate-limit) і `InvalidRecipeResponseException` (непарсабельний JSON після retry) → `generation_status=failed` + заповнений `generation_error`; status-endpoint віддає `failed`.
- [ ] Сторінка генерації сабмітить через fetch, показує спіннер, опитує `api.recipes.status` і робить redirect на `recipes.show` при `completed` (мінімальна сторінка рендерить збережений рецепт).
- [ ] `composer test` зелений (нові тести + наявні 102).

## 8. Ризики / відкриті питання

- `->change()` на `name`/JSON-колонках на sqlite (`:memory:` тест-БД) — Laravel 13 має нативну підтримку, але варто перевірити, що тести й rollback проходять; як запасний варіант — additive-only міграція з плейсхолдерами (`name=''`, JSON=`[]`) при створенні.
- Снапшот членів сім'ї зберігається в `selected_family_members_json`; задача реконструює легкі `FamilyMember`-інстанси з нього для `RecipePromptBuilder` (стійко до видалення члена між сабмітом і виконанням задачі).
- Ціль redirect-у після `completed` — мінімальний `recipes.show`; 3.10 замінить на повноцінну картку (можливий незначний rework шаблону).
- `/api/...` маршрути живуть у `web.php` під session-auth + CSRF (Sanctum у проєкті немає) — свідоме рішення для polling тим самим cookie.
