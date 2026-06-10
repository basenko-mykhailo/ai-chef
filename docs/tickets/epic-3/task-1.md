# Епік 3.1 — Міграція таблиці `recipes`

> Заповнено: 2026-06-10 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

Поля: `id`, `user_id`, `name`, `ingredients_json`, `steps_json`, `kbju_json`, `pantry_snapshot_json` (що було в коморі при генерації), `selected_family_members_json`, `status` (generated/cooked), `is_favorite`, `cooked_at`, `created_at`.

## 2. Додатковий контекст

—

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - 0.2 — Docker compose + PostgreSQL, `php artisan migrate` працює з контейнерної БД.
  - 0.3 — таблиця `users` існує (ціль для FK `user_id`).
  - Жодних FK на таблиці Епіків 1–2 не потрібно: комора і обрані члени сім'ї зберігаються як JSON-snapshot.
- **Блокує ця задача** (наступні тікети, які чекають):
  - 3.8 — `RecipeGenerationJob` пише згенерований рецепт у `recipes`.
  - 3.10 — картка рецепту читає рядок з `recipes`.
  - 4.4 — оновлення `status='cooked'` + `cooked_at`.
  - 5.1–5.4 — історія, детальна сторінка, обране — все читає/оновлює `recipes`.

## 4. Скоуп

**В скоупі:**
- Нова міграція `create_recipes_table` зі схемою:
  - `id` — `$table->id()`.
  - `user_id` — `foreignId('user_id')->constrained()->cascadeOnDelete()` (як у `family_members`).
  - `name` — `string`.
  - `ingredients_json`, `steps_json`, `kbju_json`, `pantry_snapshot_json`, `selected_family_members_json` — `json`, NOT NULL (рядок створюється лише після успішної генерації, всі поля заповнені).
  - `status` — `enum('status', ['generated', 'cooked'])->default('generated')`.
  - `is_favorite` — `boolean()->default(false)`.
  - `cooked_at` — `timestamp()->nullable()`.
  - `timestamps()` (прецедент 2.1: план перелічує лише `created_at`, але міграції проєкту використовують стандартні timestamps — `updated_at` потрібен для 4.4/5.3).
- `down()` — `dropIfExists('recipes')`.
- Застосувати міграцію в dev-БД.

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Eloquent-модель `Recipe`, фабрика, сидер (окремого тікета в плані немає — модель з'явиться разом з 3.8/3.10).
- Таблиця `recipe_cache` (3.2).
- Будь-які контролери, маршрути, в'юхи (3.7, 3.10, 5.x).

## 5. Файли (під `src/`)

- `src/database/migrations/2026_06_10_XXXXXX_create_recipes_table.php` (новий, через `make:migration`)

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php php artisan make:migration create_recipes_table
docker compose exec php php artisan migrate
docker compose exec php php artisan migrate:rollback --step=1   # перевірка down()
docker compose exec php php artisan migrate                      # повторне застосування
docker compose exec php composer test                            # повний suite лишається зеленим (sqlite :memory:)
```

## 7. Acceptance criteria

- [ ] Міграція створює таблицю `recipes` з усіма полями з plan.md: FK `user_id` з cascade-delete, 5 JSON-колонок NOT NULL, `status` enum `generated|cooked` з default `generated`, `is_favorite` boolean default `false`, `cooked_at` nullable timestamp, timestamps.
- [ ] `docker compose exec php php artisan migrate` → DONE без помилок (PostgreSQL).
- [ ] `php artisan migrate:rollback --step=1` чисто видаляє таблицю, повторний `migrate` знову проходить.
- [ ] `composer test` — повний suite зелений (схема сумісна зі sqlite `:memory:` з `phpunit.xml`).

## 8. Ризики / відкриті питання

- `enum()` на PostgreSQL Laravel реалізує як `varchar` + CHECK constraint — для додавання нового статусу в майбутньому доведеться перебудувати constraint. Для MVP (два статуси з плану) прийнятно.
- План перелічує лише `created_at` — свідомо використовуємо `timestamps()` (обидва) за прецедентом міграції 2.1; це не розширення скоупу, а консистентність з рештою схеми.