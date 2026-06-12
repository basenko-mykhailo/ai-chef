# Епік 1.4 — Eloquent-моделі `Ingredient` та `PantryItem`

> Заповнено: 2026-06-11 · Джерело: `docs/tickets/plan.md`

## Опис
Зв'язки: `User hasMany PantryItems`, `PantryItem belongsTo Ingredient`. Scope `forUser()`.

## Скоуп
- `app/Models/Ingredient.php`: `$fillable`, `pantryItems()` hasMany, `creator()` belongsTo (`created_by_user_id`), `is_custom` bool cast; scopes `availableTo(int $userId)` (довідник + власні custom) і `search(string $term)` (для autocomplete 1.6).
- `app/Models/PantryItem.php`: `$fillable`, `user()`/`ingredient()` belongsTo, `forUser(int $userId)` scope; casts `quantity`=`decimal:3`, `unit`=`App\Enums\Unit`.
- `app/Enums/Unit.php`: 8 одиниць, `label()` (укр.), `values()`, `options()`.
- `User::pantryItems(): HasMany`.
- Фабрики `IngredientFactory` (+`custom()` state) і `PantryItemFactory`; `PantryItemSeeder` (стартові 6 позицій для `test@example.com`).

## Залежності
- 1.1, 1.3. Блокує: 1.5–1.9, 3.4/3.9 (RecipePromptBuilder/кеш беруть комору), Епік 4.

## Acceptance
- [ ] Зв'язки `User↔PantryItem↔Ingredient` працюють; `forUser()` фільтрує по `user_id`.
- [ ] `unit` каститься у `Unit`, `quantity` — `decimal:3`.
- [ ] `availableTo()` віддає довідник + власні custom (не чужі); `search()` шукає по підрядку.
- [ ] Тести `PantryModelTest`, `IngredientCatalogTest`, `UnitEnumTest` зелені.
