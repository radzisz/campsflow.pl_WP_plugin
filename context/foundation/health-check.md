---
project: campsflow-wp
checked_at: 2026-05-23T00:00:00Z
health_status: needs-attention
audit:
  critical: 0
  high: 0
  moderate: 0
  low: 0
  abandoned: 1
test_runner: PHPUnit 10.x (unit: 6 tests passing; integration: suite directory missing)
ci_provider: GitHub Actions
config_gaps:
  high: 1
  medium: 1
  low: 1
---

## Pre-check: Dependency Audit

**Lockfile**: `composer.lock` present. Builds are reproducible. ✓

**Security advisories**: None. `composer audit` returned 0 vulnerabilities across all severity levels.

**Abandoned packages**: 1 finding.
- `phpcsstandards/php_codesniffer` is abandoned. Recommended replacement: `squizlabs/php_codesniffer`. The package still works and receives no security patches, but Composer surfaces this on every install. Impact: low (no security exposure, only a maintenance concern).

```
Pre-check: lockfile present. Audit: 0 CRITICAL, 0 HIGH, 0 MODERATE, 0 LOW. 1 abandoned package.
```

---

## In-check: Test Infrastructure, CI/CD, Configuration

### Test runner

**PHPUnit 10.x detected.** Two suites configured in `phpunit.xml`: `unit` and `integration`.

- **Unit suite** (`tests/Unit/`): 6 tests, 14 assertions, all passing in 0.9 s. ✓
  - `TransformerTest` covers bucket computation: full/almost_full/few_left/available, zero-seats edge case, and full field mapping. Coverage is narrow but meaningful.
- **Integration suite** (`tests/Integration/`): **directory does not exist on disk.** The suite is declared in `phpunit.xml` and documented in `CLAUDE.md`, but no test files have been written. Running `npm run test` (which includes integration) exits with "Test directory not found."

### CI/CD

**GitHub Actions detected.** Two workflows:

| Stage       | ci.yml | release.yml |
|-------------|--------|-------------|
| Lint (PHPCS)| ✓      | ✗ (skipped) |
| Test (PHPUnit unit) | ✓ | ✓ |
| Build (Composer install) | ✓ | ✓ |
| Type-check (PHPStan) | ✓ | ✓ |
| Security scan | ✗ | ✗ |

CI covers the core gates well. No security-scanning step exists in either workflow (Dependabot, `composer audit`, or Snyk).

```
CI/CD: GitHub Actions detected. Stages: lint ✓, test ✓, build ✓, type-check ✓, security ✗.
```

### Configuration gaps

**PHPCS ruleset** (`.phpcs.xml`): present and active. Targets `WordPress-Core` with PSR-4 exclusions (snake_case variable names, Yoda conditions, file comment requirements). ✓

**PHPStan config** (`phpstan.neon`): present, now at level 8 (raised in this session). ✓

**`.gitignore`**: present. ✓

**`CLAUDE.md`**: present (project + global). ✓

**`.editorconfig`**: **not present at root.** Editors not configured for consistent indentation and line endings. Low severity — PHPStan and PHPCS compensate for most style drift, but tab-vs-space inconsistency could surface in diffs.

**Active PHPCS error** — `src/Sync/SyncRunner.php:185`: "Using short ternaries is not allowed" (`Universal.Operators.DisallowShortTernary.Found`). **High severity** — linting exits non-zero, which means `npm run lint` fails in CI. This was introduced when refactoring the `date_create` null-safety check (the fix for the `DateTime|false` PHPStan level 8 error). Needs immediate correction.

**Auto-fixable PHPCS warnings** — `src/Presentation/WpBakeryIntegration.php`: 9 array-alignment warnings, all auto-fixable with `npm run lint:fix`. Medium severity — warnings do not fail CI but indicate style drift.

```
In-check: test runner detected (PHPUnit, unit passing, integration missing), CI GitHub Actions,
3 configuration gaps (1 high, 1 medium, 1 low).
```

---

## Cross-reference: Stack Assessment Gaps

From `context/foundation/stack-assessment.md` (3 gaps identified):

| Gap | Status |
|-----|--------|
| Gap 1: PHPStan level mismatch (docs said 8, config was 5) | ✅ RESOLVED — `phpstan.neon` raised to level 8 in this session. Clean at level 8. |
| Gap 2: Six presentation files excluded from PHPStan | ⚠ STILL OPEN — excluded paths remain in `phpstan.neon`. Known trade-off (third-party widget APIs). Compensation: manual `@var` annotations when editing those files. |
| Gap 3: Integration test env dependency not obvious | ⚠ WORSE THAN ASSESSED — the integration test directory doesn't exist at all, not just requiring a running env. No integration tests have been written yet. |

---

## Prioritized Fixes

### Category A — Fix before agent work

