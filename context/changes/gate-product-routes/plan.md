# Pre-flight CI verification (R-001) — Implementation Plan

## Overview

All pre-flight Foundation items from R-001 (SyncRunner PHPCS fix, integration test skeleton, WpBakery lint warnings, `.editorconfig`, CI security audit) were already applied before this planning session. This plan runs the verification gates, confirms they pass, and formally closes R-001 so R-002 (shared widget config layer) can begin.

No code changes are required. This is a run-and-confirm pass.

## Current State Analysis

Verified as of 2026-05-26:

- **PHPCS lint**: 29/29 files, 0 errors, 0 warnings — `npm run lint` exits 0.
- **PHPStan level 8**: 23 files analyzed, no errors — `npm run analyse` exits 0.
- **Unit tests**: 6/6 passing, 14 assertions — `npm run test:unit` exits 0.
- **Integration skeleton**: `tests/Integration/Sync/WpWriterTest.php` exists; placeholder test extends `WP_UnitTestCase` and passes.
- **CI workflow**: `.github/workflows/ci.yml` already includes all four gates — `composer test:unit`, PHPStan `analyse`, `composer lint`, `composer audit`.
- **`.editorconfig`**: present at project root; PHP uses tabs, JSON/YAML/XML use 4-space indentation.
- **`composer.json`**: uses `squizlabs/php_codesniffer` (not the abandoned `phpcsstandards` package).

### Key Discoveries

- `src/Sync/SyncRunner.php:185` — the short ternary (`?:`) flagged in the original health-check was already replaced with an explicit full ternary and extracted variable before this session.
- `src/Presentation/WpBakeryIntegration.php` — the 9 array-alignment warnings were already auto-fixed; `npm run lint` reports 0 warnings.
- The `ci.yml` security audit step and the `.editorconfig` were both added in the same prior session that fixed the other Foundation items.

## Desired End State

Three gate commands (`lint`, `analyse`, `test:unit`) pass locally with zero errors. The `audit` gate is verified structurally: `.github/workflows/ci.yml` is confirmed to contain `composer audit` (there is no local npm wrapper for it). The integration test skeleton exists and is runnable. R-001 is formally archived; the next active change is `/10x-plan R-002`.

### Verification

Run the three gate commands below and confirm each exits 0; confirm ci.yml contains the audit step.

## What We're NOT Doing

- Writing a meaningful integration test for `WpWriterTest` — the placeholder is sufficient to keep the `npm run test` (combined) suite from failing; real CPT upsert coverage belongs in R-002 once the widget config layer exists to test.
- Expanding the CI workflow further (e.g., Dependabot) — not needed to unblock R-002.
- Changing any source files — all Foundation fixes are already in place.

## Implementation Approach

Run the three locally-executable gate commands (`lint`, `analyse`, `test:unit`). Confirm `ci.yml` structurally contains `composer audit`. Archive the change.

## Phase 1: Run CI verification gates

### Overview

Run the three gate commands that can be executed locally via npm (`lint`, `analyse`, `test:unit`). Confirm each exits 0. Confirm `ci.yml` contains the `composer audit` step (no local npm wrapper exists for Composer commands outside `composer:install` / `composer:update`). No edits, no new files — this is a read-and-confirm pass.

### Verification Steps

#### 1. Lint gate

**Command:** `npm run lint`
**Expected:** 29/29 files, 0 errors, 0 warnings. Exits 0.

#### 2. PHPStan gate

**Command:** `npm run analyse`
**Expected:** 23 files, no errors. Exits 0.

#### 3. Unit test gate

**Command:** `npm run test:unit`
**Expected:** 6/6 passing, 14 assertions. Exits 0.

#### 4. Integration skeleton gate

**Check:** Confirm `tests/Integration/Sync/WpWriterTest.php` exists and contains a class extending `WP_UnitTestCase` with at least one test method. No wp-env required — do not run the integration suite; just confirm the file exists.

#### 5. CI workflow gate

**Check:** Read `.github/workflows/ci.yml` and confirm it contains all four steps: `composer test:unit`, PHPStan `analyse --memory-limit=1G`, `composer lint`, `composer audit`.

### Success Criteria

#### Automated Verification

- `npm run lint` exits 0, reports 0 errors
- `npm run analyse` exits 0, reports 0 errors
- `npm run test:unit` exits 0, all 6 tests pass

#### Manual Verification

- `tests/Integration/Sync/WpWriterTest.php` exists and extends `WP_UnitTestCase`
- `.github/workflows/ci.yml` contains all four gate steps

**Implementation Note:** All verification steps should pass immediately — the Foundation fixes are already in place. If any gate fails unexpectedly, stop and investigate before archiving. After all gates pass, archive this change with `/10x-archive gate-product-routes` and proceed to `/10x-plan R-002`.

---

## References

- Roadmap R-001: `context/foundation/roadmap.md`
- Health-check baseline: `context/foundation/health-check.md`
- CI workflow: `.github/workflows/ci.yml`
- Integration skeleton: `tests/Integration/Sync/WpWriterTest.php`

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles. See `references/progress-format.md`.

### Phase 1: Run CI verification gates

#### Automated

- [x] 1.1 `npm run lint` exits 0, reports 0 errors
- [x] 1.2 `npm run analyse` exits 0, reports 0 errors
- [x] 1.3 `npm run test:unit` exits 0, all 6 tests pass

#### Manual

- [x] 1.4 `tests/Integration/Sync/WpWriterTest.php` exists and extends `WP_UnitTestCase`
- [x] 1.5 `.github/workflows/ci.yml` contains all four gate steps
