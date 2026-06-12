# AI Chef — QA test cases

Source of truth for manual smoke + regression tests. Executed by the `qa-run` slash command.

Conventions:
- TC-IDs are unique and zero-padded (`TC-001`, `TC-002`, …). Do not renumber after a case is added.
- Use the canonical QA user `test@example.com` / `password123` (created on demand by `qa-run`).
- Steps reference URLs relative to `http://localhost:8080`.
- For every case, "Expected" must be observable (text/URL/element), not behavioral.

---

## TC-001: Anonymous home page renders core navigation and disclaimer

- **Feature:** Home / Layout
- **Priority:** High
- **Type:** Smoke
- **Preconditions:**
  - Anonymous session (no auth cookies).

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | Navigate to `/` | Page loads with HTTP 200; title contains `Laravel`. |
| 2 | Inspect the main region | Heading `Laravel` is visible; tagline `Рецепти з вашої комори…` is present. |
| 3 | Inspect the action buttons | Two links visible: `Увійти` (→ `/login`) and `Зареєструватися` (→ `/register`). |
| 4 | Inspect the footer | Disclaimer text contains `AI може помилятися` and mentions verifying allergens. |

### Notes
- PRD requires the AI safety disclaimer to be visible in the global layout — see `docs/tickets/prd.md`.

---

## TC-002: Login with valid credentials redirects to dashboard

- **Feature:** Authentication
- **Priority:** High
- **Type:** Smoke
- **Preconditions:**
  - User `test@example.com` / `password123` exists.
  - Anonymous session.

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | Navigate to `/login` | Login form with `Email` and `Password` textboxes and a `Log in` button is shown. |
| 2 | Fill Email = `test@example.com`, Password = `password123` | Both fields hold the entered values. |
| 3 | Click `Log in` | Browser is redirected to `/dashboard`. |
| 4 | Inspect the dashboard nav | Logged-in user's name (`Test User`) appears in the top-right menu button. |

### Postconditions
- Authenticated session active.

---

## TC-003: Login with invalid credentials shows error and stays on /login

- **Feature:** Authentication
- **Priority:** High
- **Type:** Negative
- **Preconditions:**
  - Anonymous session.
  - User `test@example.com` exists (so the email is real but the password is wrong).

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | Navigate to `/login` | Login form is shown. |
| 2 | Fill Email = `test@example.com`, Password = `wrong-password-xyz` | Fields hold the entered values. |
| 3 | Click `Log in` | URL stays on `/login`; an error message `These credentials do not match our records.` is shown next to the email field. |
| 4 | Inspect the page | The password field has been cleared (Laravel default). |

---

## TC-004: Authenticated dashboard exposes core navigation, user menu, and disclaimer

- **Feature:** Dashboard / Layout
- **Priority:** High
- **Type:** Smoke
- **Preconditions:**
  - User `test@example.com` is logged in (precede with TC-002 if running in isolation).

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | Navigate to `/dashboard` | Page loads with HTTP 200; URL is `/dashboard`. |
| 2 | Inspect the primary nav | Four links visible with these labels and hrefs: `Комора` → `/pantry`, `Сім'я` → `/family`, `Рецепти` → `/recipes/create` (repointed to the generation page in ticket 3.7), `Історія` → `/history`. |
| 3 | Inspect the AI safety banner | Note-region contains the strong text `AI може помилитися` and the trailing copy about verifying allergens. |
| 4 | Inspect the user menu | A button labeled `Test User` is present in the top-right corner. |
| 5 | Inspect the footer | Footer contains `© 2026 Laravel` and `Зроблено для хакатону · PHP · Laravel · Claude API`. |

### Notes
- The nav targets (`/pantry` etc.) are not asserted to render their own content yet — that's covered by TC-006 and future cases.
- Step 2 expectation updated in ticket 3.7: the `Рецепти` nav link was repointed from `/recipes` (placeholder) to `/recipes/create` (generation page) — mirrors the TC-006 step-3 change.

---

## TC-005: Protected route redirects anonymous user to /login

- **Feature:** Authentication / Routing
- **Priority:** High
- **Type:** Negative / Regression
- **Preconditions:**
  - Anonymous session (no auth cookies).

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | Clear cookies, then navigate to `/dashboard` | Browser is redirected to `/login`. |
| 2 | Inspect the page | Login form is shown (no dashboard content leaked). |

### Notes
- Confirms the `auth` middleware guards the dashboard.

---

## TC-006: Authenticated nav links resolve without 500 errors

- **Feature:** Routing
- **Priority:** Medium
- **Type:** Smoke
- **Preconditions:**
  - User `test@example.com` is logged in.

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | From dashboard, click `Комора` | URL is `/pantry`; response is HTTP 200 (no Laravel exception page). |
| 2 | Navigate back, click `Сім'я` | URL is `/family`; response is HTTP 200. |
| 3 | Navigate back, click `Рецепти` | URL is `/recipes/create` (nav now opens the recipe-generation page — ticket 3.7); response is HTTP 200. |
| 4 | Navigate back, click `Історія` | URL is `/history`; response is HTTP 200. |

### Notes
- This is a structural smoke test — it does **not** assert the page content of each section, only that the route resolves. Add per-section cases as features ship.
- Step 3 expectation changed in ticket 3.7: the `Рецепти` nav link was repointed from `/recipes` (placeholder) to `/recipes/create` (generation page). The `/recipes` placeholder route still exists but is no longer linked from the nav.

---

## TC-007: Recipe generation page renders pantry, family checkboxes, and generate button

