# Епік 4.1 — Кнопка "Приготовано" на картці рецепту

> Заповнено: 2026-06-13 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

**4.1 Кнопка "Приготовано" на картці рецепту**
Веде на сторінку підтвердження списання, не списує одразу.

## 2. Додатковий контекст

Користувач обрав «Scope adjustments / Naming preferences / Edge cases», але далі підтвердив роботу за дефолтами:

- **Скоуп:** активна кнопка на картці → перехід на сторінку-підтвердження (GET). Сама сторінка — мінімальний stub; повний список інгредієнтів із редагуванням кількостей — тікет 4.2.
- **Іменування (дефолти):** маршрут `recipes.cook.confirm` на `GET /recipes/{recipe}/cook`, метод `RecipeController@confirmCook`, в'юха `recipes/cook.blade.php`.
- **Edge cases:** кнопка лише для `generation_status = completed` і `status != cooked`; для вже приготованого — наявний disabled-стан «Вже приготовано»; перехід **нічого не мутує** (комора й `recipes.status` без змін); ownership-guard 403 для чужого рецепту, redirect для гостя.

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - Епік 3 ✅ — таблиця `recipes`, `Recipe` модель, картка рецепту `resources/views/recipes/show.blade.php` (3.10) з наявним **disabled**-плейсхолдером «Приготовано» («Списання комори — незабаром»), `RecipeController@show` з ownership-guard.
  - Епік 1 ✅ — таблиці/моделі комори (`pantry_items`, `PantryItem`) — знадобляться для самого списання в 4.2–4.3.
- **Блокує ця задача** (наступні тікети, які чекають):
  - 4.2 Сторінка підтвердження списання (повний список інгредієнтів із редагованими кількостями) — рендериться по маршруту, який створює ця задача.
  - 4.3 `PantryDeductionService` (атомарне списання).
  - 4.4 Оновлення статусу рецепту → `cooked` + `cooked_at`.

## 4. Скоуп

**В скоупі:**
- Замінити disabled-плейсхолдер «Приготовано» в `recipes/show.blade.php` на активне посилання на маршрут підтвердження (для `completed` і не-`cooked` рецептів).
- Новий маршрут `GET /recipes/{recipe}/cook` → `recipes.cook.confirm`.
- Новий метод `RecipeController@confirmCook`: ownership-guard (403), рендерить мінімальну сторінку-підтвердження; **не** змінює комору/статус.
- Мінімальна в'юха `recipes/cook.blade.php` (заголовок рецепту + плейсхолдер «список для списання — незабаром» + кнопка «Назад до рецепту»). 4.2 наповнить її.
- Для `status = cooked` лишається наявний disabled-стан «Вже приготовано» (без посилання).
- Feature-тест на перехід + ownership + відсутність мутацій.

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Список інгредієнтів рецепту з передзаповненими/редагованими кількостями (4.2).
- Будь-яке списання з комори / `PantryDeductionService` / DB-транзакція (4.3).
- Зміна `recipes.status = 'cooked'`, `cooked_at`, redirect із повідомленням «комору оновлено» (4.4).
- AJAX/спіннери, нові міграції.

## 5. Файли (під `src/`)

- `src/routes/web.php` — додати `Route::get('/recipes/{recipe}/cook', [RecipeController::class, 'confirmCook'])->name('recipes.cook.confirm')` у `auth`-групі.
- `src/app/Http/Controllers/RecipeController.php` — додати `confirmCook(Request $request, Recipe $recipe): View` з `abort_unless` ownership-guard.
- `src/resources/views/recipes/show.blade.php` — замінити disabled-блок (рядки ~101–109) на активне `<a href="{{ route('recipes.cook.confirm', $recipe) }}">`.
- `src/resources/views/recipes/cook.blade.php` — нова мінімальна сторінка-підтвердження (stub для 4.2).
- `src/tests/Feature/RecipeCookConfirmTest.php` — новий feature-тест.

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php composer test --filter=RecipeCookConfirmTest
docker compose exec php vendor/bin/pint app/Http/Controllers/RecipeController.php
```

## 7. Acceptance criteria

- [ ] AC_1: На `completed`, не-`cooked` рецепті картка показує **активну** кнопку «Приготовано», що веде на `GET /recipes/{recipe}/cook` (`recipes.cook.confirm`); зник текст «Списання комори — незабаром».
- [ ] AC_2: Перехід за кнопкою рендерить сторінку-підтвердження і **не змінює** `pantry_items` та `recipes.status` (лишається `generated`) — перевірено тестом.
- [ ] AC_3: `confirmCook` ownership-guarded: 403 для чужого рецепту, redirect на login для гостя.
- [ ] AC_4: Для рецепту зі `status = cooked` лишається disabled-стан «Вже приготовано» без посилання на підтвердження.
- [ ] AC_5: Мінімальна в'юха `recipes/cook.blade.php` рендериться (назва рецепту + плейсхолдер + «Назад до рецепту»); повний список — явно позначено як 4.2.
- [ ] AC_6: `composer test` зелений; `pint` чистий.

## 8. Ризики / відкриті питання

- Межа 4.1↔4.2 навмисно мінімальна: ця задача дає тільки навігацію та stub-сторінку, без жодної логіки списання — щоб 4.2/4.3/4.4 не блокувалися, але й нічого не списувалося передчасно.
