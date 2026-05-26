# Shared Widget Config Layer (R-002) — Implementation Plan

## Overview

Build the shared PHP config layer that all four widget slices (R-003–R-006) depend on: a `FieldConfig` value object, a `FieldSorter` utility, and the full 4-primitive config UI wired into `EventMetaWidget` as the tenant-visible proof of concept. The visitor-facing render output is unchanged in this slice — R-005 wires `FieldSorter` into `EventMetaWidget`'s render path.

## Current State Analysis

- Three Elementor widgets exist: `ElementorWidget` (listing), `EventMetaWidget` (event detail), `EventSessionsWidget` (sessions).
- `EventMetaWidget` already has 11 SWITCHER visibility controls (`show_photos`, `show_location`, …) in a flat `registerContentSection()`. No priority, locked-value, or default-value controls exist anywhere.
- All six Elementor/WPBakery files in `src/Presentation/` are excluded from PHPStan. New pure PHP files in `src/Widget/` will be fully PHPStan level-8 covered.
- No existing "field config" or "priority sort" concept in the codebase. Closest analog: `EventMetaWidget`'s `show_*` SWITCHERs and `Transformer`'s readonly constructor pattern (the canonical pure PHP class shape to follow).
- Test pattern: Brain\Monkey + `PHPUnit\Framework\TestCase`, PHP 8.1 `#[Test]` attributes, `Brain\Monkey\setUp()` / `tearDown()` lifecycle.

### Key Discoveries

- `EventMetaWidget::render()` (`EventMetaWidget.php:197`) uses hardcoded section order: photos → location → tags → description → program → price_include → documents → terms → instructions → contact → custom_fields. This defines the zero-config priority defaults.
- Existing control IDs (`show_photos`, `show_location`, …) are the data keys stored in Elementor WP post meta per placement. Preserving these IDs in Phase 2 makes the migration lossless.
- `FieldSorter`'s settings-key convention derives from the field ID: visible = `show_{id}`, priority = `{id}_priority`, locked = `{id}_locked`, default = `{id}_default`.
- `phpstan.neon:12–18` excludes all six Elementor/WPBakery files. The two new `src/Widget/` files must NOT be added to `excludePaths`.

## Desired End State

- `src/Widget/FieldConfig.php` and `src/Widget/FieldSorter.php` exist, are fully PHPStan level-8 clean, and pass `npm run analyse`.
- `tests/Unit/Widget/FieldSorterTest.php` passes with `npm run test:unit`.
- `EventMetaWidget` in the Elementor editor shows a "Konfiguracja pól" section with all 11 fields, each exposing a visible SWITCHER, a priority NUMBER, a locked-value TEXT, and a default-value TEXT. Admin can set and save values.
- Existing widget placements render identically to before — visitor output is unchanged.

### Verification

- `npm run analyse` exits 0.
- `npm run lint` exits 0.
- `npm run test:unit` exits 0, new `FieldSorterTest` cases pass.
- Manual: open EventMetaWidget in Elementor editor, observe new config section with 11 × 4 controls.

## What We're NOT Doing

- Wiring `FieldSorter` into any widget's `render()` — that is R-003 (Search Panel), R-004 (Listing Card), R-005 (Event Detail), R-006 (Turnus Table).
- Adding config controls to `ElementorWidget` or `EventSessionsWidget` — those live in their respective slices.
- Creating the Search Panel widget — R-003.
- Changing CPT schema, sync pipeline, templates, or shortcodes.
- Writing a backward-compat invariant test (deferred; sort-behavior tests are sufficient for R-002).
- Adding any file to `phpstan.neon excludePaths` — new `src/Widget/` files are PHPStan-covered.

## Implementation Approach

Phase 1 is pure PHP — no WordPress, no Elementor — and is fully PHPStan-covered and unit-tested. Phase 2 modifies a PHPStan-excluded Elementor file and has a manual verification gate. The two phases are independent enough that Phase 1 can be committed and reviewed before Phase 2 begins.

## Critical Implementation Details

- **Settings-key naming convention**: FieldSorter derives settings keys from the field `id` string as `show_{id}` (visible SWITCHER), `{id}_priority` (NUMBER), `{id}_locked` (TEXT), `{id}_default` (TEXT). This convention must be followed identically in Phase 2's `add_control()` calls or FieldSorter will silently receive empty values.
- **Stable sort for tie-breaking**: PHP's `usort` is not guaranteed stable. When two fields share the same priority, `FieldSorter` must preserve the input order of `$fieldDefs`. Implement with a Schwartzian transform or explicit index-comparison fallback (`<=> $indexA <=> $indexB`).
- **Elementor HEADING control**: Elementor's `Controls_Manager::HEADING` renders a visual section divider with a label. Use it once per field, before the 4 data controls, to keep the 55-control section scannable in the editor panel.

---

## Phase 1: Pure PHP Config Layer

### Overview

Create `src/Widget/FieldConfig.php` (value object), `src/Widget/FieldSorter.php` (sort utility), and `tests/Unit/Widget/FieldSorterTest.php` (Brain\Monkey unit tests). No WordPress or Elementor dependencies. All three files are PHPStan level-8 covered.

