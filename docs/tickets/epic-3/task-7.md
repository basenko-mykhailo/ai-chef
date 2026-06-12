# Епік 3.7 — Сторінка генерації рецепту

> Заповнено: 2026-06-12 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

Чекбокси з членами сім'ї (за замовчуванням усі), показ поточної комори, велика кнопка "Згенерувати рецепт".

## 2. Додатковий контекст

- **Скоуп кнопки:** реальний ендпоінт + Queue Job — це тікет 3.8. У 3.7 форма POST-иться у **стаб-роут** `recipes.generate`, який лише показує flash «генерація буде доступна незабаром» і робить redirect назад на сторінку. Сторінка повністю клікабельна end-to-end; у 3.8 тіло стабу заміниться на dispatch `RecipeGenerationJob`. Жодної мертвої/помилкової кнопки.
- **Іменування:** новий `RecipeController@create`; `GET /recipes/create` → `recipes.create` → `resources/views/recipes/create.blade.php` (узгоджено з конвенцією pantry/family — `create.blade.php`). Стаб `POST /recipes/generate` → `recipes.generate`. Існуючий placeholder `/recipes` лишається як `recipes.index`.
- **Edge cases (усі чотири):**
  - **Порожня комора** — показати нотіс + лінк на `/pantry/create`, кнопку генерації задизейблити (немає з чого готувати).
  - **Нуль членів сім'ї** — сторінка працює, генерація «для себе» без обмежень; підказка, що можна додати членів для персоналізованих рецептів.
  - **Rate-limit нотіс** — невелика підказка «10 генерацій на годину» біля кнопки (саме throttle-middleware — тікет 3.12).
  - **Default-all** — усі чекбокси членів сім'ї відмічені за замовчуванням (за plan.md), користувач може зняти галочку.

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - Епік 1 (комора) ✅ — `PantryItem` модель + `forUser(int $userId)` scope, `Unit` enum з укр. `label()`, сторінка `/pantry`.
  - Епік 2 (сім'я) ✅ — `FamilyMember` модель + `forUser(int $userId)` scope, `User::familyMembers()`.
  - 3.1–3.6 ✅ — міграція `recipes`, `ClaudeService`, `RecipePromptBuilder`, `RecipeSchema`, `RecipeResponseParser`.
- **Блокує ця задача** (наступні тікети, які чекають):
  - **3.8** Endpoint + Queue Job — замінить стаб `recipes.generate` на реальний dispatch `RecipeGenerationJob`; читає обрані member ids + комору саме з цієї форми.
  - **3.9** логіка кешу, **3.10** картка рецепту, **3.11** обробка помилок, **3.12** rate limiting — усі будуються поверх цього флоу.

## 4. Скоуп

**В скоупі:**
- `RecipeController@create` — рендерить сторінку генерації (комора + члени сім'ї поточного юзера).
- `resources/views/recipes/create.blade.php` — brand-styled сторінка: чекбокси членів сім'ї (default-all checked), read-only список поточної комори (`name` + `quantity` + укр. `Unit::label()`), велика кнопка «Згенерувати рецепт».
- Guard порожньої комори (нотіс + лінк на `/pantry/create`, disabled-кнопка), обробка нуля членів сім'ї («для себе» + підказка), текстова rate-limit підказка «10 генерацій на годину».
- Стаб `POST /recipes/generate` → `RecipeController@generate`: flash «генерація буде доступна незабаром» + redirect назад на `recipes.create`. Рецепт **не** створюється.
- Лінк/CTA на сторінку генерації (з placeholder `/recipes` та/або навігації).
- Feature-тест сторінки.

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Реальний виклик Claude / `RecipeGenerationJob` (3.8).
- Lookup/збереження кешу (3.9).
- Рендер картки згенерованого рецепту (3.10).
- Обробка помилок генерації / retry-UI (3.11).
- Реальне enforcement throttle-middleware (3.12) — тут лише текстова підказка.

## 5. Файли (під `src/`)

- `src/app/Http/Controllers/RecipeController.php` — **новий**; `create()` (рендер) + стаб `generate()` (flash + redirect).
- `src/routes/web.php` — додати під `auth`: `GET /recipes/create` → `recipes.create`, `POST /recipes/generate` → `recipes.generate`; імпорт `RecipeController`.
- `src/resources/views/recipes/create.blade.php` — **новий**; сама сторінка.
- `src/resources/views/placeholder.blade.php` або `layouts/navigation.blade.php` — додати CTA/лінк «Згенерувати рецепт» на `recipes.create` (точне місце — під час імплементації, узгоджено зі стилем pantry/family).
- `src/tests/Feature/RecipeGenerationPageTest.php` — **новий**; feature-тест.

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php php artisan make:controller RecipeController
docker compose exec php php artisan route:list --name=recipes
docker compose exec php composer test --filter=RecipeGenerationPage
docker compose exec php vendor/bin/pint
```

## 7. Acceptance criteria

- [ ] `GET /recipes/create` під `auth` повертає 200 авторизованому юзеру; гість → redirect на login.
- [ ] Сторінка показує всіх членів сім'ї **поточного** юзера як чекбокси, усі відмічені за замовчуванням; чужі члени сім'ї не з'являються (ізоляція по `forUser`).
- [ ] Сторінка показує поточну комору юзера (name + quantity + укр. `Unit::label()`), read-only.
- [ ] Порожня комора → видимий нотіс + лінк на додавання продуктів, кнопка генерації задизейблена.
- [ ] Нуль членів сім'ї → сторінка рендериться з підказкою «для себе»; кнопка генерації лишається доступною (якщо комора непорожня).
- [ ] Біля кнопки видима підказка «10 генерацій на годину».
- [ ] Сабміт форми POST-иться у `recipes.generate`, який (стаб) показує укр. flash «...буде доступно...» і робить redirect назад на `recipes.create`; жоден `recipes`-рядок не створюється.
- [ ] `composer test --filter=RecipeGenerationPage` зелений; `vendor/bin/pint` чистий.

## 8. Ризики / відкриті питання

- Стаб `recipes.generate` не можна сплутати з готовим 3.8 — лишити явний коментар-маркер `// TODO(3.8)` у `generate()`.
- Disable-кнопки на порожню комору — клієнтський; стаб-роут все одно нічого не робить, тож ризик мінімальний (3.8 додасть серверний guard).