- **Feature:** Recipes / Generation page (ticket 3.7)
- **Priority:** High
- **Type:** Smoke
- **Preconditions:**
  - User `test@example.com` / `password123` is logged in.
  - The seeded state is present (`php artisan migrate:fresh --seed` gives this user ~6 pantry items and 5 family members). If absent, seed via tinker before the case.

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | Navigate to `/recipes/create` | Page loads with HTTP 200; URL is `/recipes/create`. |
| 2 | Inspect the page header | Heading `Згенерувати рецепт` is visible with the subtitle about AI picking a recipe from your pantry. |
| 3 | Inspect the `Для кого готуємо` section | The section is present and lists the user's family members, each with a checkbox. |
| 4 | Inspect the `Ваша комора` section | Pantry items are listed, each showing a name and a quantity with a Ukrainian unit label (e.g. `г`, `кг`, `шт`, `мл`). |
| 5 | Inspect the submit button | A button labeled `Згенерувати рецепт` is visible and enabled (clickable). |
| 6 | Inspect the hint under the button | Text `Можна згенерувати до 10 рецептів на годину.` is present. |

### Notes
- The "10 на годину" copy is a static hint only — the actual throttle middleware is ticket 3.12.

---

## TC-008: Family-member checkboxes are all checked by default

- **Feature:** Recipes / Generation page (ticket 3.7)
- **Priority:** High
- **Type:** Functional / UI
- **Preconditions:**
  - User `test@example.com` / `password123` is logged in and has at least one family member (seeded state).

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | Navigate to `/recipes/create` | Generation page loads; `Для кого готуємо` lists the family-member checkboxes (`name="members[]"`). |
| 2 | Inspect every family-member checkbox | Each checkbox is **checked** by default. |
| 3 | Click the first family-member checkbox to uncheck it | The checkbox toggles to unchecked; the others remain checked. |

### Notes
- Default-all-checked is a fixed product rule from `docs/tickets/plan.md` (3.7).

---

## TC-009: Empty pantry shows guidance and disables the generate button

- **Feature:** Recipes / Generation page (ticket 3.7)
- **Priority:** High
- **Type:** Negative / Edge case
- **Preconditions:**
  - A logged-in user whose pantry is **empty**.
  - Seed via tinker if needed, e.g. create `emptypantry@example.com` / `password123` with zero pantry items (family members optional), then log in as that user. Do not delete the seeded `test@example.com` data.

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | Navigate to `/recipes/create` | Page loads with HTTP 200. |
| 2 | Inspect the `Ваша комора` section | Empty-state notice `Комора порожня.` is shown with helper text about adding products. |
| 3 | Inspect the empty-state link | A `+ Додати продукт` link points to `/pantry/create`. |
| 4 | Inspect the submit button | The `Згенерувати рецепт` button is **disabled** (not clickable; rendered in a muted/`cursor-not-allowed` style). |

### Notes
- The button is disabled client-side only in 3.7; the server-side guard arrives with the real endpoint in 3.8.

---

## TC-010: No family members renders the "для себе" hint with an active button

- **Feature:** Recipes / Generation page (ticket 3.7)
- **Priority:** Medium
- **Type:** Edge case
- **Preconditions:**
  - A logged-in user with **at least one pantry item** but **zero family members**.
  - Seed via tinker if needed, e.g. `solocook@example.com` / `password123` with one pantry item and no family members.

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | Navigate to `/recipes/create` | Page loads with HTTP 200. |
| 2 | Inspect the `Для кого готуємо` section | No checkboxes are shown; instead a hint with the text `без додаткових обмежень` (recipe generated for the user) is displayed. |
| 3 | Inspect the hint link | A link to add family members points to `/family/create`. |
| 4 | Inspect the submit button | The `Згенерувати рецепт` button is **enabled** (pantry is non-empty, so generation is allowed). |

---

## TC-011: Generate button is a stub — flashes "coming soon" and creates no recipe

- **Feature:** Recipes / Generation page (ticket 3.7)
- **Priority:** High
- **Type:** Functional / Regression
- **Preconditions:**
  - User `test@example.com` / `password123` is logged in with at least one pantry item (seeded state).

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | Navigate to `/recipes/create` | Generation page loads; the `Згенерувати рецепт` button is enabled. |
| 2 | Click `Згенерувати рецепт` | Form submits (POST `/recipes/generate`). |
| 3 | Inspect the resulting page | Browser lands back on `/recipes/create` (redirect-back). |
| 4 | Inspect the flash banner | A banner with text `Генерація рецептів буде доступна незабаром.` is visible. |
| 5 | Inspect the page for a recipe result | No recipe card / recipe detail is shown — the page is still the generation form. |

### Notes
- This is the intentional 3.7 stub. The real Claude call + `RecipeGenerationJob` lands in ticket 3.8; DB-level "no recipe row created" is asserted by the feature test `RecipeGenerationPageTest`, not by this browser case.

---

## TC-012: «Рецепти» nav link opens the generation page

- **Feature:** Recipes / Navigation (ticket 3.7)
- **Priority:** Medium
- **Type:** Smoke / Regression
- **Preconditions:**
  - User `test@example.com` / `password123` is logged in.

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | Navigate to `/dashboard` | Dashboard loads; the top nav shows a `Рецепти` link. |
| 2 | Click the `Рецепти` nav link | URL is `/recipes/create`; response is HTTP 200. |
| 3 | Inspect the page | Heading `Згенерувати рецепт` is visible. |

---

## TC-013: Generation page requires authentication

- **Feature:** Recipes / Routing (ticket 3.7)
- **Priority:** High
- **Type:** Negative / Regression
- **Preconditions:**
  - Anonymous session (no auth cookies).

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | Clear cookies, then navigate to `/recipes/create` | Browser is redirected to `/login`. |
| 2 | Inspect the page | Login form is shown (no generation-page content leaked). |

### Notes
- Confirms the `auth` middleware guards `recipes.create`.