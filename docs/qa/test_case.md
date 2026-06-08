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
| 2 | Inspect the primary nav | Four links visible with these labels and hrefs: `Комора` → `/pantry`, `Сім'я` → `/family`, `Рецепти` → `/recipes`, `Історія` → `/history`. |
| 3 | Inspect the AI safety banner | Note-region contains the strong text `AI може помилитися` and the trailing copy about verifying allergens. |
| 4 | Inspect the user menu | A button labeled `Test User` is present in the top-right corner. |
| 5 | Inspect the footer | Footer contains `© 2026 Laravel` and `Зроблено для хакатону · PHP · Laravel · Claude API`. |

### Notes
- The nav targets (`/pantry` etc.) are not asserted to render their own content yet — that's covered by TC-006 and future cases.

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
| 3 | Navigate back, click `Рецепти` | URL is `/recipes`; response is HTTP 200. |
| 4 | Navigate back, click `Історія` | URL is `/history`; response is HTTP 200. |

### Notes
- This is a structural smoke test — it does **not** assert the page content of each section, only that the route resolves. Add per-section cases as features ship.