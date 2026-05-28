---
project: "Campsflow WP Plugin — Configurable Widgets"
version: 1
status: draft
created: 2026-05-23
context_type: brownfield
product_type: "# TODO: product_type — see Open Questions"
target_scale:
  users: "# TODO: target_scale — see Open Questions"
  qps: "# TODO"
  data_volume: "# TODO"
timeline_budget:
  delivery_weeks: 8
  hard_deadline: null
  after_hours_only: null
---

## Current System Overview

**System purpose:** WordPress plugin that syncs camp/retreat event data from Campsflow.pl API into local WordPress CPTs and renders them via shortcodes and Elementor widgets.

**Key architecture:** Three-layer pipeline — Synchronizer (Fetcher → Transformer → WpWriter → WP Cron), Presentation (WP_Query on local CPTs, shortcodes/Elementor widgets), Registration (dedicated WP page with Campsflow iframe). Plugin is a pure API client; no own backend.

**Tech stack:** PHP 8, WordPress (CPT: cf_event, cf_session; taxonomies: cf_tag, cf_age_group), Elementor widgets, WP Cron, Campsflow Public API.

**Current user base:** Multi-tenant — each SaaS tenant (camp organizer) installs the plugin on their own WordPress site. Content editors / site admins configure pages and widgets.

**Core functionality today:**
- Sync: pulls events + sessions from API, upserts into `cf_event` / `cf_session` CPTs, computes availability buckets
- Display: `[campsflow_listing view="events|sessions"]` shortcode and matching Elementor widget — hardcoded output, no layout control
- Registration: `[campsflow_registration_form]` embeds Campsflow iframe; `[campsflow_register_button]` links to it
- Elementor widgets render `custom_fields` alongside standard fields (recently added)

## Problem Statement & Motivation

The plugin's presentation layer has hardcoded layouts — the admin has no control over which data fields appear in the search/filter panel, which fields appear on listing cards, what order elements appear in, or which filters are pre-set/locked for a given placement. The result diverges sharply from the UX that tenants expect based on the reference implementation (camps4you.pl).

Tenants are pointing at camps4you.pl and asking "why doesn't ours look/work like that?" — specifically the 6-field search panel, the per-field predefined/locked values, and the configurable card layouts.

Tenants either accept the hardcoded output or resort to custom CSS/JS hacks. Both paths are brittle and require developer intervention for each tenant.

## User & Persona

**Primary persona — Site Admin / Content Editor:**
- Role: WordPress admin or content editor for a camp organizer tenant
- Context: Building out their public camp listings page using Elementor
- Moment they feel the pain: Opening the widget's configuration panel and finding no controls — can't hide irrelevant filters, can't pre-filter to their age group, can't reorder sections on an event detail page
- What they need: Widget configuration controls that let them toggle fields on/off, set display order, and define per-field default or locked values — without writing code

**Secondary persona — Camp visitor (end user):**
- Sees the result of admin configuration; benefits from a search panel pre-tuned to the tenant's offering (e.g., only summer camps, only age 6–9)
- No direct interaction with widget config

## Success Criteria

### Primary
- A site admin can open any of the 4 affected Elementor widgets, toggle individual fields on/off, set their display order via numbered priority inputs, and set per-field default or locked values — all from the widget's configuration panel, without touching code.
- The rendered front-end output reflects exactly the admin's configuration: locked fields show as a read-only label to visitors, defaulted fields are pre-filled (visitor can override), field order matches the configured priority order.

### Secondary
- Search panel pre-filtering works using the event taxonomy and location data already present in the plugin's local data store — no new API data fields are required for v1.
- The existing data sync and registration embed are unaffected by the widget changes.

### Guardrails
- The data synchronization pipeline must not be modified by this change; its output is the data source for all widgets.
- The registration page embed flow must continue to work unchanged.
- No regression for tenants who do not modify their widget configuration — zero-config placement renders identically to the current hardcoded output.
- Widget configuration persists correctly across widget saves and page reloads, including full cache clears.
- The core listing, event detail, and turnus table render without JavaScript; the search panel may require JavaScript for interactive filtering.

## User Stories

# TODO: User Stories — no Given/When/Then acceptance-criteria stories were captured during shaping. To be written before implementation begins. See Open Questions entry 1.

## Scope of Change

### Search Panel Widget

- FR-001: Admin can toggle each search filter field (Kierunek, Termin, Wiek, Dla Kogo, Transport, Profil) visible or hidden from the widget's configuration panel. Priority: must-have. Change: new
  > Socrates: Counter-argument considered: "CSS hiding achieves the same result." Resolution: rejected — CSS hacking is exactly the pain being solved; proper toggle is must-have.

