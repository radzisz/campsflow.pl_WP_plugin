# Shared Widget Config Layer (R-002) — Plan Brief

> Full plan: `context/changes/widget-config-layer/plan.md`

## What & Why

R-002 builds the shared PHP config primitives that all four widget slices (R-003–R-006) depend on. Without it, each widget slice would reinvent its own field-ordering and visibility logic. The goal is to prove the config model end-to-end — admin sees and saves toggle/priority/lock/default controls — before any of the four widget slices wire them to visitor output.

## Starting Point

`EventMetaWidget` already has 11 flat SWITCHER controls (`show_photos`, `show_location`, …) for visibility. No priority, locked-value, or default-value controls exist anywhere in the codebase. No shared "field config" concept exists. All six Elementor/WPBakery files in `src/Presentation/` are excluded from PHPStan.

## Desired End State

A tenant's WordPress admin opens EventMetaWidget in the Elementor editor and sees a "Konfiguracja pól" section with 11 field rows, each exposing four controls: visible SWITCHER, priority NUMBER, locked-value TEXT, default-value TEXT. The priority defaults reproduce today's hardcoded render order so zero-config placements are backward-compatible. `FieldConfig` + `FieldSorter` (pure PHP, fully PHPStan-covered) are ready for R-003–R-006 to import. Visitor output is unchanged.

## Key Decisions Made

| Decision | Choice | Why (1 sentence) | Source |
|---|---|---|---|
| New code namespace | `src/Widget/` (`Campsflow\Widget`) | Clean separation from PHPStan-excluded Presentation files; new files fully analyzed | Plan |
| Proof-of-concept widget | EventMetaWidget — all 11 fields | Already has SWITCHER toggles; R-005 will wire render() to these same controls with no extra migration | Plan |
| Elementor control structure | Flat controls — 5 per field (HEADING + 4 controls) | REPEATER sub-controls don't support SWITCHER; flat matches existing pattern in EventMetaWidget | Plan |
| FieldSorter API | `sort(array $fieldDefs, array $settings): list<FieldConfig>` | Elementor widget passes `get_settings_for_display()` directly; no adapter layer needed | Plan |
| Backward-compat defaults | Elementor `default:` parameter on each control | Elementor persists defaults with stored values; no second source of truth needed | Plan |
| Existing `show_{id}` control IDs | Preserved unchanged | Forward-compatible: any placement saved before R-002 continues to render without data loss | Plan |
| Unit test scope | Sort behavior only (4 cases) | Backward-compat invariant deferred; sort-behavior tests cover the load-bearing contract for R-003–R-006 | Plan |

## Scope

**In scope:**
- `src/Widget/FieldConfig.php` — readonly value object
- `src/Widget/FieldSorter.php` — sort + filter utility
- `tests/Unit/Widget/FieldSorterTest.php` — 4 Brain\Monkey unit tests
- `src/Presentation/EventMetaWidget.php` — replace `registerContentSection()` with loop-driven `registerConfigSection()`

**Out of scope:**
- Wiring FieldSorter into any widget's `render()` (R-003–R-006)
- Config controls for `ElementorWidget` or `EventSessionsWidget` (R-004, R-006)
- Search Panel widget (R-003)
- CPT schema, sync pipeline, shortcodes, templates

## Architecture / Approach

```
src/Widget/FieldConfig.php     ← readonly value object (id, label, visible, priority, lockedValue, defaultValue)
src/Widget/FieldSorter.php     ← sort(fieldDefs[], settings[]) → FieldConfig[]

EventMetaWidget                ← calls add_control() for each field × 4 primitives
  → passes get_settings_for_display() to FieldSorter in R-005+
```

Settings-key convention (baked into FieldSorter): `show_{id}` (visible), `{id}_priority`, `{id}_locked`, `{id}_default`.

## Phases at a Glance

| Phase | What it delivers | Key risk |
|---|---|---|
| 1. Pure PHP layer | FieldConfig + FieldSorter + unit tests; PHPStan-clean | Stable sort tie-breaking must be explicit — PHP `usort` is not stable |
| 2. EventMetaWidget config section | Admin sees 11 × 4 controls in Elementor editor; visitor output unchanged | 55 `add_control()` calls — easy to misname a key and break FieldSorter's lookup convention |

**Prerequisites:** Docker Desktop running (`npm run env:start` for Phase 2 manual verification).
**Estimated effort:** ~2–3 hours across 2 phases.

## Open Risks & Assumptions

- `{id}_locked` and `{id}_default` controls use plain TEXT — no value validation. For the Search Panel (R-003), these may need SELECT controls (taxonomy terms, location names). That's an R-003 concern, not R-002.
- Elementor's NUMBER control stores integers as strings. FieldSorter must cast with `(int)` or `intval()` — a silent bug if omitted (priority comparisons would silently do string comparison).

## Success Criteria (Summary)

- `npm run analyse`, `npm run lint`, `npm run test:unit` all exit 0 after Phase 1.
- Admin opens EventMetaWidget in Elementor editor and sees "Konfiguracja pól" section with 11 fields × 4 controls after Phase 2.
- Visitor output on existing placements is unchanged — zero-config backward compatibility confirmed manually.
