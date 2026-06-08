# Test case template

Copy a block below into `docs/qa/test_case.md` and edit. Keep TC-IDs unique and sequential.

The `qa-run` command parses every `## TC-` heading in `docs/qa/test_case.md` as a separate case.

---

## TC-XXX: <short imperative title — e.g. "Login with valid credentials">

- **Feature:** <area — Authentication / Home / Pantry / Family / Recipes / History / Cooked flow>
- **Priority:** High | Medium | Low
- **Type:** Smoke | Regression | Negative | UI | Localization
- **Preconditions:**
  - <e.g. user `test@example.com` / `password123` exists>
  - <e.g. anonymous session (no cookies)>
- **Test data:**
  - <key: value lines — emails, sample pantry items, etc. — optional>

### Steps

| # | Action | Expected result |
|---|--------|-----------------|
| 1 | <action — be specific: "Navigate to `/login`", "Fill Email with `test@example.com`", "Click `Log in` button"> | <expected — observable: URL, visible text, element presence> |
| 2 | … | … |

### Postconditions
- <state after the test, e.g. "session cookie cleared", optional>

### Notes
- <gotchas, link to PRD section, related ticket, optional>