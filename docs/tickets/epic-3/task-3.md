# Епік 3.3 — Service `ClaudeService`

> Заповнено: 2026-06-10 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

Обгортка над Anthropic API: метод `generateText(messages, system)` і `generateFromImage(images, prompt)`. Обробка retry, timeouts, error logging.

## 2. Додатковий контекст

Від користувача: **використовувати офіційний SDK** (`anthropic-ai/sdk` через composer), решта — дефолти. Дефолти, зафіксовані явно:

- Клас: `app/Services/ClaudeService.php` (нова директорія `app/Services/`).
- Сигнатури:
  - `generateText(array $messages, ?string $system = null, ?string $model = null): string` — повертає текст відповіді (конкатенація text-блоків).
  - `generateFromImage(array $images, string $prompt, ?string $model = null): string` — `$images` = масив `['data' => base64, 'media_type' => 'image/jpeg|png|webp']`.
- Модель за замовчуванням — `config('services.anthropic.default_model')` (claude-haiku-4-5); `$model`-параметр дозволяє передати quality-модель (claude-sonnet-4-6) у майбутніх тікетах. Жодних прямих `env()`.
- Retry: обмежені повтори з backoff на 429 / 529 / 5xx; 4xx (крім 429) — без повтору, одразу виняток. Якщо SDK має вбудований retry/timeout-конфіг — використовуємо його, інакше цикл у сервісі (перевірити vendor-код SDK при імплементації).
- Error logging: `Log::error()`/`warning()` з контекстом (status, error type, model) — без витоку API-ключа.
- Реєстрація singleton у `AppServiceProvider` (ключ із конфігу інжектиться один раз).

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - 0.5 ✅ — `config/services.php` секція `anthropic` (`api_key`, `default_model`, `quality_model`); `ANTHROPIC_API_KEY` заповнений і перевірений живим викликом (HTTP 200) цієї сесії.
  - Composer працює в контейнері `php`.
- **Блокує ця задача** (наступні тікети, які чекають):
  - 3.4 — `RecipePromptBuilder` (його вихід піде в `generateText`).
  - 3.6 — парсер відповіді (retry на невалідний JSON поверх сервісу).
  - 3.8 — `RecipeGenerationJob` викликає сервіс.
  - 3.11 — обробка помилок генерації спирається на винятки сервісу.
  - 6.3 — `PhotoRecognitionService` використовує `generateFromImage`.

## 4. Скоуп

**В скоупі:**
- `composer require anthropic-ai/sdk` (в контейнері).
- `app/Services/ClaudeService.php`: конструктор приймає SDK-клієнт + дефолтну модель; обидва методи з плану; retry/timeout/error logging як у §2.
- Реєстрація в `AppServiceProvider::register()` — singleton, клієнт створюється з `config('services.anthropic.api_key')`.
- Юніт-тести (`tests/Unit/ClaudeServiceTest.php`): інстанціювання через контейнер, побудова повідомлень/блоків, retry-поведінка — наскільки дозволяє mockability SDK-клієнта (перевірити, чи класи не final / чи є інтерфейси).
- Ручний smoke-тест через tinker з реальним ключем (одноразовий мінімальний виклик).

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Промпти рецептів (3.4), JSON-схема (3.5), парсинг/валідація відповіді (3.6).
- Queue Job (3.8), кеш (3.9), rate limiting (3.12).
- Streaming, structured outputs, tool use — MVP обходиться простими text-викликами.

## 5. Файли (під `src/`)

- `src/app/Services/ClaudeService.php` (новий)
- `src/app/Providers/AppServiceProvider.php` (реєстрація singleton)
- `src/composer.json`, `src/composer.lock` (нова залежність)
- `src/tests/Unit/ClaudeServiceTest.php` (новий)

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php composer require anthropic-ai/sdk
docker compose exec php php artisan make:test ClaudeServiceTest --unit
docker compose exec php composer test -- --filter=ClaudeServiceTest
docker compose exec php composer test
# ручний smoke (один мінімальний виклик, ~$0.001):
docker compose exec php php artisan tinker --execute="dump(app(\App\Services\ClaudeService::class)->generateText([['role' => 'user', 'content' => 'Скажи одне слово: працює']]));"
```

## 7. Acceptance criteria

- [ ] `anthropic-ai/sdk` у `composer.json` require; `composer install` чистий.
- [ ] `ClaudeService::generateText(array $messages, ?string $system = null, ?string $model = null): string` працює; system передається як параметр API, модель за замовчуванням — з конфігу.
- [ ] `ClaudeService::generateFromImage(array $images, string $prompt, ?string $model = null): string` будує мультимодальні content-блоки (base64 image source + text).
- [ ] Retry: 429/529/5xx ретраяться обмежено з backoff; інші 4xx кидають виняток одразу. Помилки логуються з контекстом, без API-ключа в лозі.
- [ ] Сервіс зареєстрований singleton-ом; `app(ClaudeService::class)` віддає робочий інстанс (читає `config('services.anthropic.*')`, не `env()`).
- [ ] `composer test` — повний suite зелений; нові юніт-тести покривають те, що реально мокається.
- [ ] Ручний tinker-smoke повертає непорожній рядок від living API.

## 8. Ризики / відкриті питання

- Retry/timeout-опції PHP SDK не задокументовані в довідці — під час імплементації перевірити vendor-код (Stainless-генерований клієнт зазвичай має `maxRetries`/`timeout`); якщо є — віддати retry SDK і лишити в сервісі тільки логування.
- Mockability: якщо SDK-класи final без інтерфейсів — юніт-тести зведуться до wiring/конструювання, а happy-path покриє ручний smoke. Зафіксувати фактичний стан у progress-нотатці.
- Casing ключів image-блоку в PHP SDK (`media_type` vs `mediaType`) — перевірити по vendor-типах перед написанням `generateFromImage`.