- FR-002: Admin can set the display order of search filter fields via a numbered priority input in the widget's configuration panel. Priority: must-have. Change: new
  > Socrates: Counter-argument considered: "drag-and-drop in Elementor repeater is complex vs. a numbered priority field." Resolution: accepted simplification — numbered priority field for v1; drag-and-drop deferred to v2.

- FR-003: Admin can lock a filter field to a specific value; the locked value is applied to results and the field displays as a read-only label visible to the visitor. Priority: must-have. Change: new
  > Socrates: Counter-argument considered: "hidden locked filter leaves visitor confused when results are narrow." Resolution: weakened — locked fields show a read-only label so visitor understands the active constraint.

- FR-004: Admin can set a default value for a filter field that the visitor sees pre-filled but can override. Priority: must-have. Change: new
  > Socrates: Counter-argument considered: "system should validate that a default produces results before admin saves." Resolution: rejected — admin responsibility; not a system concern for v1.

### Listing Card Widget

- FR-005: Admin can toggle individual card fields on/off (title, dates, price, availability badge, location, tags) from the widget's configuration panel; toggling price off shows a warning in the configuration panel. Priority: must-have. Change: modified
  > Socrates: Counter-argument considered: "hiding price causes sticker shock at registration." Resolution: strengthened — toggle is allowed but the configuration panel shows an advisory when price is disabled.

- FR-006: Admin can set the display order of card fields via a numbered priority input in the widget's configuration panel. Priority: must-have. Change: new
  > Socrates: Same drag-and-drop vs. numbered priority consideration as FR-002. Resolution: consistent — numbered priority for v1.

- FR-007: Admin can select card layout (card grid for v1). Priority: must-have. Change: modified
  > Socrates: Counter-argument considered: "multiple card templates multiply CSS/template maintenance." Resolution: scoped to card layout only for v1; list layout is nice-to-have for v2.

- FR-008: Admin can set items-per-page count and use paginated pagination. Priority: must-have. Change: new. Infinite scroll: nice-to-have.
  > Socrates: Counter-argument considered: "infinite scroll adds significant JS complexity." Resolution: paginated only for v1; infinite scroll demoted to nice-to-have.

- FR-009: Admin can control which badges appear on the card image via the widget's configuration panel; badge visual styling (color, size) is controlled via the page builder's native style panel. Priority: must-have. Change: new
  > Socrates: Counter-argument considered: "badge position/style are CSS concerns." Resolution: split — visibility/which badges via PHP config; CSS via Elementor styling panel.

### Event Detail Page Widget

- FR-010: Admin can toggle content sections on/off (description, accommodation, activities, gallery, documents) from the widget's configuration panel. Priority: must-have. Change: modified
  > Socrates: Counter-argument considered: "some sections contain legally required disclosures." Resolution: admin owns legal compliance; no sections locked by the plugin.

- FR-011: Admin can set the display order of content sections via a numbered priority input in the widget's configuration panel. Priority: must-have. Change: new
  > Socrates: Same drag-and-drop vs. numbered priority. Resolution: consistent — numbered priority for v1.

- FR-012: Admin can control which turnus summary fields (next date, price range, availability status) appear in the event hero/header area. Priority: must-have. Change: new
  > Socrates: Counter-argument about data freshness. Resolution: freshness is the sync pipeline's responsibility; widget reads what's in CPT meta — no special freshness handling needed.

- FR-013: Admin can toggle the context-aware related camps section on/off; when on, the section surfaces camps matching the visitor's active search parameters that led them to the current event page. Priority: must-have. Change: new
  > Socrates: Counter-argument considered: "'related' needs a rule or it's random." Resolution: rule is context-aware — recommendations are derived from the visitor's active search query parameters (not a static tag match). Business logic defined in Business Logic Changes section.

- FR-014: Admin can customize the display title of each content section independently per widget placement. Priority: must-have. Change: new
  > Socrates: Counter-argument considered: "per-section titles multiply string maintenance." Resolution: kept — tenants use different terminology (e.g. 'Co robimy' vs 'Program'); this is a real, confirmed need.

### Turnus Table Widget

- FR-015: Admin can toggle turnus table columns on/off (dates, price, availability status, register button). Priority: nice-to-have. Change: modified
  > Socrates: Counter-argument considered: "every column is decision-relevant; toggle may not be needed." Resolution: demoted to nice-to-have for v1; reorder (FR-016) is the primary must-have.

