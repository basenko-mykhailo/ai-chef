# Епік 3.10 — Картка рецепту (UI)

> Заповнено: 2026-06-13 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

**3.10 Картка рецепту (UI)**
Відображення згенерованого: назва, опис, інгредієнти (з відміткою "є в коморі" / "треба купити"), покрокові інструкції, КБЖУ-блок, кнопки "Приготовано" і "В обране".

## 2. Додатковий контекст

Користувач делегував рішення ("okay") — фіксуємо прийняті дефолти:

- **Scope adjustments:** повноцінна картка замінює мінімальний `recipes/show.blade.php`. Кнопка **«В обране»** отримує мінімальний робочий тогл (`is_favorite` — просте перемикання boolean), щоб картка не мала «мертвих» кнопок; тікет 5.3 пізніше розширить це на AJAX-серце в історії/списках. Кнопка **«Приготовано»** рендериться, але її реальний флоу (екран підтвердження → списання комори) залишається за Епіком 4.1 — поки що placeholder/disabled з підказкою, без списання.
- **Naming:** лишаємо `recipes/show.blade.php` + `RecipeController@show`; додаємо `RecipeController@toggleFavorite` на `PATCH /recipes/{recipe}/favorite` (`recipes.favorite`).
- **Edge cases:** не-`completed` (pending/processing) → стан «ще готується»; `failed` → friendly-помилка + «Спробувати ще раз»; вже приготований (`status='cooked'`) → бейдж «Приготовано» + `cooked_at`, кнопка «Приготовано» disabled; серце відображає поточний `is_favorite`; відсутні поля КБЖУ → «—»; не-власник → 403.

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - 3.1 `recipes` міграція (`status`, `is_favorite`, `cooked_at`, 5 json-колонок) ✅
  - 3.8 `app/Models/Recipe.php` (array-касти json-колонок, `GenerationStatus` каст, `forUser()`), `RecipeController@show` + ownership-guard, мінімальний `recipes/show.blade.php`, `RecipeFactory::completed()` ✅
  - 3.8 `app/Enums/GenerationStatus.php` (pending/processing/completed/failed + `label()`) ✅
  - Brand-палітра (`brand`/`cream`/`beige`/`ink`/`muted`) у `resources/css/app.css` та `x-app-layout` ✅
- **Блокує ця задача** (наступні тікети, які чекають):
  - 4.1 «Кнопка Приготовано на картці рецепту» — замінить placeholder-кнопку на перехід до екрана підтвердження списання.
  - 5.3 «Тогл В обране + endpoint» — розширить тогл на AJAX-серце в історії/списках (база — цей `toggleFavorite`).
  - 3.11 «Обробка помилок генерації» — поглибить friendly-стан для `failed` (тут лише мінімальний показ).

## 4. Скоуп

**В скоупі:**
- Переписати `resources/views/recipes/show.blade.php` на повноцінну картку: назва, опис, кількість порцій, інгредієнти з відмітками «є в коморі»/«треба купити», нумеровані кроки, блок КБЖУ (на порцію), панель дій із кнопками «Приготовано» і «В обране».
- Стани відмальовки: `pending`/`processing` → «рецепт ще готується»; `failed` → friendly-помилка + посилання «Спробувати ще раз»; `completed` → повна картка.
- Бейдж «Приготовано» + `cooked_at` і disabled-стан кнопки «Приготовано» для рецептів зі `status='cooked'`.
- Мінімальний робочий тогл «В обране»: `RecipeController@toggleFavorite` (ownership-guard, перемикає `is_favorite`, redirect назад із flash); іконка-серце (заповнене/контур) відображає стан.
- Кнопка «Приготовано» рендериться як placeholder/disabled з підказкою (реальний флоу — 4.1).

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Екран підтвердження приготування + списання комори (Епік 4).
- AJAX-серце «В обране» на сторінках історії/списків (5.3) — тут лише тогл на самій картці.
- Повна friendly-обробка помилок генерації з retry-логікою (3.11).
- Rate limiting генерації (3.12).

## 5. Файли (під `src/`)

- `src/resources/views/recipes/show.blade.php` — переписати на повну картку (всі стани + панель дій).
- `src/app/Http/Controllers/RecipeController.php` — додати `toggleFavorite(Request, Recipe)`.
- `src/routes/web.php` — додати `PATCH /recipes/{recipe}/favorite` → `recipes.favorite` (у `auth`-групі).
- `src/tests/Feature/RecipeShowTest.php` — новий feature-тест (стани, 403, тогл обраного).

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php composer test --filter=RecipeShowTest
docker compose exec php vendor/bin/pint
```

## 7. Acceptance criteria

- [ ] GET `/recipes/{recipe}` для `completed`-рецепту показує назву, опис, порції, кожен інгредієнт із коректною відміткою «є в коморі»/«треба купити», усі кроки і 4 значення КБЖУ.
- [ ] На картці `completed`-рецепту присутні обидві кнопки — «Приготовано» і «В обране».
- [ ] `PATCH /recipes/{recipe}/favorite` перемикає `is_favorite` (false→true→false) для власника й відображається в іконці-серці; не-власник отримує 403.
- [ ] Не-`completed` рецепт (pending/processing/failed) показує відповідний стан «ще готується»/помилки замість картки; не-власник на `show` отримує 403.
- [ ] Рецепт зі `status='cooked'` показує бейдж «Приготовано» з `cooked_at`, а кнопка «Приготовано» disabled.
- [ ] `composer test --filter=RecipeShowTest` зелений; `pint` чистий.

## 8. Ризики / відкриті питання

- Межа скоупу з 4.1/5.3: «Приготовано» лишається placeholder-ом, «В обране» — мінімальний тогл на власній картці; 4.1/5.3 згодом перекриють/розширять це. Підтвердити, що такий поділ ок.
- `status` — звичайна string-колонка без enum-касту, тож детект «cooked» іде по літералу `'cooked'`.
