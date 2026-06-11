# Епік 1.1 — Міграція таблиці `ingredients` (довідник)

> Заповнено: 2026-06-11 · Джерело: `docs/tickets/plan.md`

## Опис
Довідник інгредієнтів: `id`, `name` (ukr), `category` (опц.), `is_custom` (false для seed), `created_by_user_id` (nullable для custom).

## Скоуп
- `database/migrations/2026_06_11_120000_create_ingredients_table.php`.
- `name` з індексом (для autocomplete у 1.6), `category` nullable, `is_custom` default false, `created_by_user_id` nullable FK → `users` (`nullOnDelete`), timestamps.

## Залежності
- 0.3 (`users`). Блокує: 1.2 (seeder), 1.4 (модель), 1.6/1.7 (autocomplete + custom).

## Acceptance
- [ ] `php artisan migrate` створює `ingredients` з усіма полями.
- [ ] FK `created_by_user_id` nullable і `nullOnDelete`.
- [ ] `composer test` зелений (sqlite будує схему).
