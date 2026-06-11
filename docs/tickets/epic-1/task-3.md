# Епік 1.3 — Міграція таблиці `pantry_items`

> Заповнено: 2026-06-11 · Джерело: `docs/tickets/plan.md`

## Опис
Позиції комори користувача: `id`, `user_id` (FK), `ingredient_id` (FK), `quantity` (decimal 10,3), `unit` (enum), timestamps.

## Скоуп
- `database/migrations/2026_06_11_120100_create_pantry_items_table.php`.
- `user_id`/`ingredient_id` — `constrained()->cascadeOnDelete()`.
- `unit` — enum `g/kg/ml/l/pcs/tbsp/tsp/cup` (UI показує укр. підписи через `App\Enums\Unit`).

## Залежності
- 1.1 (`ingredients`), 0.3 (`users`). Блокує: 1.4 (модель), 1.5–1.9, Епік 4.

## Acceptance
- [ ] `php artisan migrate` створює `pantry_items` з FK і enum `unit`.
- [ ] `composer test` зелений.
