---
project: "Campsflow WP Plugin — Configurable Widgets"
generated: 2026-05-26
prd: context/foundation/prd.md
status: draft
framing:
  main_goal: market-feedback
  north_star: "R-003 — Search Panel Widget (FR-001–004)"
  top_blocker: capacity
  investment:
    frontend: invest deeply
    backend: go simple
    data: go simple
    infra: go simple
    observability: go simple
---

## At a glance

| ID    | Title                          | Status   | Parallel after | Depends on |
|-------|-------------------------------|----------|----------------|------------|
| R-001 | Pre-flight: CI + test health  | ready    | —              | —          |
| R-002 | Shared widget config layer    | proposed | —              | R-001      |
| R-003 | Search Panel Widget           | proposed | R-004, R-005, R-006 | R-002 |
| R-004 | Listing Card Widget           | proposed | R-003, R-005, R-006 | R-002 |
| R-005 | Event Detail Page Widget      | proposed | R-003, R-004, R-006 | R-002 |
| R-006 | Turnus Table Widget           | proposed | R-003, R-004, R-005 | R-002 |
| R-007 | API contract spec (parallel)  | ready    | independent    | —          |

## Framing

**main_goal: market-feedback** — Ship widget controls that tenants can compare against camps4you.pl and give feedback on. Sequencing prioritizes getting a tenant-testable widget in front of real users first, not completing all 4 widgets simultaneously. After the north-star slice ships, validate the config model matches tenant expectations before replicating it across the remaining 3 widgets.

**north_star: Search Panel Widget (R-003)** — The 6-field search panel is the explicit tenant comparison point vs. camps4you.pl. FR-001 through FR-004 cover all 4 config primitives (toggle, order, lock, default) in a single widget. Shipping this first proves the config model end-to-end; R-004 through R-006 follow the same pattern.

**top_blocker: capacity** — 17 must-have FRs across 4 widgets in 8 weeks with ASAP pressure, acknowledged as requiring sustained dedication. Mitigation: R-002 is sequenced first as the single shared layer, then R-003 through R-006 run as parallel agent streams. Nice-to-have FR-015 (turnus column toggle) is deferred to v2. Pre-flight (R-001) is a half-day fix, not a multi-day project.

**Investment areas:**
- **Frontend/Presentation: invest deeply** — all 17 FRs are Presentation layer changes. Six Elementor files (`ElementorIntegration.php`, `ElementorWidget.php`, `EventMetaWidget.php`, `EventSessionsWidget.php`, `WpBakeryIntegration.php`, `WpBakeryDynamicContent.php`) are excluded from PHPStan. Every edit to those files requires explicit `@var` annotations and manual return-type review — there is no static analysis safety net there.
- **Backend/API: go simple** — sync pipeline (Fetcher, Transformer, WpWriter, SyncScheduler) is frozen for v1 widget delivery; no new REST endpoints needed.
- **Data: go simple** — no CPT schema changes; widget configuration is stored per placement in Elementor widget settings (WP post meta). No data migration.
- **Infra: go simple** — GitHub Actions CI covers all gates; fix the pre-flight issues and no further investment is needed.
- **Observability: go simple** — no observability investment in v1; capacity constraint and market-feedback goal both point to shipping fast, not instrumenting.

## Baseline

```
Frontend:      partial — Elementor widgets + PHP templates (templates/*.php);
               assets/js/registration.js is the only JS; no compiled bundle pipeline
Backend/API:   present — WordPress Plugin API (hooks, CPTs, meta, Options);
               src/Api/WebhookEndpoint.php (REST webhook); Campsflow API is external
Data:          present — cf_event / cf_session CPTs (src/PostType/),
               cf_tag / cf_age_group taxonomies (src/Taxonomy/),
               WP_Query repositories (src/Repository/), Options API
Auth:          present — WordPress native capability gates (manage_options, editor)
Deploy/infra:  present — GitHub Actions ci.yml + release.yml; GitHub Releases ZIP
Observability: absent
```

## Foundations

These are CI/test health prerequisites that must land before R-002 begins. If CI is broken, every subsequent push fails.

**F-1 — Fix active PHPCS error (`src/Sync/SyncRunner.php:185`)**
`Universal.Operators.DisallowShortTernary` causes `npm run lint` to exit non-zero, blocking CI on every push. Fix: replace the `?:` short ternary with a full ternary or extracted variable. Effort: < 5 min.

**F-2 — Integration test directory + skeleton**
`tests/Integration/` does not exist on disk. `phpunit.xml` declares the suite and `npm run test` fails with "Test directory not found." Fix: create the directory and a skeleton `WpWriterTest` extending `WP_UnitTestCase`. Effort: 15–30 min.