### Changes Required

#### 1. `src/Widget/FieldConfig.php` (new file)

**File**: `src/Widget/FieldConfig.php`

**Intent**: Readonly value object representing the effective configuration of one field in one widget placement — the result of combining a field definition with the stored Elementor settings.

**Contract**: `final class FieldConfig` in namespace `Campsflow\Widget`. Readonly constructor properties: `string $id`, `string $label`, `bool $visible`, `int $priority`, `?string $lockedValue`, `?string $defaultValue`. No methods beyond construction. `declare(strict_types=1)`.

#### 2. `src/Widget/FieldSorter.php` (new file)

**File**: `src/Widget/FieldSorter.php`

**Intent**: Pure PHP utility that takes a widget's canonical field definition list and a raw Elementor settings array, returns visible fields sorted by priority ascending. No WordPress functions. No static state.

**Contract**: `final class FieldSorter` in namespace `Campsflow\Widget`. One public method:

```php
/**
 * @param array<int, array{id: string, label: string}> $fieldDefs
 * @param array<string, mixed>                          $settings
 * @return list<FieldConfig>
 */
public function sort(array $fieldDefs, array $settings): array
```

- For each entry in `$fieldDefs`: read `$settings["show_{$id}"]` for visibility (string `'yes'` = visible, anything else = hidden). Skip hidden fields.
- For visible fields: read `$settings["{$id}_priority"]` (cast to int, default 50 if absent), `$settings["{$id}_locked"]` (?string, `null` when empty string), `$settings["{$id}_default"]` (?string, `null` when empty string).
- Construct a `FieldConfig` for each visible field.
- Sort by `priority` ascending. Tie-breaking preserves `$fieldDefs` input order (stable). Return as `list<FieldConfig>`.

#### 3. `tests/Unit/Widget/FieldSorterTest.php` (new file)

**File**: `tests/Unit/Widget/FieldSorterTest.php`

**Intent**: Brain\Monkey unit tests verifying FieldSorter.sort() behavior across the four cases that matter for all future widget slices.

**Contract**: Namespace `Campsflow\Tests\Unit\Widget`, extends `PHPUnit\Framework\TestCase`, uses `Brain\Monkey\setUp()` / `tearDown()`, PHP 8.1 `#[Test]` attribute on each test method. Four test methods:

- `sort_returns_visible_fields_in_priority_order` — 3 fields with priority values [30, 10, 20], all visible → result IDs in order [id-with-10, id-with-20, id-with-30].
- `hidden_fields_are_excluded` — 3 fields, middle one has `show_{id}` = `''` → result contains only the 2 visible fields.
- `all_hidden_returns_empty_array` — all `show_{id}` = `''` → result is `[]`.
- `equal_priority_preserves_fieldDefs_input_order` — 2 visible fields with identical priority → result matches `$fieldDefs` input order, not reversed.

### Success Criteria

#### Automated Verification

- `npm run analyse` exits 0 — FieldConfig.php and FieldSorter.php are PHPStan level-8 clean (no new excludePaths added)
- `npm run lint` exits 0 — PHPCS clean for both new files
- `npm run test:unit` exits 0 — all 4 FieldSorterTest cases pass alongside existing 6 tests

#### Manual Verification

- Read `src/Widget/FieldConfig.php` and `src/Widget/FieldSorter.php` — confirm `declare(strict_types=1)`, correct namespace, no static state, no WP/Elementor dependencies

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual confirmation from the human before proceeding to Phase 2. Phase blocks use plain bullets — the corresponding `- [ ]` checkboxes for these items live in the `## Progress` section at the bottom of the plan.

---

## Phase 2: EventMetaWidget Config Section

### Overview

Replace `EventMetaWidget::registerContentSection()` with a loop-driven `registerConfigSection()` that emits 5 controls per field (HEADING + visible SWITCHER + priority NUMBER + locked TEXT + default TEXT) for all 11 fields. The section label changes from "Widoczność sekcji" to "Konfiguracja pól". The existing `show_{id}` control IDs are preserved so no stored Elementor settings are lost. `render()` is not touched.

### Changes Required

#### 1. `src/Presentation/EventMetaWidget.php`

**File**: `src/Presentation/EventMetaWidget.php`

**Intent**: Upgrade the content section from 11 flat SWITCHERs to the full 4-primitive config UI covering all 11 fields. This makes the widget the proof-of-concept for the shared config model without changing visitor output.

**Contract**:

In `register_controls()`, replace the call to `$this->registerContentSection()` with `$this->registerConfigSection()`.

