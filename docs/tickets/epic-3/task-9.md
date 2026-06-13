# Епік 3.9 — Логіка кешу

> Заповнено: 2026-06-13 · Джерело: `docs/tickets/plan.md`

## 1. Опис задачі (з plan.md)

Хеш від `(sorted pantry items, sorted selected family member ids, their constraint hashes)`. Перед API-викликом дивимось у кеш, після успішного — зберігаємо.

## 2. Додатковий контекст

Користувач обрав категорії «Scope / Naming / Edge cases», але залишив їх на мій розсуд («okay»). Зафіксовані дефолти (можна скоригувати на plan-mode gate):

- **Scope:** кеш-lookup живе в `GenerateRecipeJob` **перед** викликом Claude; парсована відповідь зберігається після успіху. Таблиця `recipe_cache` вже існує (3.2) — нової міграції не треба. **Поза скоупом:** TTL-cleanup команда (міграція згадує її, але це окремий тікет), UI-індикатор «з кешу».
- **Naming:** Eloquent-модель `App\Models\RecipeCache` (`protected $table = 'recipe_cache'`), сервіс ключа `App\Services\RecipeCacheKeyBuilder`.
- **Edge cases:** ключ не залежить від порядку (сортуємо комору й членів перед хешуванням); члени сім'ї з однаковими обмеженнями дають **той самий** ключ (бажаний dedup); порожній вибір членів → стабільний ключ лише від комори; ім'я члена **не** входить у хеш (на рецепт впливають обмеження, не ім'я).

## 3. Залежності

- **Готове до старту** (з `implementations_progress.md`):
  - 3.1 `recipes` + 3.2 `recipe_cache` міграції (таблиця кешу: `cache_key` PK, `response_json`, `model_used`, `created_at`)
  - 3.3 `ClaudeService`, 3.4 `RecipePromptBuilder`, 3.5 `RecipeSchema`, 3.6 `RecipeResponseParser`
  - 3.8 `GenerateRecipeJob` (де живуть снапшоти `pantry_snapshot_json` + `selected_family_members_json` і виклик Claude)
- **Блокує ця задача** (наступні тікети, які чекають):
  - Прямих блокувань немає. Завершує Епік 3 разом з 3.10–3.12; майбутня cleanup-команда (TTL) спиратиметься на цю таблицю/модель.

## 4. Скоуп

**В скоупі:**
- `RecipeCacheKeyBuilder::build(array $pantrySnapshot, array $memberSnapshots): string` — детермінований sha256 від нормалізованих (відсортованих) снапшотів комори й обмежень членів.
- Eloquent-модель `RecipeCache` (`$table='recipe_cache'`, рядковий PK `cache_key`, без `updated_at`, `response_json` → array cast).
- `GenerateRecipeJob`: перед викликом Claude рахуємо ключ зі снапшотів рецепту, дивимось у `recipe_cache`. **Hit** → заповнюємо рецепт із `response_json`, статус `completed`, **без** виклику `ClaudeService`. **Miss** → генеруємо як зараз, після успіху зберігаємо парсовану відповідь у кеш (`model_used` = дефолтна модель з конфіга).
- Unit-тест на key builder (детермінізм / незалежність від порядку / чутливість до змін) + feature/job-тест на hit (Claude не викликається) та miss (рядок кешу створюється).

**НЕ в скоупі** (буде в наступних тікетах / поза MVP):
- Команда очищення кешу за TTL (`created_at` + cleanup) — окремий тікет/поза 3.9.
- Будь-який UI-індикатор «відповідь з кешу».
- Зміна місця, де диспатчиться job (контролер 3.8 лишається як є).

## 5. Файли (під `src/`)

- `app/Models/RecipeCache.php` — **новий**: модель на таблицю `recipe_cache`.
- `app/Services/RecipeCacheKeyBuilder.php` — **новий**: побудова cache-ключа.
- `app/Jobs/GenerateRecipeJob.php` — **правка**: lookup перед генерацією + store після успіху (інжект `RecipeCacheKeyBuilder`).
- `tests/Unit/RecipeCacheKeyBuilderTest.php` — **новий**.
- `tests/Feature/GenerateRecipeJobTest.php` — **правка/доповнення**: кейси hit/miss (або новий `RecipeCacheTest.php`).

## 6. Команди (виконувати в контейнері `php`)

```bash
docker compose exec php composer test --filter=RecipeCache
docker compose exec php composer test
docker compose exec php vendor/bin/pint
```

## 7. Acceptance criteria

- [ ] `RecipeCacheKeyBuilder` повертає детермінований sha256: однакові снапшоти (у будь-якому порядку комори/членів) → однаковий ключ; зміна кількості/одиниці/назви продукту чи будь-якого обмеження члена → інший ключ.
- [ ] Члени сім'ї з ідентичними обмеженнями (і переставлені комора/члени) б'ються в один кеш-запис; порожній вибір членів дає стабільний ключ лише від комори.
- [ ] `RecipeCache` модель націлена на `recipe_cache` (рядковий PK `cache_key`, без `updated_at`), `response_json` каститься в array.
- [ ] `GenerateRecipeJob` при cache-hit заповнює рецепт із `response_json` і ставить `completed`, **не** викликаючи `ClaudeService`.
- [ ] При cache-miss + успішній генерації job зберігає парсовану відповідь під обчисленим ключем з `model_used`.
- [ ] `composer test` зелений (попередні + нові RecipeCache-тести); `pint` чистий.

## 8. Ризики / відкриті питання

- Снапшот членів (3.8 контролер) зберігає лише обмеження без `id`; plan каже «ids + constraint hashes». Хешування самих обмежень еквівалентне за результатом і додатково дедуплікує однакові за обмеженнями профілі — свідоме відхилення від буквального формулювання.
- `GenerateRecipeJob` не відстежує, яку саме модель повернув `RecipeResponseParser`; у `model_used` пишемо дефолтну модель з `config('services.anthropic.default_model')`.
