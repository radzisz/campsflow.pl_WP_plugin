---
project: campsflow-wp
assessed_at: 2026-05-23T00:00:00Z
agent_readiness: ready-with-compensation
context_type: brownfield
stack_components:
  language: PHP 8.1+ / 8.2
  framework: WordPress Plugin API
  build_tool: Composer 2 + npm/@wordpress/env
  test_runner: PHPUnit 10.x (Brain\Monkey unit / WP_UnitTestCase integration)
  package_manager: Composer (PHP), npm (dev tooling)
  ci_provider: GitHub Actions
  deployment_target: GitHub Releases (ZIP via softprops/action-gh-release)
gates_passed: 3
gates_failed: 1
---

## Stack Components

**Language — PHP 8.1 / 8.2.** `composer.json` requires `php: >=8.1`; `.wp-env.json` and CI use PHP 8.2. The codebase mandates `declare(strict_types=1)` in every file (enforced by CLAUDE.md rule). PHP 8.1+ brings union types, intersection types, readonly properties, and native enums, making it the most type-rich PHP release to date.

**Framework — WordPress Plugin API.** The plugin targets WordPress via its native hook system (actions/filters), Custom Post Types, meta API, Options API, Nonces, and Capabilities. It is not built on a PHP web framework (no Laravel, Symfony, Slim, etc.) — it is a first-party WordPress plugin with PSR-4 autoloading under the `Campsflow\` namespace. Architectural layering is: `Sync` (data pipeline), `Presentation` (shortcodes/widgets), `Admin` (settings/status pages), `PostType` + `Taxonomy` (CPT registration), `Repository` (WP_Query wrappers).

**Build tooling.** Composer 2 manages PHP dependencies; all PHP tooling (PHPUnit, PHPStan, PHPCS, PHPCBF) is invoked via Docker (`docker run --rm ... php:8.2-cli vendor/bin/...`) through npm scripts defined in `package.json`. The npm dependency is `@wordpress/env` (`wp-env`), which provisions the local WordPress + MySQL environment in Docker. There is no front-end build pipeline — the plugin ships no compiled JS/CSS bundles beyond a single `registration.js` file.

**Test runner — PHPUnit 10.x.** Two suites: `unit` (uses Brain\Monkey to mock WP functions, no live WP required) and `integration` (uses `WP_UnitTestCase`, requires `npm run env:start` to be running). PHPUnit is configured in `phpunit.xml`; `failOnWarning` and `failOnRisky` are set to false.

**Static analysis — PHPStan level 5.** Configured in `phpstan.neon`, level 5 with `szepeviktor/phpstan-wordpress` extension providing WordPress-specific stubs. Six presentation files are explicitly excluded from analysis (see Gaps section).

**Linting — PHPCS + WordPress Coding Standards 3.1.** The WPCS ruleset governs formatting, naming, escaping, nonce/capability patterns, and sanitisation conventions.

**CI/CD — GitHub Actions.** Two workflows: `ci.yml` (triggers on push to `main`/`dev` and PRs to `main` — runs unit tests, PHPStan, PHPCS) and `release.yml` (triggers on tag `v*` — runs unit tests + PHPStan, then builds a ZIP and creates a GitHub Release with `softprops/action-gh-release`).

---

## Quality Gate Assessment

| Component        | Typed | Convention | Training Data | Documented | Verdict        |
|------------------|-------|------------|---------------|------------|----------------|
| Language (PHP)   | ~     | —          | —             | —          | partial pass   |
| Framework (WP)   | —     | ✓          | ✓             | ✓          | pass           |
| Build (Composer) | —     | ✓          | ✓             | ✓          | pass           |
| Test (PHPUnit)   | —     | —          | ✓             | ✓          | pass           |

Legend: ✓ = pass, ✗ = fail, ~ = partial, — = not applicable

### Gate Details

**Gate 1 — Typed (Language)**
Score: **partial pass (~)**

Evidence for pass: `declare(strict_types=1)` is mandated in all files (observed in every `src/` file sampled, e.g. `src/Sync/Transformer.php:2`). PHP 8.1+ type hints used consistently (constructor property promotion with typed readonly properties, typed function signatures, PHPDoc `@param array<string, mixed>` generics). PHPStan is configured and running in CI via `phpstan.neon`.

Evidence against full pass:
- PHPStan is at **level 5** (basic type inference), not the higher level 8 that `CLAUDE.md:281` references. Level 5 catches argument type mismatches but misses nullable analysis, dead code detection, and full return type inference that level 8 provides.
- **Six files are excluded** from PHPStan analysis via `phpstan.neon` `excludePaths`: `ElementorIntegration.php`, `ElementorWidget.php`, `EventMetaWidget.php`, `EventSessionsWidget.php`, `WpBakeryIntegration.php`, `WpBakeryDynamicContent.php`. No static analysis runs against those files.
- WordPress itself exposes many functions returning `mixed` or `WP_Error|T` unions that the `szepeviktor/phpstan-wordpress` stubs cover only partially.

**Gate 2 — Convention-based (Framework)**
Score: **pass (✓)**

WordPress enforces strong conventions project-wide: hooks must be registered via `add_action`/`add_filter`, CPT registration happens on `init`, nonces are validated with `wp_verify_nonce`, capabilities checked with `current_user_can`. The plugin's own layer conventions are documented in `CLAUDE.md` (PSR-4, no direct `$wpdb` queries, all output escaped, all input sanitised). Evidence: `composer.json` autoload block, `src/` layer structure.

**Gate 3 — Popular in training data (Framework, Build tool, Test runner)**
Score: **pass (✓)**

PHP and WordPress constitute the most widely deployed CMS platform (≈43% of the web) and are extensively represented in training data. PHPUnit is the canonical PHP test framework with 20+ years of documentation, blog posts, and examples. Composer is the universal PHP package manager. PHPCS/WPCS is the standard WordPress linting toolchain. The agent can be expected to know WordPress hook patterns, CPT registration, the Options API, and PHPUnit assertion styles without additional steering.

**Gate 4 — Well-documented (Framework, Build tool, Test runner)**
Score: **pass (✓)**

WordPress maintains versioned developer docs at `developer.wordpress.org`. PHPUnit 10.x has current docs at `phpunit.de`. PHPStan has docs at `phpstan.org`. Brain\Monkey has docs at `brain-wp.github.io/BrainMonkey`. WPCS documents all sniffs inline. `szepeviktor/phpstan-wordpress` documents its stubs in the GitHub repo. No component relies on community wikis or outdated third-party documentation.

---

## Gaps & Compensation

### Gap 1 — PHPStan level mismatch (CLAUDE.md says 8, config is 5)

**What failed**: Gate 1 partial pass. `CLAUDE.md:281` states `npm run analyse # PHPStan level 8` but `phpstan.neon:4` has `level: 5`. An agent reading CLAUDE.md will believe level 8 is enforced; it isn't.

