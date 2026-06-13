# Епік 4.2 — Сторінка підтвердження списання

> Заповнено: 2026-06-13 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

Список інгредієнтів рецепту, які є в коморі (по `name + unit` match), з полями кількості (передзаповнені сумами з рецепту, але редаговані).

## 2. Додатковий контекст

Користувач обрав «Scope adjustments / Naming preferences / Edge cases», але деталі не надав («okay») — нижче мої припущення за замовчуванням, які можна змінити на gate плану:

- **Scope:** 4.2 наповнює GET-сторінку підтвердження (`recipes.cook.confirm`) реальним списком зіставлених інгредієнтів у редагованій `<form>`. Щоб форму можна було відправити вже зараз, додаю POST-маршрут `recipes.cook.store` + **stub**-метод `cook()` (захищений власністю, лише flash+redirect, **без** списання й без зміни статусу). Реальне списання — 4.3, перехід статусу — 4.4.
- **Naming:** новий маршрут `recipes.cook.store` (POST `/recipes/{recipe}/cook`), метод контролера `cook()`; існуючі `recipes.cook.confirm` / `confirmCook()` / `recipes/cook.blade.php` лишаються й доповнюються.
- **Matching/edge cases:** зіставлення інгредієнта рецепту з **поточною** коморою (`PantryItem`), а не зі снапшотом, бо списувати 4.3 буде з живої комори. Ключ зіставлення — `name` (case-insensitive) + одиниця як `Unit::label()` (одиниці в `ingredients_json` уже зберігаються як українські лейбли з `RecipeSchema::ALLOWED_UNITS`). Інгредієнти-стейпли (`in_pantry: false`) та незбіги за назвою/одиницею до списку не потрапляють. Конвертації одиниць (кг↔г) немає — це поза MVP.

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - 3.1 `recipes` + `ingredients_json` (масив `{name, quantity, unit, in_pantry}`); 3.10 картка рецепту з кнопкою «Приготовано».
  - 4.1 — кнопка «Приготовано» вже веде на `recipes.cook.confirm` → `RecipeController@confirmCook` → stub `resources/views/recipes/cook.blade.php`.
  - Епік 1 — `PantryItem` (`unit` cast у `App\Enums\Unit`, `ingredient()` belongsTo, `forUser()` scope), `Ingredient.name`, `Unit::label()` для зіставлення.
- **Блокує ця задача** (наступні тікети, які чекають):
  - 4.3 `PantryDeductionService` — приймає підтверджені кількості з цієї форми й атомарно віднімає від комори.
  - 4.4 `recipes.status='cooked'` + `cooked_at` + redirect «Готово, комору оновлено» — спрацьовує на сабміт цієї сторінки.

## 4. Скоуп

**В скоупі:**
- Доповнити `confirmCook()`: зібрати інгредієнти рецепту, що зіставляються з поточною коморою користувача за `name` (case-insensitive) + `Unit::label()`, кожен із кількістю з рецепту як передзаповненим значенням.
- Наповнити `recipes/cook.blade.php`: редагована `<form method="POST">` зі списком зіставлених рядків (назва, лейбл одиниці, `<input>` кількості, передзаповнений сумою з рецепту), кнопка підтвердження + «Назад до рецепту».
- Empty state: якщо жоден інгредієнт рецепту не зіставився з коморою — повідомлення «немає інгредієнтів для списання» + посилання назад, без форми.
- Додати POST-маршрут `recipes.cook.store` + stub-метод `cook()` (захист власністю → 403; поки що лише redirect/flash, **без** мутацій).
- Оновити/розширити `tests/Feature/RecipeCookConfirmTest.php`.

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Фактичне віднімання кількостей, DB-транзакція, видалення рядків ≤ 0 (4.3).
- Перехід `status='cooked'` + `cooked_at` + redirect «комору оновлено» (4.4).
- Конвертація між різними одиницями (кг↔г, л↔мл) — поза MVP.

## 5. Файли (під `src/`)

- `src/app/Http/Controllers/RecipeController.php` — доповнити `confirmCook()`, додати stub `cook()`.
- `src/resources/views/recipes/cook.blade.php` — редагований список + форма (зараз stub).
- `src/routes/web.php` — додати `POST /recipes/{recipe}/cook` → `recipes.cook.store`.
- `src/tests/Feature/RecipeCookConfirmTest.php` — розширити (зіставлення, передзаповнення, виключення незбігів, empty state, 403/guest, stub-сабміт не мутує).

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php composer test --filter=RecipeCookConfirmTest
docker compose exec php vendor/bin/pint app/Http/Controllers/RecipeController.php resources/views/recipes/cook.blade.php
```

## 7. Acceptance criteria

- [ ] GET `/recipes/{recipe}/cook` (власник, `completed` рецепт) рендерить редаговану форму з рядками **лише** тих інгредієнтів рецепту, що зіставлені з поточною коморою за case-insensitive `name` + одиницею (`Unit::label()`); кожен рядок має `<input>` кількості, передзаповнений кількістю з рецепту.
- [ ] Інгредієнти рецепту без збігу в поточній коморі (інша назва, інша одиниця або `in_pantry: false`/стейпл) у список **не** потрапляють.
- [ ] Якщо жоден інгредієнт не зіставився — показано empty-state з повідомленням і посиланням назад до рецепту, без форми сабміту.
- [ ] Форма постить на новий маршрут `recipes.cook.store`; сабміт **не** змінює кількостей у коморі й не змінює статус рецепту (це 4.3/4.4) — це захищений stub.
- [ ] Власність збережено: не-власник → 403 і на GET, і на POST; гість → redirect на `login`. Перегляд і сабміт не мутують рядки комори чи статус рецепту.
- [ ] `composer test --filter=RecipeCookConfirmTest` зелений; `pint` чистий.

## 8. Ризики / відкриті питання

- Інгредієнти з незбігом одиниць тихо виключаються (немає конвертації в MVP) — отже рецепт із «0.5 кг» не зіставиться з позицією комори «500 г». Прийнятно за правилом закритого enum одиниць, але варто пам'ятати як відоме обмеження.
- Рішення додати POST-stub зараз (щоб форма була відправною) vs. лишити сторінку GET-only до 4.3 — якщо на gate плану обереш GET-only, прибираю `recipes.cook.store`/`cook()`.
