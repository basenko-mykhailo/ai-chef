# Епік 2.4 — Форма додавання/редагування члена сім'ї

> Заповнено: 2026-06-10 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

Ім'я + три textarea (улюблене / неулюблене / алергії та дієти) з підказками-плейсхолдерами.

## 2. Додатковий контекст

- Форма однакова для створення і редагування — винесена в partial `family/partials/form.blade.php`, режим визначається через `$member->exists`.
- Сторінка списку `family/index.blade.php` (2.3) отримує робочі лінки: кнопку «Додати члена сім'ї» (у шапці й в empty state) та «Редагувати» на кожному рядку.
- Інтерфейс — українською (як і решта розділу «Сім'я»). `APP_LOCALE` не чіпаємо — це окрема задача.

## 3. Залежності

- **Готове до старту:**
  - 2.1 — міграція `family_members`.
  - 2.2 — модель `FamilyMember` (`$fillable`, `belongsTo(User)`, `scopeForUser`), `User::familyMembers()`.
  - 2.3 — `FamilyMemberController@index` + `family/index.blade.php`.
  - 0.3/0.4 — Breeze (auth) + базовий лейаут і компоненти (`x-input-label`, `x-text-input`, `x-input-error`, `x-primary-button`).
- **Блокує ця задача:**
  - 2.5 — Видалення (потрібні кнопки на рядках списку, той самий ownership-guard).

## 4. Скоуп

**В скоупі:**
- `FamilyMemberController`: `create`, `store`, `edit`, `update`.
- Роути під `auth`: `family.create` (GET `/family/create`), `family.store` (POST `/family`), `family.edit` (GET `/family/{familyMember}/edit`), `family.update` (PATCH `/family/{familyMember}`).
- `app/Http/Requests/FamilyMemberRequest.php`: правила (`name` required, ≤255; три текстові поля nullable, ≤1000), українські `messages()`/`attributes()`, `authorize()` — перевірка власника (для `update`) до валідації.
- Blade: `family/partials/form.blade.php`, `family/create.blade.php`, `family/edit.blade.php`; новий компонент `components/textarea.blade.php`.
- `family/index.blade.php`: CTA «Додати члена сім'ї», лінки «Редагувати», flash про успіх.
- Тести: `tests/Feature/FamilyMemberFormTest.php`.

**НЕ в скоупі:**
- Видалення з підтвердженням (2.5).
- Перемикання `APP_LOCALE` на `uk` (окрема задача).
- Сторінка одного члена сім'ї (show).

## 5. Файли (під `src/`)

- `app/Http/Controllers/FamilyMemberController.php` (+create/store/edit/update)
- `app/Http/Requests/FamilyMemberRequest.php` (новий)
- `routes/web.php` (4 нові роути)
- `resources/views/components/textarea.blade.php` (новий)
- `resources/views/family/partials/form.blade.php` (новий)
- `resources/views/family/create.blade.php` (новий)
- `resources/views/family/edit.blade.php` (новий)
- `resources/views/family/index.blade.php` (CTA + edit-лінки + flash)
- `tests/Feature/FamilyMemberFormTest.php` (новий)

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php php artisan route:list --name=family
docker compose exec php composer test -- --filter=FamilyMember
```

## 7. Acceptance criteria

- [ ] Авторизований юзер відкриває `/family/create` і бачить форму (ім'я + 3 textarea з плейсхолдерами).
- [ ] Сабміт валідної форми створює `FamilyMember`, прив'язаний до поточного юзера, і редіректить на `/family` з повідомленням про успіх.
- [ ] `name` обов'язкове; помилки показуються українською над полем; нічого не зберігається.
- [ ] `user_id` не можна підмінити через payload (створення йде через relationship).
- [ ] `/family/{member}/edit` відкривається лише власником; чужий член сім'ї → 403 (і на edit, і на update).
- [ ] Оновлення зберігає зміни і редіректить на `/family` з повідомленням.
- [ ] На `/family` є кнопка «Додати члена сім'ї» (шапка + empty state) і «Редагувати» на кожному рядку.
- [ ] `composer test` — увесь suite зелений (включно з наявним `FamilyMemberIndexTest`, чиї assert-и на empty state тепер виконуються).

## 8. Ризики / відкриті питання

- Авторизація без Policy: для одного простого правила власності використано `FamilyMemberRequest::authorize()` (write) + `abort_unless` в `edit()` (GET). Якщо в наступних епіках з'явиться більше правил доступу — варто винести в `FamilyMemberPolicy`.
- Ліміт 1000 символів на текстові поля — UI-обмеження (колонки `text`), за потреби легко змінити в `FamilyMemberRequest`.