**Why it matters**: If the agent adds new code that passes PHPStan at level 5 but would fail at level 8, it will report clean analysis incorrectly. This also means the agent may skip type annotations that level 8 would require, creating drift toward weaker typing over time.

**Compensation** — add to the PHPStan section of `CLAUDE.md`:

```
## Static analysis

`phpstan.neon` is the authoritative config — it currently runs at **level 5**. The comment
"PHPStan level 8" in the dev commands section is aspirational and not yet enforced.
- Run analysis: `npm run analyse` (uses phpstan.neon — do NOT pass --level override)
- When PHPStan passes at level 5, that is the acceptance bar. Do not assume level 8 rules apply.
- When raising the level in the future, update phpstan.neon first, then update this note.
```

---

### Gap 2 — Six presentation files excluded from PHPStan

**What failed**: Gate 1 partial pass. `phpstan.neon` `excludePaths` removes these files from analysis:
- `src/Presentation/ElementorIntegration.php`
- `src/Presentation/ElementorWidget.php`
- `src/Presentation/EventMetaWidget.php`
- `src/Presentation/EventSessionsWidget.php`
- `src/Presentation/WpBakeryIntegration.php`
- `src/Presentation/WpBakeryDynamicContent.php`

**Why it matters**: Agents editing those files receive no static analysis signal. Type errors, undefined property access, and wrong return types will not be caught by `npm run analyse`. This is a known trade-off (the files use third-party widget APIs with incomplete stubs), but the agent must know about it.

