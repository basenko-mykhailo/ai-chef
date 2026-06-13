# Епік 3.11 — Обробка помилок генерації

> Заповнено: 2026-06-13 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

**3.11 Обробка помилок генерації**
Якщо API впав / модель повернула невалідний JSON / timeout — friendly error з кнопкою "Спробувати ще раз".

## 2. Додатковий контекст

Користувач обрав категорії «Корективи скоупу», «Найменування», «Edge cases», але делегував конкретику (відповідь «okay»). Рішення прийняті за best-judgment і зафіксовані тут:

- **Корективи скоупу:** замість одного загального повідомлення `GenerateRecipeJob` мапить причину збою на friendly-повідомлення трьох категорій (API/мережа недоступні · невалідний JSON від моделі · timeout). Технічні деталі (клас винятку, message) лишаються лише в `Log::error` — користувач їх не бачить.
- **Найменування:** friendly-тексти тримаємо як приватні константи в `GenerateRecipeJob` (єдине джерело правди; show-сторінка читає вже збережений `generation_error` з БД, своїх текстів не дублює). Метод збою — наявний `markFailed(?string $message = null)` (розширюємо сигнатуру). Нових роутів/файлів не вводимо.
- **Edge cases:** (а) worker убив джобу по timeout / fatal → хук `failed()` лишає timeout-повідомлення; (б) мережева помилка під час polling-у на сторінці генерації → friendly-банер + retry (вже частково в `create.blade.php`); (в) «Спробувати ще раз» = **нова** генерація (новий `Recipe`), а не перезапуск збитого джоба — це найпростіший і вже наявний флоу; failed-рядок лишається в БД (прибирання з історії — питання Епіку 5).

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - 3.6 — `RecipeResponseParser` + `InvalidRecipeResponseException` (кидається на невалідний JSON/схему після retry).
  - 3.8 — `GenerateRecipeJob` (вже має `catch (Throwable)` → `markFailed()` + хук `failed()`), `RecipeController::status()` (віддає `generation_error`), polling у `recipes/create.blade.php`.
  - 3.10 — `recipes/show.blade.php` уже має failed-стан з посиланням «Спробувати ще раз».
- **Блокує ця задача** (наступні тікети, які чекають):
  - Прямих блокувань немає. Суміжний 3.12 (rate-limit 429) перевикористає той самий friendly-банер на сторінці генерації, але реалізується окремо.

## 4. Скоуп

**В скоупі:**
- Диференційовані friendly-повідомлення в `GenerateRecipeJob`: мапа причини збою (`InvalidRecipeResponseException` → «невалідна відповідь AI»; інші `Throwable` з catch, тобто API/мережа/rate-limit/timeout SDK → «сервіс тимчасово недоступний»; хук `failed()` для worker-timeout/fatal → «генерація зайняла забагато часу»). Усі тексти українською, без технічних деталей.
- Зберігати friendly-текст у `recipes.generation_error`; `RecipeController::status()` віддає його як є (вже реалізовано) — фронт показує цей текст.
- Явна, помітна кнопка «Спробувати ще раз» у failed-банері на сторінці генерації (`create.blade.php`) — повторно сабмітить форму (нова генерація), а не лише покладається на ре-активовану основну кнопку.
- Кнопка/посилання «Спробувати ще раз» у failed-стані картки рецепту (`show.blade.php`) — веде на `recipes.create`; привести стиль до brand-кнопки.
- Тести на кожну категорію збою (API-fail, невалідний JSON, worker-timeout через хук `failed()`) — перевіряють `Failed` статус і коректний friendly `generation_error` (а не технічний message).

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Rate limiting / 429-throttle на генерацію — тікет 3.12.
- Перезапуск саме збитого джоба (retry того ж `Recipe`-рядка) — лишаємо «нову генерацію».
- Прибирання `failed`-рецептів зі сторінки історії — Епік 5.
- Помилки фото-розпізнавання — Епік 6.

## 5. Файли (під `src/`)

- `app/Jobs/GenerateRecipeJob.php` — приватні friendly-константи; `friendlyMessage(Throwable $e): string` (мапа причин); `markFailed(?string $message)`; catch і хук `failed()` передають відповідний текст.
- `resources/views/recipes/create.blade.php` — у `x-show="failed"` банер додати кнопку «Спробувати ще раз», що викликає повторний сабміт (Alpine `start`/retry); прибрати дубль-залежність лише від ре-активованої кнопки.
- `resources/views/recipes/show.blade.php` — failed-стан: оформити «Спробувати ще раз» як brand-кнопку (лишається лінк на `recipes.create`).
- `tests/Feature/GenerateRecipeJobTest.php` — підсилити наявні `test_api_failure_*` / `test_unparseable_json_*` перевіркою тексту; додати кейс на хук `failed()` (worker-timeout) з timeout-повідомленням.
- `tests/Feature/RecipeShowTest.php` (або `RecipeGenerationFlowTest.php`) — перевірити, що failed-рецепт рендерить «Спробувати ще раз» і friendly `generation_error` через `status()`.

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php composer test --filter=GenerateRecipeJobTest
docker compose exec php composer test --filter=RecipeShowTest
docker compose exec php composer test
docker compose exec php vendor/bin/pint
```

(Міграцій немає — `generation_status` і `generation_error` вже додані в 3.8.)

## 7. Acceptance criteria

- [ ] `GenerateRecipeJob` зберігає різні friendly `generation_error` залежно від причини: невалідний JSON (`InvalidRecipeResponseException`), збій API/мережі/rate-limit/timeout SDK, та worker-timeout через хук `failed()` — усі українською, без технічних деталей.
- [ ] Технічні деталі збою (клас винятку + message) логуються через `Log::error`, але **не** потрапляють у `generation_error`/UI.
- [ ] Сторінка генерації (`create.blade.php`) у стані `failed` показує friendly-повідомлення з ендпоінта статусу і явну кнопку «Спробувати ще раз», що запускає нову генерацію.
- [ ] Картка рецепту (`show.blade.php`) у стані `failed` показує `generation_error` і кнопку «Спробувати ще раз» → `recipes.create`.
- [ ] Тести покривають усі три категорії збою (assert на `GenerationStatus::Failed` + конкретний friendly текст) і рендер кнопки retry; `composer test` зелений, `pint` чистий.

## 8. Ризики / відкриті питання

- Точні класи винятків SDK Anthropic для timeout/rate-limit перевірити в коді при реалізації — мапа має покривати їх через загальний `Throwable`-кейс, тож навіть незнайомий підтип отримає friendly «сервіс недоступний» (не технічний дамп).
