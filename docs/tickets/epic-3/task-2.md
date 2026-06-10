# Епік 3.2 — Міграція таблиці `recipe_cache`

> Заповнено: 2026-06-10 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

Поля: `cache_key` (string, indexed, primary), `response_json`, `model_used`, `created_at`. TTL контролюємо через created_at + cleanup-команду.

## 2. Додатковий контекст

Користувач обрав категорії "scope / naming / edge cases", але конкретики не надав — застосовано дефолти, зафіксовані тут явно:

- **Scope:** лише міграція. Cleanup-команда для TTL — НЕ в цьому тікеті (з'явиться разом з логікою кешу 3.9 або в поліровці), бо тікет називається "Міграція таблиці".
- **Naming:** точно за планом — таблиця `recipe_cache` (однина!), колонки `cache_key`, `response_json`, `model_used`, `created_at`.
- **Edge cases:**
  - `cache_key` — `string` (255, дефолт) як PK; алгоритм хешу визначить 3.9, тому довжину не обрізаємо до 64.
  - `created_at` — `timestamp()->useCurrent()` + окремий index: cleanup-команда робитиме `DELETE WHERE created_at < ?`.
  - Без `updated_at` — кеш-рядки insert-only, план явно перелічує лише `created_at`.
  - Майбутній Eloquent-модель (3.9) має задати `protected $table = 'recipe_cache'` (Laravel за конвенцією чекав би `recipe_caches`) і `UPDATED_AT = null`.

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - 0.2 — Docker compose + PostgreSQL, міграції працюють.
  - Таблиця автономна: жодних FK (cache_key — хеш, response_json — сирий JSON відповіді).
  - 3.1 ✅ — `recipes` міграція (сусідній тікет, спільного нічого, але Епік 3 вже розпочато).
- **Блокує ця задача** (наступні тікети, які чекають):
  - 3.9 — Логіка кешу: lookup перед API-викликом, запис після успішної генерації.

## 4. Скоуп

**В скоупі:**
- Нова міграція `create_recipe_cache_table`:
  - `cache_key` — `string('cache_key')->primary()`.
  - `response_json` — `json`, NOT NULL.
  - `model_used` — `string` (зберігає, якою моделлю згенеровано: haiku/sonnet).
  - `created_at` — `timestamp()->useCurrent()->index()` (для TTL-cleanup `WHERE created_at < ?`).
- `down()` — `dropIfExists('recipe_cache')`.
- Застосувати міграцію в dev-БД.

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Eloquent-модель `RecipeCache` і логіка кеш-ключа (3.9).
- Cleanup artisan-команда для TTL (разом з 3.9 або поліровкою).
- `ClaudeService` (3.3) та решта Епіку 3.

## 5. Файли (під `src/`)

- `src/database/migrations/2026_06_10_XXXXXX_create_recipe_cache_table.php` (новий, через `make:migration`)

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php php artisan make:migration create_recipe_cache_table
docker compose exec php php artisan migrate
docker compose exec php php artisan migrate:rollback --step=1   # перевірка down()
docker compose exec php php artisan migrate                      # повторне застосування
docker compose exec php composer test                            # повний suite лишається зеленим
```

## 7. Acceptance criteria

- [ ] Таблиця `recipe_cache` (саме однина) існує з: `cache_key` string PK, `response_json` json NOT NULL, `model_used` string, `created_at` timestamp з default `now()` та індексом.
- [ ] Жодної колонки `id` / `updated_at` — PK саме `cache_key`.
- [ ] `docker compose exec php php artisan migrate` → DONE без помилок (PostgreSQL).
- [ ] `php artisan migrate:rollback --step=1` чисто видаляє таблицю, повторний `migrate` знову проходить.
- [ ] `composer test` — повний suite зелений (sqlite `:memory:` будує схему).

## 8. Ризики / відкриті питання

- Назва таблиці `recipe_cache` не відповідає Laravel-конвенції множини — майбутня модель у 3.9 мусить явно задати `$table`. Зафіксовано в контексті вище.
- `useCurrent()` дає default на рівні БД; якщо 3.9 писатиме через Eloquent з вимкненими timestamps, created_at заповниться БД автоматично — ок для обох шляхів (Query Builder / Eloquent).
