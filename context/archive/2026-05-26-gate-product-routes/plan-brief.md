# Pre-flight CI verification (R-001) — Plan Brief

> Full plan: `context/changes/gate-product-routes/plan.md`

## What & Why

All pre-flight Foundation items from roadmap R-001 were already applied before this session. The goal is to run the CI verification gates, confirm they pass, and formally close R-001 so R-002 (shared widget config layer) can begin. No code changes are needed — this is a confirm-and-archive pass.

## Starting Point

The health-check identified five pre-flight issues: a PHPCS short-ternary error in `SyncRunner.php`, a missing integration test skeleton, array-alignment warnings in `WpBakeryIntegration.php`, a missing `.editorconfig`, and no `composer audit` step in CI. All five were resolved before this session.

## Desired End State

Three gate commands (`lint`, `analyse`, `test:unit`) pass locally with zero errors. The `audit` gate is verified structurally — ci.yml confirmed to contain `composer audit`. R-001 is archived; R-002 becomes the next active change.

## Key Decisions Made

| Decision | Choice | Why (1 sentence) | Source |
|---|---|---|---|
| WpWriterTest scope | Placeholder sufficient | Real CPT upsert tests belong in R-002 once the widget config layer exists | Plan |
| PHPStan in success criteria | Yes — include | ci.yml checks it on every push; success criteria should match exactly | Plan |
| Nice-to-haves (.editorconfig, audit) | Already done | Both were applied in the same prior session that fixed F-1/F-2/F-3 | Plan |
| Closure artifact | `/10x-archive` after gates pass | Clean hand-off; R-001 status moves to done in roadmap | Plan |

## Scope

**In scope:** Run the four gate commands; confirm integration skeleton and ci.yml structure; archive R-001.

**Out of scope:** Writing new code, expanding integration tests, CI workflow changes, any R-002 work.

## Architecture / Approach

Pure verification pass. Four commands + two file checks. If all pass: archive and proceed to R-002.

## Phases at a Glance

| Phase | What it delivers | Key risk |
|---|---|---|
| 1. Run CI gates | Confirmed passing state documented in Progress | Any gate failing unexpectedly — stop and investigate before archiving |

**Prerequisites:** Docker Desktop running (for `npm run lint` / `npm run analyse` / `npm run test:unit` which use Docker-wrapped PHP).
**Estimated effort:** ~10 minutes.

## Open Risks & Assumptions

- If a gate fails unexpectedly, it means something regressed since the health-check was written — investigate before archiving.
- Integration suite (`npm run test:integration`) is NOT run in this plan; it requires `wp-env` and the placeholder only asserts `assertTrue(true)`.

## Success Criteria (Summary)

- `npm run lint`, `npm run analyse`, `npm run test:unit` all exit 0
- `tests/Integration/Sync/WpWriterTest.php` confirmed present; ci.yml structurally confirmed to contain `composer audit`
- R-001 archived; R-002 ready to plan