**F-3 — Auto-fix PHPCS warnings in `WpBakeryIntegration.php`**
9 array-alignment warnings, all auto-fixable. Fix: `npm run lint:fix`. Effort: < 5 min.

F-1, F-2, F-3 are bundled into R-001.

---

## R-001: Pre-flight — CI and test health

**Status:** ready
**Parallel:** no — must land before R-002 begins
**Depends on:** —
**FRs:** none (prerequisite work)

**User-visible outcome:** CI passes on every push to main; `npm run test` exits cleanly. Invisible to tenants but unblocks all subsequent agent work.

**Scope:**
- Fix `SyncRunner.php:185` short ternary (F-1)
- Create `tests/Integration/Sync/WpWriterTest.php` skeleton (F-2)
- Run `npm run lint:fix` on `WpBakeryIntegration.php` (F-3)

**Unknowns:** none.

---

## R-002: Shared widget config layer

**Status:** proposed
**Parallel:** no — single-thread; all widget slices depend on this
**Depends on:** R-001
**FRs:** architectural prerequisite for FR-001 through FR-016

**User-visible outcome:** Admin can open any future Campsflow Elementor widget and see a config section with toggle / priority / lock / default controls — though no widget output is wired yet. Serves as the tenant-visible proof of concept for the config interaction pattern before it ships in production.

**What this is:**
The config persistence contract and shared rendering utility shared by all 4 widget slices:

- **Config schema** — per-field: visibility toggle (bool), display priority (int), locked value (string|null), default value (string|null). Stored as Elementor widget settings (WP post meta) per placement, not in CPT meta.
- **Shared rendering utility** — given a field list and a config, returns fields in priority order with hidden fields excluded.
- **Backward-compat rule** — zero-config defaults must reproduce the current hardcoded output exactly. Any widget placement with no new configuration set must render identically to what ships today.
- **Config schema stability** — the schema must survive plugin updates without silently resetting admin-configured values.

**PHPStan note:** The Elementor files being edited in R-003 through R-006 are excluded from static analysis. Add explicit `@var` annotations on all Elementor API object access in those files. Do not add new files to `excludePaths` without documenting why in `phpstan.neon`.

**Unknowns:**
- Open Question 3 (non-blocking): exact PHP control identifier strings per field in each widget — resolved during implementation, not a design blocker.

---

## R-003: Search Panel Widget ★ north-star

**Status:** proposed
**Parallel:** yes — can run concurrently with R-004, R-005, R-006 after R-002 lands
**Depends on:** R-002
**FRs:** FR-001, FR-002, FR-003, FR-004

**User-visible outcome:** A site admin opens the Search Panel Elementor widget, sees 6 configurable filter field rows (Kierunek, Termin, Wiek, Dla Kogo, Transport, Profil), can toggle each visible or hidden, set their display order via numbered priority inputs, lock any field to a specific value (visitor sees a read-only label showing the active constraint), and set a default value (visitor sees it pre-filled but can change it). The rendered front-end reflects the admin's configuration exactly.

**Tenant feedback gate:** After this slice ships, show it to at least one tenant and confirm the config model matches their expectation vs. camps4you.pl. The feedback from this gate informs whether R-004 through R-006 need any course correction before they're finished.

**FRs detail:**
- FR-001: Toggle each filter field visible/hidden from widget panel
- FR-002: Display order via numbered priority (not drag-and-drop — v2 concern)
- FR-003: Lock field to value → visitor sees read-only label, not a hidden filter
- FR-004: Default value → pre-filled for visitor, visitor can override

**Unknowns:**
- **Open Question 2 (partial block):** TRANSPORT and DLA KOGO filter fields — do they map to existing `cf_tag` / `cf_age_group` taxonomies, or require new data from the sync pipeline? Implementation can start by wiring to existing taxonomy data. New API fields are a parallel track (R-007); the filter will be extended once the API extension lands. Owner: user + Campsflow API team.

---

## R-004: Listing Card Widget

**Status:** proposed
**Parallel:** yes — can run concurrently with R-003, R-005, R-006 after R-002 lands
**Depends on:** R-002
**FRs:** FR-005, FR-006, FR-007, FR-008, FR-009

**User-visible outcome:** A site admin opens the Listing Card Elementor widget, can toggle card fields on/off (toggling price off displays an advisory warning in the widget panel), set card field display order via numbered priority, select card grid layout (grid only in v1), set items-per-page count with paginated pagination, and control which badges appear on card images. The rendered card listing reflects the admin's configuration.

**FRs detail:**
- FR-005: Toggle card fields (title, dates, price, availability badge, location, tags); price-off advisory in panel
- FR-006: Card field order via numbered priority
- FR-007: Card layout selector — card grid only in v1; list layout is v2
- FR-008: Items-per-page + paginated pagination; infinite scroll is v2
- FR-009: Badge visibility via widget panel config; badge color/size/style via Elementor style panel