- FR-016: Admin can set the display order of turnus table columns via a numbered priority input in the widget's configuration panel. Priority: must-have. Change: new
  > Socrates: Consistent with FR-002, FR-006, FR-011 — numbered priority for v1.

- FR-017: Tenant can customize availability status display (label text, color, icon style) globally via the plugin's settings page; this applies to all widgets site-wide. Priority: must-have. Change: modified
  > Socrates: Counter-argument considered: "per-widget bucket display multiplies config." Resolution: global setting only — consistent across all widgets on the site; moved from per-widget to settings page.

- FR-018: V1 turnus table displays dates, price, availability status, and register button. Transport type, pickup location, and return-to location columns are deferred to v2 pending Campsflow API extension. Priority: must-have (v1 scope). Change: modified
  > Socrates: Counter-argument about filtering which turnusy appear. Resolution: clarified — closed/expired turnusy are already removed by the sync pipeline (trashed when absent from API); no table-level filter needed for v1. Transport/pickup/return fields are a v2 API dependency.

## Constraints & Compatibility

**Backward compatibility:**
- Old shortcodes (`[campsflow_listing]`, `[campsflow_register_button]`, `[campsflow_registration_form]`) can be deprecated — tenants accept migration
- Registration embed URL format is frozen
- The sync pipeline's core components (Fetcher, Transformer, WpWriter, SyncScheduler) are not modified for v1 widget delivery; however, the API contract JSON shape for new turnus fields (transport, pickup, return-to location) and corresponding test fixture updates are in scope as a parallel track
- Sync pipeline extensions for those new fields ship when the Campsflow API team delivers the new fields

**Data migration:** None — widget configuration is stored per placement in Elementor widget settings; no CPT data changes.

**Existing integrations that must keep working:**
- Campsflow Public API sync contract (`GET /api/v1/public/{tenantSlug}/events`)
- Scheduled sync job
- Registration form embed on `/rejestracja/`

**Preserved behavior:** Any widget placement with no new configuration set must render identically to the current hardcoded output (zero-config = backward-compatible default).

**Config schema stability:** Widget configuration schema must be backward-compatible across plugin updates; an upgrade must not silently reset any admin-configured field order or toggle state.

## Business Logic Changes

When a visitor views an event detail page, the plugin reads their active search parameters from the page URL and surfaces other events whose type, age group, and location match those parameters as context-aware related camp recommendations.

Supporting detail:
- No ranking algorithm — pure attribute matching against the visitor's active search context.
- The existing availability display rule (event availability reflects the best turnus availability) is unchanged; this change only reads the already-computed value.
- All other widget behavior (field toggle, order, locked/defaulted values) is display configuration — no new domain decision is introduced.

## Access Control Changes

No access control changes — current model preserved.

Widget configuration is managed through the page builder's control panel using standard WordPress admin capability gates. Visitors see the rendered output only.

## Non-Goals

- Not implementing sync pipeline extensions for transport, pickup, and return-to location fields in v1 — those turnus columns wait for the Campsflow API extension; plugin-side changes ship when the API team delivers the new fields.
- Not building a drag-and-drop reorder UI — numbered priority input is used across all 4 widgets for v1; drag-and-drop deferred to v2.
- Not building a list-layout card variant — card grid layout only for v1; list view is v2.
- Not adding infinite scroll — paginated pagination only; v2 concern.
- Not changing the registration embed flow — the registration page embed and URL format are frozen.
- Not targeting alternative page builders — all widget configuration is delivered through Elementor's native widget panel; no other page builder target in this change.

## Open Questions

1. **User Stories (Given/When/Then)** — No acceptance-criteria stories were written during shaping. To be written before implementation begins. Block: no (FRs cover scope; stories add acceptance-criteria clarity).
2. **TRANSPORT and DLA KOGO search filter field mapping** — Do these filter fields map to existing event taxonomy and location data, or require new data fields in the sync pipeline? Owner: user + Campsflow API team. Block: partial — implementation can start with existing data; new fields require a separate API extension track.
3. **Exact field list per widget** — The FRs name field categories (dates, price, availability, tags, etc.) but precise configuration control identifiers need to be specified during implementation planning. Block: no (implementation concern, not PRD scope).
4. **`product_type`** — Not captured during shaping. This plugin targets WordPress with Elementor; closest enum: `other (WordPress plugin)`. To be confirmed. Block: no.
5. **`target_scale`** — Not captured during shaping. Tenant count and expected visitor load unspecified. Block: no (doesn't affect implementation scope for this change).
