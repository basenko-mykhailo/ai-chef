# Епік 3.6 — Парсер відповіді Claude + валідація схеми

> Заповнено: 2026-06-10 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

Розбирає JSON, перевіряє наявність обов'язкових полів, відкидає неповні відповіді (з retry).

## 2. Додатковий контекст

Користувач: **обсяг за plan.md без змін; назви за замовчуванням; додаткових крайніх випадків немає.** Дефолти, зафіксовані явно:

- Парсер — `app/Services/RecipeResponseParser.php`; виняток — `app/Exceptions/InvalidRecipeResponseException.php`.
- `parse(string $raw): array` — чиста логіка без залежності від `ClaudeService`: зрізає markdown-обгортку (\`\`\`json … \`\`\`), декодує JSON, валідує проти контрактних констант `RecipeSchema` (3.5), повертає нормалізований масив рецепту. Будь-яка невідповідність → `InvalidRecipeResponseException` з людиночитною причиною (для логів 3.11).
- Retry з плану ("відкидає неповні відповіді (з retry)") — метод `parseWithRetry(callable $generate, int $maxAttempts = 2): array`: викликає `$generate()` (у 3.8 це буде замикання навколо `ClaudeService::generateText`), при `InvalidRecipeResponseException` повторює до `$maxAttempts` разів, після вичерпання — рethrow останнього винятку. Так парсер тестується без API, а 3.8 отримує готовий цикл.
- Валідація: усі `REQUIRED_KEYS` верхнього рівня; `ingredients` — непорожній список, кожен елемент з `INGREDIENT_KEYS`, `quantity` числове, `in_pantry` булеве, `unit` ∈ `ALLOWED_UNITS`; `steps` — непорожній список рядків; `kbju` містить усі `KBJU_KEYS` з числовими значеннями; `servings` числове; `name`/`description` — непорожні рядки.

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - 3.5 ✅ — `RecipeSchema` з `REQUIRED_KEYS`, `INGREDIENT_KEYS`, `KBJU_KEYS`, `ALLOWED_UNITS` — контракт, підготовлений саме під цей парсер.
  - 3.3 ✅ — `ClaudeService` — джерело сирого тексту; в 3.6 не викликається (підставляється замиканням у retry-циклі).
- **Блокує ця задача** (наступні тікети, які чекають):
  - 3.8 — `RecipeGenerationJob` (склеює builder → ClaudeService → `parseWithRetry`).
  - 3.9 — кеш зберігає лише провалідований `response_json`.
  - 3.10 — картка рецепту рендерить розпарсену структуру.
  - 3.11 — обробка помилок ловить `InvalidRecipeResponseException` після вичерпання retry.

## 4. Скоуп

**В скоупі:**
- `RecipeResponseParser::parse(string $raw): array` — зрізання markdown-обгортки, `json_decode`, повна валідація за §2, нормалізований масив на виході.
- `App\Exceptions\InvalidRecipeResponseException` — з фабричними повідомленнями, що називають конкретну причину (невалідний JSON / відсутній ключ / невалідна одиниця / порожній список).
- `RecipeResponseParser::parseWithRetry(callable $generate, int $maxAttempts = 2): array` — цикл повторної генерації при невалідній відповіді.
- Юніт-тести: валідна відповідь; відповідь у markdown-обгортці; невалідний JSON; відсутні ключі (верхній рівень / інгредієнт / kbju); порожні `ingredients`/`steps`; одиниця поза enum; нечислова `quantity`; retry — успіх з другої спроби та вичерпання спроб.

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Виклик Claude, Job/endpoint, збереження в `recipes` (3.8); кеш (3.9); UI картки (3.10); friendly-екран помилки (3.11).
- Семантична перевірка (чи рецепт справді уникає алергенів) — програмно неперевірювана, відповідальність промпта (3.4).
- Конвертація/нормалізація одиниць — одиниця поза enum просто відхиляється.

## 5. Файли (під `src/`)

- `src/app/Services/RecipeResponseParser.php` (новий)
- `src/app/Exceptions/InvalidRecipeResponseException.php` (новий)
- `src/tests/Unit/RecipeResponseParserTest.php` (новий)

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php php artisan make:test RecipeResponseParserTest --unit
docker compose exec php composer test -- --filter=RecipeResponseParserTest
docker compose exec php composer test
```

## 7. Acceptance criteria

- [ ] `parse()` валідної JSON-відповіді (зокрема в \`\`\`json-обгортці) повертає масив з усіма ключами `RecipeSchema::REQUIRED_KEYS`.
- [ ] Невалідний JSON, відсутній обов'язковий ключ (верхній рівень / `ingredients[*]` / `kbju`), порожні `ingredients`/`steps`, `unit` поза `ALLOWED_UNITS`, нечислова `quantity`, небулевий `in_pantry` → `InvalidRecipeResponseException` з причиною в повідомленні.
- [ ] `parseWithRetry()` повторно викликає `$generate` при невалідній відповіді й повертає результат успішної спроби; після `$maxAttempts` невдач — кидає `InvalidRecipeResponseException`.
- [ ] Парсер не звертається до `ClaudeService`/мережі — чиста логіка, тестується без моків SDK.
- [ ] `composer test` — нові тести + повний suite зелені.

## 8. Ризики / відкриті питання

- Сигнатура `parseWithRetry(callable)` — припущення 3.6; якщо в 3.8 зручніше виявиться інша форма (наприклад, retry всередині Job), цикл легко переноситься, бо `parse()` лишається чистим.
- Кількість спроб (2) — дефолт; узгодити з таймаутом Job у 3.8 (кожна спроба — окремий виклик API до 120 с).