Add private method `fieldDefs()` returning `array<int, array{id: string, label: string, default_visible: string, default_priority: int}>` with exactly these 11 entries in this order (matching today's hardcoded render order):

| id | label | default_visible | default_priority |
|---|---|---|---|
| `photos` | `Zdjęcia` | `''` | `10` |
| `location` | `Lokalizacja` | `'yes'` | `20` |
| `tags` | `Tagi` | `'yes'` | `30` |
| `description` | `Opis ogólny` | `'yes'` | `40` |
| `program` | `Program` | `''` | `50` |
| `price_include` | `Co zawiera cena` | `''` | `60` |
| `documents` | `Dokumenty` | `''` | `70` |
| `terms` | `Warunki ogólne` | `''` | `80` |
| `instructions` | `Inf. praktyczne` | `''` | `90` |
| `contact` | `Kontakt` | `''` | `100` |
| `custom_fields` | `Pola własne` | `''` | `110` |

Add private method `registerConfigSection()` that:
1. Opens a new section with ID `'section_field_config'`, label `__( 'Konfiguracja pól', 'campsflow' )`, tab `Controls_Manager::TAB_CONTENT`.
2. Iterates over `$this->fieldDefs()`. For each field:
   - `add_control("field_heading_{$id}", ['type' => Controls_Manager::HEADING, 'label' => $label, 'separator' => 'before'])`
   - `add_control("show_{$id}", ['type' => SWITCHER, 'label' => __('Widoczny', 'campsflow'), 'default' => $default_visible, 'label_on' => $yes, 'label_off' => $no])`
   - `add_control("{$id}_priority", ['type' => Controls_Manager::NUMBER, 'label' => __('Kolejność', 'campsflow'), 'min' => 1, 'max' => 999, 'step' => 1, 'default' => $default_priority])`
   - `add_control("{$id}_locked", ['type' => Controls_Manager::TEXT, 'label' => __('Zablokuj wartość', 'campsflow'), 'default' => ''])`
   - `add_control("{$id}_default", ['type' => Controls_Manager::TEXT, 'label' => __('Wartość domyślna', 'campsflow'), 'default' => ''])`
3. Closes the section.

Remove the old `registerContentSection()` method entirely.

`render()` is unchanged — it continues to read `$s['show_photos']`, `$s['show_location']`, etc. in hardcoded order. Since the `show_{id}` control IDs are preserved, existing Elementor placements that saved these settings are forward-compatible and render identically.

### Success Criteria

#### Automated Verification

- `npm run lint` exits 0 — no PHPCS regressions in `EventMetaWidget.php`
- `npm run test:unit` exits 0 — no regressions in existing 6 tests

#### Manual Verification

- Open EventMetaWidget in the Elementor editor on a page: the Content tab shows "Konfiguracja pól" section with 11 field groups, each with a visible SWITCHER, priority NUMBER, locked TEXT, and default TEXT control
- Set a non-default priority for one field, save the page, reload the editor — the saved value persists
- A widget placement saved before this change (if any) renders identically in the visitor view — no regression

**Implementation Note**: After completing this phase and all automated verification passes, pause here for manual confirmation from the human before proceeding.

---

## Testing Strategy

### Unit Tests

- `tests/Unit/Widget/FieldSorterTest.php` — 4 cases covering priority ordering, hidden exclusion, all-hidden edge case, tie-breaking stability.

### Integration Tests

None required for R-002 — no CPT writes, no WP_Query, no output change.

### Manual Testing Steps

1. `npm run env:start` (if not already running)
2. Open wp-admin → edit any page containing EventMetaWidget in Elementor
3. Click EventMetaWidget → Content tab → confirm "Konfiguracja pól" section present with all 11 fields × 4 controls
4. Change "Kolejność" (priority) for "Zdjęcia" from 10 to 5, save → reload editor → verify value is 5
5. Toggle "Widoczny" off for "Kontakt" → save → reload Elementor editor → confirm toggle state saved
6. Open the same page in the visitor view → confirm output is identical to before Phase 2 (sections render in the same order as before, contact section still visible since render() ignores the new controls)

## References

- Roadmap R-002: `context/foundation/roadmap.md`
- PRD FRs: `context/foundation/prd.md`
- EventMetaWidget source: `src/Presentation/EventMetaWidget.php`
- PHPStan config: `phpstan.neon`
- Test pattern reference: `tests/Unit/Sync/TransformerTest.php`

## Progress

> Convention: `- [ ]` pending, `- [x]` done. Append ` — <commit sha>` when a step lands. Do not rename step titles. See `references/progress-format.md`.

### Phase 1: Pure PHP Config Layer

#### Automated

- [x] 1.1 `npm run analyse` exits 0 — FieldConfig.php and FieldSorter.php PHPStan level-8 clean
- [x] 1.2 `npm run lint` exits 0 — PHPCS clean for new files
- [x] 1.3 `npm run test:unit` exits 0 — all 4 FieldSorterTest cases pass

#### Manual

- [x] 1.4 Read FieldConfig.php and FieldSorter.php — confirm strict_types, correct namespace, no WP/Elementor dependencies

### Phase 2: EventMetaWidget Config Section

#### Automated

- [ ] 2.1 `npm run lint` exits 0 — no PHPCS regressions in EventMetaWidget.php
- [ ] 2.2 `npm run test:unit` exits 0 — no regressions in existing 6 tests

#### Manual

- [ ] 2.3 Elementor editor shows "Konfiguracja pól" section with 11 fields × 4 controls
- [ ] 2.4 Priority value persists across editor save and reload
- [ ] 2.5 Visitor view unchanged — no rendering regression
