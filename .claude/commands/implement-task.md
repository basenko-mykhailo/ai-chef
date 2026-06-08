---
description: Implement a ticket from plan.md — fill task spec, gate via plan mode, then log progress
argument-hint: <epic>.<task>  e.g. 2.2  or  "2 2"  or  "2.2 optional title hint"
---

You are implementing a single ticket from `docs/tickets/plan.md`. Follow these steps in order. Do not skip steps. Do not batch them.

Project anchors (from `CLAUDE.md`):
- Laravel app lives in `src/`. Repo root holds Docker compose + docs only.
- All `php artisan` / `composer` / `npm` calls run **inside the `php` container**: `docker compose exec php <cmd>`.
- DB: host `db`, user/db `ai_chive`, password `secret`.
- Session/cache/queue all default to `database` driver — do not switch casually.
- Fixed product rules (pantry units enum, AND-combined family constraints, broad allergy interpretation, photo limits, 10 gen/hr rate limit, atomic pantry deduction) live in `CLAUDE.md` and `docs/tickets/prd.md` — re-read the relevant section if the task touches that area.

---

## Step 1 — Parse args

Raw arguments: `$ARGUMENTS`

Accept any of these forms and extract integer `EPIC` and `TASK`:
- `2.2`
- `2 2`
- `2.2 Some optional title hint`
- `2 2 Some optional title hint`

If args are missing or malformed, stop and ask the user to retry with `/implement-task <epic>.<task>`.

## Step 2 — Locate the ticket

Read `docs/tickets/plan.md`. Find section `## Епік {EPIC}:` and within it the bullet starting with `**{EPIC}.{TASK} `. Capture:
- **title** (the bold text after the number)
- **description** (the lines under the bullet, verbatim — preserve Ukrainian text exactly)

If not found, stop. Report what you saw, list the valid task numbers found inside that epic, and ask the user to clarify.

## Step 3 — Check progress

Read `docs/tickets/implementations_progress.md`. For this task determine:
- Current status: ✅ done / 🟡 partial / ⬜ not started
- What's already done in this epic (dependencies satisfied)
- Cross-epic dependencies (e.g. "blocked on Epic 1")

If the task is already **✅ done**, ask the user whether to: (a) re-implement, (b) extend it (treat as a new sub-task), or (c) abort. Do not silently proceed.

## Step 4 — Gather extra context from the user

Use a single `AskUserQuestion` call with `multiSelect: true` to ask which categories of extra info the user wants to add. Suggested options:
- "Nothing — plan.md description is enough"
- "Scope adjustments (add/remove something)"
- "Naming preferences (model/route/file names)"
- "Edge cases or gotchas to handle"

(The "Other" option is added automatically — that's the free-text fallback.)

For each non-trivial category selected, ask a follow-up open-ended question via plain text (just state "Paste your {category} below:" and wait for the user's next message). If user selects only "Nothing", skip to Step 5.

## Step 5 — Fill template and save

Read `docs/tickets/_task_template.md`. Substitute every `{PLACEHOLDER}`:
- `{EPIC}`, `{TASK}`, `{TITLE}` — from Step 2
- `{DATE}` — current date from session context (today's date)
- `{PLAN_DESCRIPTION}` — verbatim Ukrainian from Step 2
- `{USER_EXTRA_CONTEXT}` — from Step 4, or "—" if user provided nothing
- `{DEPENDENCIES_DONE}` / `{DOWNSTREAM_BLOCKED}` — from Step 3 and by scanning later epics in `plan.md`
- `{IN_SCOPE}` / `{OUT_OF_SCOPE}` — your best draft from plan description + extra context. Do **not** invent unrelated cleanup work.
- `{FILES_TO_TOUCH}` — concrete paths under `src/`. Read existing code first if unsure (e.g. check `src/app/Models/` before claiming a file path).
- `{ARTISAN_COMMANDS}` — concrete `docker compose exec php …` commands needed (make:model, make:migration, migrate, test). Omit empty section.
- `{AC_1}` / `{AC_2}` … — measurable acceptance criteria tied to the plan description
- `{RISKS_OR_NONE}` — anything genuinely uncertain, or write "Немає"

Save the filled file to `docs/tickets/epic-{EPIC}/task-{TASK}.md`. Create the `epic-{EPIC}` directory if it doesn't exist. **If the file already exists, ask before overwriting.**

Show the user a brief summary (max ~15 lines): the AC list and the artisan commands. Do not dump the whole spec.

## Step 6 — Plan-mode gate

Ask the user via a single yes/no `AskUserQuestion`: *"Ready to enter plan mode and start implementation?"*

If yes, call `EnterPlanMode` with a plan body that mirrors the filled spec's **Scope + Files + Commands + AC**. Let the user approve, edit, or reject through the normal plan-mode UI.

If no, stop here. The spec file is on disk for later use.

## Step 7 — Implement

After plan approval:
- Make file changes inside `src/`. Never edit at the repo root by mistake.
- Run migrations / seeders / tests via `docker compose exec php …` only.
- For tests use `composer test --filter=…` or `vendor/bin/phpunit --filter=…`.
- If a `php artisan migrate` runs, mention "DONE" output (matches existing progress-doc style).
- If something doesn't match the plan partway through (missing dep, schema surprise), pause and report — do not silently expand scope.

## Step 8 — Log progress

After implementation succeeds, update `docs/tickets/implementations_progress.md`:
1. Find the `## Epic {EPIC}` section. Flip the bullet for `{EPIC}.{TASK}` from `⬜` → `✅` (or `🟡` if partial). Match the existing entry style: one sentence + concrete file paths as evidence, e.g.
   > ✅ **{EPIC}.{TASK} {TITLE}** — `src/app/Models/Foo.php` adds the model with `forUser()` scope; `User` gets `hasMany(FamilyMember::class)`. `composer test --filter=FamilyMemberTest` passes.
2. Update the summary table at the top if the epic's overall status changed (e.g. last 🟡 task done → ✅).
3. Update the "Recommended next step" paragraph at the bottom if appropriate.
4. Show the user the diff for `implementations_progress.md`.

Do **not** create a git commit unless the user explicitly asks.