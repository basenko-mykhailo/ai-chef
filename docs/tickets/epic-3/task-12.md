# Епік 3.12 — Rate limiting на генерацію

> Заповнено: 2026-06-13 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

Laravel throttle: max 10 генерацій на годину на юзера для захисту від bug-loop і випадкового спаму.

## 2. Додатковий контекст

Рішення прийняті асистентом (користувач делегував scope / naming / edge cases):

**Скоуп:**
- Тротлимо **тільки** `POST /api/recipes/generate` (`api.recipes.generate`) — 10 запитів/годину на юзера.
- Polling-ендпоінт `GET /api/recipes/{recipe}/status` **не** тротлимо (фронт опитує його кожні 2с — ліміт зламав би polling).
- UI-підказку «Можна згенерувати до 10 рецептів на годину» в `recipes/create.blade.php` лишаємо як є (вона вже стоїть з 3.7).

**Naming:**
- Іменований rate limiter з ключем `recipe-generation`.
- Реєструємо в `AppServiceProvider::boot()` (там уже живе singleton Anthropic-клієнта і HTTPS-boot — тримаємо в одному місці).
- Застосовуємо через `->middleware('throttle:recipe-generation')` на маршруті.

**Edge cases / gotchas:**
- Ключ ліміту — `$request->user()?->id` (маршрут під `auth`, юзер завжди є; IP як захисний фолбек).
- При перевищенні — JSON `429` з дружнім українським `message`, який підхоплює наявний банер помилки з 3.11 (кнопка «Спробувати ще раз») через `start()` → `if (!res.ok)`.
- Per-user ізоляція: ліміт юзера A не впливає на юзера B.
- Тести юзають `Queue::fake()` (щоб не ганяти реальний job) і б'ють по ендпоінту 11 разів, аби впіймати 429.

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - 3.8 — ендпоінт `api.recipes.generate` + `GenerateRecipeJob` (ціль тротлингу).
  - 3.11 — банер помилки з «Спробувати ще раз» у `recipes/create.blade.php` (перевикористовуємо для 429).
  - 0.5 — конфіг Anthropic / `AppServiceProvider` (місце реєстрації лімітера).
- **Блокує ця задача** (наступні тікети, які чекають):
  - Жодного прямого блокера. Це останній тікет Епіку 3 — закриває його повністю.

## 4. Скоуп

**В скоупі:**
- Іменований rate limiter `recipe-generation` (10/год на юзера, IP-фолбек) у `AppServiceProvider::boot()`.
- Кастомна JSON-429 відповідь з українським повідомленням.
- `throttle:recipe-generation` на маршруті `POST /api/recipes/generate`.
- Feature-тест: 10 успішних запитів, 11-й → 429 з українським `message`; ізоляція між юзерами.

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Тротлинг інших ендпоінтів (status, favorite, pantry/family CRUD).
- Відображення лічильника «залишилось N спроб» в UI.
- Конфігурованість ліміту через `.env` (хардкод 10/год достатньо для MVP).
- Окрема сторінка/429-вʼю — помилку показує наявний банер.

## 5. Файли (під `src/`)

- `app/Providers/AppServiceProvider.php` — реєстрація `RateLimiter::for('recipe-generation', …)` у `boot()` з кастомною 429-відповіддю.
- `routes/web.php` — додати `->middleware('throttle:recipe-generation')` до `api.recipes.generate`.
- `tests/Feature/RecipeGenerationThrottleTest.php` — новий feature-тест (ліміт спрацьовує на 11-му, per-user ізоляція, дружнє повідомлення).

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php composer test --filter=RecipeGenerationThrottleTest
docker compose exec php composer test
docker compose exec php vendor/bin/pint
```

## 7. Acceptance criteria

- [ ] Іменований лімітер `recipe-generation` зареєстровано в `AppServiceProvider::boot()` як `Limit::perHour(10)->by(user id / IP)`.
- [ ] Маршрут `POST /api/recipes/generate` несе `throttle:recipe-generation`; `status`-ендпоінт лишається без тротлингу.
- [ ] 11-й запит за годину повертає `429` з українським JSON `message`, який показує банер помилки з 3.11.
- [ ] Ліміт рахується **на юзера** — окремий юзер не успадковує чужий лічильник.
- [ ] `composer test` зелений (новий тест + наявні 136), `pint` чистий.

## 8. Ризики / відкриті питання

- У тестах cache-драйвер `array`, тож стан лімітера тримається в межах одного тесту — для перевірки 429 треба слати запити в одному тесті (не покладатися на персистентність між тестами). `Queue::fake()` обовʼязковий, щоб 10 «успішних» запитів не плодили реальні job-и.
