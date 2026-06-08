# Епік 2.2 — Eloquent-модель `FamilyMember`

> Заповнено: 2026-05-13 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

Зв'язок з User, `forUser()` scope.

## 2. Додатковий контекст

- Додати сидер з 5-ма прикладами `FamilyMember`-ів для одного користувача (тестовий юзер з `DatabaseSeeder` — `test@example.com`). Сидер має бути окремим класом і викликатись із `DatabaseSeeder::run()` після створення тестового юзера. Поля сидера — реалістичні українські приклади (улюблені/неулюблені продукти, алергії/дієти), щоб майбутні UI-сторінки (2.3–2.5) одразу мали з чим працювати.

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - 2.1 — міграція `family_members` застосована (`database/migrations/2026_05_13_165321_create_family_members_table.php`).
  - 0.3 — `User` модель і Breeze-міграції на місці.
- **Блокує ця задача** (наступні тікети, які чекають):
  - 2.3 — Сторінка списку членів сім'ї (потрібна модель + relation).
  - 2.4 — Форма додавання/редагування (потрібна модель з `$fillable`).
  - 2.5 — Видалення (потрібен `cascadeOnDelete` через relation).
  - 3.4 — `RecipePromptBuilder` приймає масив `FamilyMember`.
  - 3.9 — Кеш-ключ хеширує `selected family member ids` + їхні constraint-и.

## 4. Скоуп

**В скоупі:**
- `app/Models/FamilyMember.php`: `$fillable = ['name','favorite_products','disliked_products','allergies_and_diets']`, `belongsTo(User::class)`, scope `forUser($query, $userId)` (повертає members, що належать вказаному user_id).
- Оновлення `app/Models/User.php`: `hasMany(FamilyMember::class)` через метод `familyMembers()`.
- `database/factories/FamilyMemberFactory.php` — фабрика з реалістичними українськими прикладами.
- `database/seeders/FamilyMemberSeeder.php` — 5 членів сім'ї для тестового користувача, підключений у `DatabaseSeeder::run()`.
- Юніт-тест: `tests/Unit/FamilyMemberTest.php` — перевіряє `forUser()` scope і `User::familyMembers()` relation.

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Контролери, маршрути, blade-в'юхи (2.3–2.5).
- Валідація форм (2.4).
- Логіка delete-confirmation (2.5).

## 5. Файли (під `src/`)

- `src/app/Models/FamilyMember.php` (новий)
- `src/app/Models/User.php` (додати `familyMembers()` relation)
- `src/database/factories/FamilyMemberFactory.php` (новий)
- `src/database/seeders/FamilyMemberSeeder.php` (новий)
- `src/database/seeders/DatabaseSeeder.php` (підключити сидер)
- `src/tests/Unit/FamilyMemberTest.php` (новий)

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php php artisan make:model FamilyMember
docker compose exec php php artisan make:factory FamilyMemberFactory --model=FamilyMember
docker compose exec php php artisan make:seeder FamilyMemberSeeder
docker compose exec php php artisan make:test FamilyMemberTest --unit
docker compose exec php php artisan migrate:fresh --seed
docker compose exec php composer test -- --filter=FamilyMemberTest
```

## 7. Acceptance criteria

- [ ] `FamilyMember` модель існує з `$fillable` усіх 4 полів і `belongsTo(User::class)`.
- [ ] Scope `forUser($userId)` фільтрує по `user_id` і покритий unit-тестом.
- [ ] `User::familyMembers()` повертає `HasMany` і покритий тестом.
- [ ] `FamilyMemberFactory` створює валідного `FamilyMember` з `user_id` (через `User::factory()`).
- [ ] `FamilyMemberSeeder` створює рівно 5 членів сім'ї прив'язаних до тестового користувача (`test@example.com`).
- [ ] `php artisan migrate:fresh --seed` проходить без помилок і в БД є 5 рядків `family_members` для тестового юзера.
- [ ] `composer test --filter=FamilyMemberTest` зелений.

## 8. Ризики / відкриті питання

- Чи має `forUser()` приймати `User` модель чи `int $userId`? — За планом і консистентністю з майбутнім `PantryItem::forUser()` (Епік 1.4) використаємо `int $userId` з можливістю передати `auth()->id()`. Прийняти `User` як `union type` — overkill для MVP.