**Unknowns:** none blocking.

---

## R-005: Event Detail Page Widget

**Status:** proposed
**Parallel:** yes — can run concurrently with R-003, R-004, R-006 after R-002 lands
**Depends on:** R-002
**FRs:** FR-010, FR-011, FR-012, FR-013, FR-014

**User-visible outcome:** A site admin opens the Event Detail Page Elementor widget, can toggle content sections on/off (description, accommodation, activities, gallery, documents), set their display order via numbered priority, control which turnus summary fields appear in the event hero area, toggle a context-aware related camps section on/off, and customize the display title of each section independently per widget placement.

**FRs detail:**
- FR-010: Toggle content sections on/off; admin owns legal compliance for any section
- FR-011: Section order via numbered priority
- FR-012: Turnus summary fields in event hero (next date, price range, availability bucket)
- FR-013: Context-aware related camps — when on, surfaces events whose `cf_tag`, `cf_age_group`, and `cf_location` match the visitor's active URL search parameters (server-side render; no ML or scoring)
- FR-014: Customize display title of each section per placement (tenants use different terminology, e.g., "Co robimy" vs. "Program")

**Business logic note (FR-013):** The recommendation query reads the visitor's URL query string at render time and runs a `WP_Query` filtered by `cf_tag`, `cf_age_group`, `cf_location`. Pure attribute match — no ranking algorithm. The current availability bucket rule (event bucket = best turnus bucket) is unchanged.

**Unknowns:** none blocking.

---

## R-006: Turnus Table Widget

**Status:** proposed
**Parallel:** yes — can run concurrently with R-003, R-004, R-005 after R-002 lands
**Depends on:** R-002
**FRs:** FR-015 (nice-to-have), FR-016, FR-017, FR-018

**User-visible outcome:** A site admin opens the Turnus Table Elementor widget and can set the display order of columns via numbered priority. The global WP Admin → Campsflow → Settings page gains an availability status display customization section (label text, color, icon style per bucket) that applies site-wide to all widgets.

**FRs detail:**
- FR-016: Column order via numbered priority (must-have)
- FR-017: Global availability bucket display customization on Settings page — label, color, icon style; applies to all widgets site-wide (must-have)
- FR-018: V1 columns — dates (`cf_date_from` / `cf_date_to`), price (`cf_price`), availability bucket (`cf_availability`), register button (must-have)
- FR-015: Column toggle on/off — nice-to-have; defer to v2 if capacity is tight

**V2 dependency:** Transport type, pickup location, and return-to location columns wait for R-007 (API contract) plus the Campsflow API team delivering the new fields. Plugin-side Transformer/WpWriter extension ships as a separate change after the API is live.

**Unknowns:** none blocking for v1 scope.

---

## R-007: API contract spec (parallel track)

**Status:** ready
**Parallel:** yes — fully independent; can run at any time alongside any other slice
**Depends on:** —
**FRs:** prerequisite for v2 Turnus Table columns (transport, pickup, return-to location)

**User-visible outcome:** None in v1. This track produces a JSON shape document defining the planned API contract for new turnus fields (transport type, pickup location, return-to location) and updates mock data / test fixtures to match the agreed shape. When the Campsflow API team ships these fields, the Transformer/WpWriter extension can be implemented against a pre-agreed contract without re-design.

**Scope:**
- Document the planned API JSON shape for new turnus fields (field names, types, nullability)
- Update mock data and test fixtures to match
- Actual Transformer/WpWriter extension is a separate change — ships after the API is live

**Unknowns:**
- **Owner: user + Campsflow API team.** Field names, types, and nullability must be agreed with the API team. This track cannot finalize the contract unilaterally. Until the API team engages, this track is unblocked for drafting a proposal but blocked for finalization.

---

## Done

<!-- /10x-archive appends entries here when changes are closed -->

## Open Questions

1. **TRANSPORT and DLA KOGO filter field mapping** — Do these search filter fields map cleanly to existing `cf_tag` / `cf_age_group` taxonomy data, or require new meta/taxonomy fields in the sync pipeline? Owner: user + Campsflow API team. Block: partial — R-003 can start wiring to existing taxonomy data; new fields require the parallel API extension track (R-007). See also PRD Open Question 2.

2. **Tenant feedback gate after R-003** — Who is the target tenant for the north-star feedback loop, and how will their feedback be collected and acted on? Owner: user. Block: no — R-003 ships regardless; this is a process decision, not a technical dependency.

3. **Exact Elementor control identifiers per field** — The FRs name field categories (dates, price, tags, etc.) but the PHP control ID strings used in each widget's `_register_controls()` method are resolved during implementation. Owner: implementation. Block: no.
