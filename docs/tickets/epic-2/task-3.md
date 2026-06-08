# Епік 2.3 — Сторінка списку членів сім'ї

> Заповнено: 2026-05-13 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

Картки з ім'ям і короткими тегами обмежень. Empty state.

## 2. Додатковий контекст

- Сторінка має реально відображати `family_members`, що вже були засіяні в задачі 2.2 (5 записів для `test@example.com`: Тато, Мама, Бабуся, Син, Донька).
- Тестовий доступ для перевірки в браузері: **`test@example.com` / `password`** (з `UserFactory` → `Hash::make('password')`).

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - 2.1 міграція `family_members` (table існує, FK на `users` cascadeOnDelete)
  - 2.2 модель `FamilyMember` з `forUser()` scope + `User::familyMembers(): HasMany` + `FamilyMemberSeeder` (5 записів для `test@example.com`)
  - 0.3 Breeze (auth middleware), 0.4 базовий лейаут `<x-app-layout>` з навігацією
- **Блокує ця задача** (наступні тікети, які чекають):
  - 2.4 Форма додавання/редагування (потрібна кнопка "Додати" / лінки "Редагувати" на цій сторінці)
  - 2.5 Видалення (потрібна кнопка "Видалити" на картці)
  - 3.7 Сторінка генерації рецепту (чекбокси з членами сім'ї — буде перевикористовувати дані тієї ж моделі)

## 4. Скоуп

**В скоупі:**
- `FamilyMemberController@index` (resource-style, лише `index` поки що) під middleware `auth`.
- Заміна `Route::view('/family', 'placeholder', …)` на `Route::get('/family', [FamilyMemberController::class, 'index'])->name('family.index')`.
- Blade-view `resources/views/family/index.blade.php`, який рендерить картки членів сім'ї поточного юзера через `auth()->user()->familyMembers` (або `FamilyMember::forUser(auth()->id())->get()`).
- На картці: ім'я (h3), короткі теги-чіпи з полів `favorite_products` / `disliked_products` / `allergies_and_diets` (по 1-2 тегу на категорію, з кольоровим маркуванням; довгий текст обрізаємо).
- Empty state: іконка/заглушка + текст "Ще немає членів сім'ї" + плейсхолдер-кнопка "Додати члена сім'ї" (лінк/disabled — реальний роут створюється в 2.4).
- Кнопка "Додати члена сім'ї" в шапці сторінки (поки веде на майбутній `family.create` або плейсхолдер).
- Стилізація під поточний Tailwind/Breeze дизайн (`<x-app-layout>`, `max-w-7xl mx-auto`, картки `bg-white shadow-sm sm:rounded-lg`).

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Форма додавання/редагування (2.4)
- Видалення з підтвердженням (2.5)
- Глобальний switch `APP_LOCALE` на `uk` (окрема задача)
- Детальна сторінка одного члена сім'ї

## 5. Файли (під `src/`)

- `app/Http/Controllers/FamilyMemberController.php` — новий контролер, метод `index()`.
- `routes/web.php` — замінити `Route::view('/family', …)` на контролерний роут.
- `resources/views/family/index.blade.php` — новий Blade-template зі списком карток + empty state.
- (опційно) `resources/views/family/partials/_card.blade.php` — якщо картка стане громіздкою, винести в partial. Інакше — інлайнити.

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php php artisan make:controller FamilyMemberController
docker compose exec php php artisan route:list --name=family
docker compose exec php php artisan migrate:fresh --seed   # щоб упевнитись, що FamilyMemberSeeder заповнив 5 записів для test@example.com
docker compose exec php composer test
```

## 7. Acceptance criteria

- [ ] Авторизований юзер (`test@example.com` / `password`) на `/family` бачить 5 карток (Тато, Мама, Бабуся, Син, Донька) — кожна з іменем і видимими тегами обмежень.
- [ ] Неавторизований юзер при заході на `/family` редіректиться на `/login` (через middleware `auth`).
- [ ] Юзер без жодного `FamilyMember` бачить empty state з текстом і кнопкою-CTA "Додати члена сім'ї".
- [ ] Картки рендеряться тільки для членів сім'ї поточного юзера (через `forUser()` scope / relation), не зливаючи дані інших юзерів.
- [ ] У route list: `family.index` → `FamilyMemberController@index` (а не `Route::view`).
- [ ] `composer test` → весь suite зелений (28+ тестів, включно з існуючими FamilyMember-тестами).
- [ ] Сторінка візуально проходить швидке око: верстка не ламається на десктопі (mobile-pass — задача 7.1).

## 8. Ризики / відкриті питання

- Дизайн "коротких тегів обмежень" не специфіковано в plan.md → беремо найпростіший варіант: chip-style span'и з обрізкою тексту (`line-clamp-2`), окремий колір для алергій. Можна доточити в 7.x (полировка).