**1. Active PHPCS error in `SyncRunner.php:185`**
- **Finding**: `Universal.Operators.DisallowShortTernary` — short ternary `?:` is banned by the WPCS ruleset. `npm run lint` exits non-zero. CI lint step would fail on this file.
- **Impact**: Any agent-generated code in `SyncRunner.php` will hit a failing lint gate. Misleading when debugging CI failures.
- **Fix**: Replace `( date_create( $t->dateFrom ) ?: null )?->format( 'd.m.Y' )` with an extracted variable using a full ternary. Example:
  ```php
  $dateObj    = date_create( $t->dateFrom );
  $dateFormatted = $dateObj !== false ? $dateObj->format( 'd.m.Y' ) : null;
  $title = $t->name !== '' ? $t->name : ( $t->dateFrom ? ( $dateFormatted ?? $t->dateFrom ) : $t->turnusId );
  ```
- **Effort**: quick (< 5 min)

**2. Integration test suite is empty**
- **Finding**: `tests/Integration/` does not exist. `phpunit.xml` declares the suite; CLAUDE.md documents it. Running `npm run test` fails with "Test directory not found."
- **Impact**: An agent cannot write or run integration tests without a bootstrap. More critically, the WpWriter (the component that does CPT upserts) has no test coverage at all — only the Transformer has tests.
- **Fix**: Either create a placeholder integration test or update `phpunit.xml` to make the directory optional. The former is better — a single `WpWriterTest` skeleton makes the suite runnable and signals intent to the agent.
- **Effort**: moderate (15–30 min to write a first integration test skeleton)

**3. Auto-fixable PHPCS warnings in `WpBakeryIntegration.php`**
- **Finding**: 9 array-alignment warnings, all marked `[x]` (auto-fixable by PHPCBF).
- **Impact**: Low — warnings don't fail CI, but they add noise to lint output and indicate this file has accumulated style drift.
- **Fix**: `npm run lint:fix` — resolves all 9 automatically.
- **Effort**: quick (< 5 min)

**4. Abandoned package `phpcsstandards/php_codesniffer`**
- **Finding**: Package declared abandoned by its maintainers. Replacement: `squizlabs/php_codesniffer`.
- **Impact**: No security exposure. Composer warns on every install. Will eventually stop receiving updates.
- **Fix**: In `composer.json`, replace `"phpcsstandards/php_codesniffer": "^3.10"` with `"squizlabs/php_codesniffer": "^3.10"`, then run `npm run composer:update`.
- **Effort**: quick (< 5 min)

**5. Missing `.editorconfig`**
- **Finding**: No `.editorconfig` at the project root. Editors may use inconsistent indentation (tabs vs spaces) or line endings.
- **Impact**: Low — PHPCS already enforces tab indentation for PHP. But the root `campsflow.php` and any JS/JSON files have no enforced style across editors.
- **Fix**: Create a `.editorconfig` with `indent_style = tab` for PHP and `indent_style = space, indent_size = 4` for JSON/YAML.
- **Effort**: quick (< 5 min)

**6. No security scan in CI**
- **Finding**: Neither `ci.yml` nor `release.yml` runs `composer audit` or Dependabot.
- **Impact**: Security vulnerabilities in dependencies won't be surfaced automatically. Currently no vulnerabilities exist, but the safety net is absent.
- **Fix**: Add a step to `ci.yml`: `run: composer audit`. Optionally enable GitHub Dependabot via `.github/dependabot.yml`.
- **Effort**: quick (< 5 min per approach)

### Category B — Addressed in upcoming lessons

**Missing `AGENTS.md`**
No agent instruction file in the Codex/Copilot convention exists yet. `CLAUDE.md` covers Claude Code but `AGENTS.md` as a team-accessible file is absent.
→ Agent onboarding covers this: [Agent Onboarding: Agents.md, AI Rules i feedback loops (M1L4)](https://platforma.przeprogramowani.pl/external/10xdevs-3/m1-l4)

**No deployment configuration**
No `Dockerfile`, `fly.toml`, or deployment target beyond GitHub Releases (ZIP). The release pipeline creates a distributable plugin package, which is appropriate for a WordPress plugin. No container or hosting deployment is expected.
→ Infrastructure lesson covers CI/CD expansion: [Sprint Zero z Agentem: infrastruktura, walking skeleton i pierwszy deploy (M1L5)](https://platforma.przeprogramowani.pl/external/10xdevs-3/m1-l5)

---

## Summary

**Health: needs-attention.** No security vulnerabilities, unit tests passing, CI pipeline in good shape — the core infrastructure is solid. Three Category A fixes needed before handing the codebase to an agent for regular development:

1. **PHPCS error in SyncRunner.php:185** — active lint failure blocks CI. Fix the short ternary. (quick)
2. **Integration test suite is a ghost** — declared in config, documented in CLAUDE.md, non-existent on disk. At minimum, create the directory and a skeleton test. (moderate)
3. **WpBakeryIntegration warnings** — auto-fixable now. Run `npm run lint:fix`. (quick)

Items 4–6 are lower priority but worth cleaning up in the same session.

The PHPStan level mismatch (the main gap from stack-assessment) is resolved. PHPStan now runs at level 8 cleanly.