**Compensation** — add to `CLAUDE.md`:

```
## PHPStan excluded files

These files are excluded from static analysis (third-party widget APIs have incomplete stubs):
- src/Presentation/ElementorIntegration.php
- src/Presentation/ElementorWidget.php
- src/Presentation/EventMetaWidget.php
- src/Presentation/EventSessionsWidget.php
- src/Presentation/WpBakeryIntegration.php
- src/Presentation/WpBakeryDynamicContent.php

When editing these files: add explicit `@var` annotations on all WP object access, manually
review return types, and compensate with extra care since `npm run analyse` gives no feedback.
Do NOT add new files to excludePaths without documenting why.
```

---

### Gap 3 — Integration test environment dependency not obvious from repo

**What failed**: not a gate failure but an operational trap. `npm run test:integration` silently fails or produces confusing errors if the `wp-env` Docker environment is not running. This is not discoverable from `package.json` alone.

**Why it matters**: Agents attempting to run the full test suite will encounter unexplained failures if they call `npm run test` (which includes integration) without first running `npm run env:start`.

**Compensation** — add to `CLAUDE.md`:

```
## Running tests

Unit tests run against a plain PHP Docker container — no environment setup required:
```bash
npm run test:unit      # fast, self-contained
npm run analyse        # PHPStan (no env required)
npm run lint           # PHPCS (no env required)
```

Integration tests require the WordPress Docker environment to be running first:
```bash
npm run env:start      # start WP + MySQL in Docker (one-time per session)
npm run test:integration
```

Never run `npm run test` (combined) in CI without the env running — use the split commands.
```

---

### Recommended Instruction File Additions

Paste these three blocks into `CLAUDE.md` (replacing or augmenting the existing dev commands section):

---

**Block 1 — PHPStan level clarification**
```markdown
## Static analysis

`phpstan.neon` is the authoritative config — currently **level 5**.
- Run: `npm run analyse` (do NOT pass --level override)
- Level 5 is the acceptance bar. Do not assume level 8 rules apply.
- To raise the level: update phpstan.neon, then update this note.
```

---

**Block 2 — PHPStan excluded files**
```markdown
## PHPStan excluded files

Static analysis does not run against:
- src/Presentation/ElementorIntegration.php
- src/Presentation/ElementorWidget.php
- src/Presentation/EventMetaWidget.php
- src/Presentation/EventSessionsWidget.php
- src/Presentation/WpBakeryIntegration.php
- src/Presentation/WpBakeryDynamicContent.php

When editing these: add `@var` annotations explicitly and review return types manually.
Do not add new files to excludePaths without a comment explaining why.
```

---

**Block 3 — Test environment dependency**
```markdown
## Running tests

Unit / static analysis — no Docker env required:
  npm run test:unit | npm run analyse | npm run lint

Integration — requires running env first:
  npm run env:start
  npm run test:integration

Combined `npm run test` includes integration — only run it with env started.
```

---

## Summary

The stack is **ready-with-compensation**. PHP 8.1+ with WordPress conventions, PHPUnit, PHPStan, and PHPCS provides a strong, well-documented, and training-data-rich foundation. The agent will need minimal steering on WordPress hook patterns, CPT registration, and PHPUnit assertions — all well-covered by training data.

Three concrete gaps need to be addressed in `CLAUDE.md` before handing the agent regular work:

1. **PHPStan level mismatch** — the instruction file says level 8; the tool is configured to level 5. Fix the instruction file to reflect reality.
2. **Six excluded files** — the agent must know that no static analysis runs on those presentation files so it compensates with manual annotation discipline.
3. **Integration test environment gate** — add explicit instructions that `test:integration` requires `env:start` first.

Once those three blocks are added to `CLAUDE.md`, the stack poses no significant friction for agent-assisted development.

**Next step**: `/10x-health-check` — focuses test runner detection, CI/CD evaluation, and missing configuration analysis on the gaps identified here.
