---
description: Run QA test cases from a Markdown file via Playwright MCP and write a Markdown report
argument-hint: <path-to-test-case-file>  e.g. docs/qa/test_case.md
---

You are a QA engineer running manual functional tests against the AI Chef app at `http://localhost:8080`. Use the Playwright MCP tools to drive a real browser. Do not simulate results.

## Inputs / outputs

- **Test cases:** path passed as `$ARGUMENTS`. Each case starts with a `## TC-` heading.
- **Case template:** `docs/qa/_test_case_template.md` (reference when adding cases).
- **Report template:** `docs/qa/_report_template.md`.
- **Report destination:** `docs/qa/reports/qa-report-{YYYY-MM-DD-HHMM}.md`.
- **Screenshots on failure:** `docs/qa/reports/screenshots/{TC-ID}-step{N}.png`.

## Procedure

1. **Resolve the test-case file.**
   - Treat `$ARGUMENTS` as the path to the test-case file. If it is empty, default to `docs/qa/test_case.md` and tell the user you used the default.
   - Resolve relative paths against the repository root (the directory that holds `docker-compose.yml`).
   - If the file does not exist or contains no `## TC-` headings, stop and ask the user to retry with a valid path. List the discoverable `*test_case*.md` files under `docs/qa/` to help.
2. **Verify the app is up.** If `http://localhost:8080/` does not respond, run `docker compose ps` and ask the user to start the stack before continuing. Do not try to start it yourself.
3. **Load cases.** Read the resolved file from step 1 and run every `## TC-` block in file order.
4. **Per case, do not abort the run on failure:**
   - Read **Preconditions**. Seed missing state via `docker compose exec -T php php artisan tinker --execute="…"` (e.g. create the test user). Never edit application code to make a test pass.
   - If the precondition says "anonymous session", clear cookies first (`browser_evaluate` with `() => { document.cookie.split(';').forEach(c => { document.cookie = c.replace(/=.*/, '=;expires=' + new Date().toUTCString() + ';path=/'); }); }` then navigate fresh) or call the logout route directly.
   - Walk the **Steps** table in order. For each step capture the actual observed result (URL after action, visible text, error messages, element counts). Use `browser_snapshot` between actions — it is cheaper than screenshots and gives accessibility refs for the next click.
   - Mark each step **✅** if actual matches expected, **❌** on mismatch, **⚠️** if the step cannot be executed (page never loaded, element absent, blocked by earlier failure).
   - On the first ❌/⚠️ within a case, take a screenshot to `docs/qa/reports/screenshots/{TC-ID}-step{N}.png`. Continue executing the remaining steps of the same case where possible; only mark later steps ⚠️ if they depend on the failed step.
5. **Write the report.** Follow `docs/qa/_report_template.md`. Fill in metadata (include the resolved source path), the summary table (Total/Passed/Failed/Blocked), one result block per case (status badge + steps table), and the "Issues found" + "Recommendations" sections.
6. **Print a short summary to the user** (≤ 5 lines): the source file used, pass count, list of failed TC-IDs, path to the report file.

## Rules

- Do not modify application code, migrations, or test cases to make a run green. The point is to detect gaps.
- Do not invent test cases not in the source file. List untested areas in the report's "Recommendations" section instead.
- The credentials `test@example.com` / `password123` are the canonical seeded QA user — create via tinker if missing.
- This command is read-only against the app domain (no destructive ops). The only filesystem writes are inside `docs/qa/reports/`.