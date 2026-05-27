<!-- PLAN-REVIEW-REPORT -->
# Plan Review: AJAX search and filter for events listing

- **Plan**: context/changes/search-filters/plan.md
- **Mode**: Deep
- **Date**: 2026-05-27
- **Verdict**: SOUND (after triage)
- **Findings**: 4 critical  1 warning  1 observation

## Verdicts

| Dimension | Verdict |
|-----------|---------|
| End-State Alignment | PASS |
| Lean Execution | PASS |
| Architectural Fitness | WARNING |
| Blind Spots | FAIL |
| Plan Completeness | FAIL |

## Grounding
9/9 paths ✓, CAMPSFLOW_PLUGIN_FILE ✓, CAMPSFLOW_VERSION ✓, MANAGED_TAXONOMIES ✓, renderEventCard ✓, renderSessionRow ✓ (src/Api/ does not exist — plan creates it); brief↔plan ✓

## Findings

### F1 — Phase 5 and 6 Progress headings don't match phase body headers

- **Severity**: ❌ CRITICAL
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Plan Completeness
- **Location**: ## Phase 5 / ## Phase 6 vs. ### Phase 5 / ### Phase 6 in Progress
- **Detail**: /10x-implement requires exact name match. Body had "(blocked — wait for R-002 impl_reviewed)" but Progress had "(blocked — R-002)" for both phases.
- **Fix**: Renamed both Progress headings to match body headers exactly.
- **Decision**: FIXED

### F2 — Phase 1 missing integration test Progress item

- **Severity**: ❌ CRITICAL
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Plan Completeness
- **Location**: Phase 1 Progress → Automated section
- **Detail**: Success Criteria listed "npm run test:integration passes" but no matching Progress item existed. Phase 3 correctly had 3.7 for the same check.
- **Fix**: Added 1.11 "Integration tests pass", renumbered manual items 1.11–1.15 → 1.12–1.16.
- **Decision**: FIXED

### F3 — min([]) throws ValueError in PHP 8 on events with no turnusy

- **Severity**: ❌ CRITICAL
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Blind Spots
- **Location**: Phase 1 — SyncRunner::saveEventMeta, cf_date_earliest spec
- **Detail**: array_filter() removes empty strings, so events without turnusy produce []. PHP 8 min([]) throws ValueError — not a warning, a fatal exception.
- **Fix**: Updated spec to `$dates = array_filter(...); $dates ? min($dates) : ''`.
- **Decision**: FIXED

### F4 — term_exists() return type not extracted in setDestinationTerms spec

- **Severity**: ❌ CRITICAL
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Blind Spots
- **Location**: Phase 1 — SyncRunner::setDestinationTerms
- **Detail**: term_exists() returns null|int|array; wp_insert_term() returns array|WP_Error. Plan said "capture $parentId" without specifying extraction. On second sync (term exists) produces type error.
- **Fix**: Added helper pattern `$termId = fn(mixed $r) => is_wp_error($r) ? 0 : (int)(is_array($r) ? $r['term_id'] : $r)` and documented usage for both parent and child term operations.
- **Decision**: FIXED

### F5 — Script enqueue location departs from codebase pattern

- **Severity**: ⚠️ WARNING
- **Impact**: 🔎 MEDIUM — real tradeoff; pause to reason through it
- **Dimension**: Architectural Fitness
- **Location**: Phase 4 — enqueue location
- **Detail**: All assets enqueued in single wp_enqueue_scripts closure in campsflow.php. Plan had enqueue inside ListingShortcode::render(), introducing a second enqueue pattern.
- **Fix A ⭐ Applied**: Moved enqueue to campsflow.php wp_enqueue_scripts hook, using CAMPSFLOW_PLUGIN_URL. Global enqueue; JS bails immediately on pages without .cf-search-form.
- **Decision**: FIXED via Fix A

### F6 — Use CAMPSFLOW_PLUGIN_URL instead of plugin_dir_url(CAMPSFLOW_PLUGIN_FILE)

- **Severity**: 💡 OBSERVATION
- **Impact**: 🏃 LOW — quick decision; fix is obvious and narrowly scoped
- **Dimension**: Plan Completeness
- **Location**: Phase 4 spec
- **Detail**: CAMPSFLOW_PLUGIN_URL already defined in campsflow.php:28. F5 fix already uses correct constant.
- **Decision**: DISMISSED (covered by F5)
