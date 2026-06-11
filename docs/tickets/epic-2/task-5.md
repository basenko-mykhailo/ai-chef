# Епік 2.5 — Видалення члена сім'ї

> Заповнено: 2026-06-11 · Джерело: `docs/tickets/plan.md`

## Опис
Видалення члена сім'ї з підтвердженням.

## Скоуп
- `FamilyMemberController@destroy` + роут `DELETE /family/{familyMember}` (`family.destroy`) під `auth`.
- Кнопка «Видалити» на кожному рядку `family/index.blade.php` із JS-підтвердженням (`confirm`).
- Перевірка власника (`abort_unless` → 403), flash `family-member-deleted`.
- Видалення йде через каскад FK (`user_id` cascadeOnDelete з 2.1) — рядок просто `delete()`.

## Залежності
- 2.3 (список), 2.4 (контролер/в'юха).

## Acceptance
- [ ] Власник видаляє свого члена сім'ї → редірект на `/family` з повідомленням, рядок зник.
- [ ] Чужого члена сім'ї видалити не можна → 403, рядок лишається.
- [ ] Гість → редірект на `/login`.
- [ ] Тест `FamilyMemberDeleteTest` зелений.
