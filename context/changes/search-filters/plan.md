---
change_id: search-filters
title: AJAX search and filter for events listing
status: planned
created: 2026-05-27
updated: 2026-05-27
---

# Plan: AJAX search and filter for events listing

## Overview

Add a configurable search/filter UI to the events listing. The filter form (category, age group, destination, transport type, event class, date window) submits via a REST endpoint that returns a JSON `{html}` fragment. A results container swaps content with a skeleton loader during fetch. Two Elementor widgets and WPBakery equivalents are delivered in Phases 5–6, after R-002 (`widget-config-layer`) is `impl_reviewed`.

## Desired End State

- REST endpoint `GET /wp-json/campsflow/v1/events` returns `{"html": "..."}` with matching event card HTML.
- `ListingShortcode` renders `<form class="cf-search-form">` with up to 6 filter fields and a paired `<div class="cf-search-results">`.
- Vanilla JS `assets/js/search-filters.js` intercepts form changes, shows a skeleton loader, fetches the endpoint, and replaces the results container contents.
- Two new taxonomies synced: `cf_destination` (hierarchical: country ISO → region name) and `cf_transport_type`.
- Event meta: `cf_min_age`, `cf_max_age`, `cf_date_earliest` kept in sync.
- Age groups (`cf_age_group`) computed from configurable thresholds (WP Admin → Campsflow → Ustawienia): Dzieci / Młodzież / Dorośli.
- Elementor `SearchFilterWidget` + `SearchResultsWidget` and WPBakery shortcode equivalents (Phases 5–6, blocked by R-002).

## Current State Analysis

- `cf_age_group` taxonomy exists but stores raw "10–16 lat" string labels; no configurable thresholds.
- No `cf_destination` or `cf_transport_type` taxonomies registered.
- No `cf_min_age`, `cf_max_age`, `cf_date_earliest` event meta.
- No REST endpoint.
- No AJAX/search JS.
- R-002 (`widget-config-layer`) Phase 2 pending manual verification — widgets in Phases 5–6 must wait.

## What We're NOT Doing

- No session-level search (event-level only).
- No full-text keyword search.
- No pagination in the REST endpoint (all matching events, up to 50).
- `cf_date_latest` / `cf_date_to` meta not added — date filter uses `cf_date_earliest` for both bounds (events whose earliest session falls within the requested window).
- Phases 5–6 not implemented until R-002 is `impl_reviewed`.

## Architecture Decisions

| Decision | Choice | Rationale |
|---|---|---|
| Response format | JSON `{html: "..."}` | WP REST API serialises response data as JSON; extract `.html` on JS side |
| JS approach | Vanilla JS IIFE, new file | No build pipeline; consistent with existing codebase |
| `cf_destination` | Hierarchical taxonomy (parent = country ISO, child = destination name) | Enables filtering by country or region independently |
| `eventClass` filter | Meta `cf_event_class` exact match | Already synced; no new taxonomy needed |
| Age groups | Configurable thresholds → fixed terms Dzieci / Młodzież / Dorośli | Three terms suffice for the filter; raw ages stored in `cf_min_age`/`cf_max_age` |
| Date filter | Both bounds use `cf_date_earliest` (min dateFrom) | Single meta key, `meta_query` with `type => DATE`; approximate but correct for MVP |
| Widget topology | Two separate widgets: SearchFilter + SearchResults | Flexible layout — filter in sidebar, results spanning content area |
| Shared card renderer | Extract `EventCardRenderer` from `ListingShortcode` | Prevents duplication between shortcode and REST endpoint |

---

## Phase 1: Sync Extensions

### Overview

Register `DestinationTaxonomy` and `TransportTypeTaxonomy`. Save `cf_min_age`, `cf_max_age`, `cf_date_earliest` on events. Replace the raw "10–16 lat" age group term with configurable threshold-based terms (Dzieci / Młodzież / Dorośli). Add destination and transport type term assignment in sync.

### Changes Required

- **NEW** `src/Taxonomy/DestinationTaxonomy.php`
  - `SLUG = 'cf_destination'`
  - Hierarchical (`'hierarchical' => true`); attached to `cf_event`; read-only capabilities (same pattern as `AgeGroupTaxonomy`)
  - rewrite slug: `kierunek`; `show_admin_column => true`; `show_in_rest => true`

- **NEW** `src/Taxonomy/TransportTypeTaxonomy.php`
  - `SLUG = 'cf_transport_type'`
  - Non-hierarchical; attached to `cf_event`; read-only capabilities
  - rewrite slug: `transport`; `show_admin_column => true`; `show_in_rest => true`

- **MOD** `campsflow.php:71–90`
  - Register both new taxonomy classes alongside existing ones (`new DestinationTaxonomy()`, `new TransportTypeTaxonomy()`)

- **MOD** `src/Sync/SyncRunner.php`
  - `saveEventMeta()`: add three new meta saves:
    - `cf_min_age` = `(int)$event['minAge']` (only when isset)
    - `cf_max_age` = `(int)$event['maxAge']` (only when isset)
    - `cf_date_earliest`: compute `$dates = array_filter(array_map(fn($t) => (string)($t['dateFrom'] ?? ''), $event['turnusy'] ?? []))`, then save `$dates ? min($dates) : ''` — guards against empty array (PHP 8 `min([])` throws `ValueError`)
  - `setAgeGroupTerms()`: replace `$minAge . '–' . $maxAge . ' lat'` with threshold-based logic:
    - `$childMax = (int) get_option('campsflow_age_child_max', 12)`
    - `$youthMax = (int) get_option('campsflow_age_youth_max', 17)`
    - Overlap check: `[minAge, maxAge]` overlaps `[4, childMax]` → push `__('Dzieci', 'campsflow')`
    - Overlap check: `[minAge, maxAge]` overlaps `[childMax + 1, youthMax]` → push `__('Młodzież', 'campsflow')`
    - `$maxAge > $youthMax` → push `__('Dorośli', 'campsflow')`
    - `wp_set_object_terms($postId, $groups, AgeGroupTaxonomy::SLUG)`
  - **ADD** `setDestinationTerms(int $postId, array $event): void`:
    - Extract `$country = (string)($event['localization']['address']['country'] ?? '')`
    - Extract `$destination = (string)($event['localization']['destination'] ?? '')`
    - If no destination: `wp_set_object_terms($postId, [], DestinationTaxonomy::SLUG); return`
    - Helper inline: `$termId = function(mixed $r): int { return is_wp_error($r) ? 0 : (int)(is_array($r) ? $r['term_id'] : $r); }`
    - Ensure parent term: `$parentId = $termId(term_exists($country, SLUG) ?? wp_insert_term($country, SLUG))`
    - Ensure child term with parent: `$childId = $termId(term_exists($destination, SLUG, $parentId) ?? wp_insert_term($destination, SLUG, ['parent' => $parentId]))`
    - If `$childId === 0`: bail silently; otherwise `wp_set_object_terms($postId, [$childId], DestinationTaxonomy::SLUG)`
  - **ADD** `setTransportTypeTerms(int $postId, array $event): void`:
    - Collect unique `$turnus['transport']['type']` strings across `$event['turnusy']`
    - `wp_set_object_terms($postId, $types, TransportTypeTaxonomy::SLUG)`
  - `upsertEvent()`: call `setDestinationTerms()` and `setTransportTypeTerms()` after existing taxonomy calls

- **MOD** `src/Admin/SyncNotice.php:MANAGED_TAXONOMIES`
  - Add `DestinationTaxonomy::SLUG` and `TransportTypeTaxonomy::SLUG`
  - Add `use` imports for both taxonomy classes

### Success Criteria

#### Automated Verification:
- `npm run test:integration` passes
- `npm run analyse` passes (PHPStan level 8)
- `npm run lint` passes (PHPCS)

#### Manual Verification:
- "Synchronizuj teraz" → admin column `cf_destination` shows "Bieszczady", "Wybrzeże Zachodniopomorskie", "Tatry" for the three fixture events
- Admin column `cf_transport_type` shows "bus" for all three events
- Admin column `cf_age_group` shows "Dzieci, Młodzież" for Bieszczady (10–16, defaults child_max=12 youth_max=17)
- Admin column `cf_age_group` shows "Młodzież" only for Surf Camp (13–17)
- `get_post_meta($id, 'cf_date_earliest', true)` = `"2026-06-27"` for Bieszczady

---

## Phase 2: Settings Extensions

### Overview

Add an "Age group thresholds" section to the Settings tab. Two new WP options: `campsflow_age_child_max` (default 12) and `campsflow_age_youth_max` (default 17).

### Changes Required

- **MOD** `src/Admin/SettingsPage.php`
  - `registerSettings()`: add to `campsflow_settings` group:
    - `campsflow_age_child_max` default `'12'`, sanitize: `'absint'`
    - `campsflow_age_youth_max` default `'17'`, sanitize: `'absint'`
  - `renderSettingsTab()`: add section after availability thresholds:
    - `<h3>Grupy wiekowe — progi wiekowe</h3>`
    - Description: "Określ progi wiekowe dla grup Dzieci / Młodzież / Dorośli."
    - Two number inputs (`min="1" max="99"`): `campsflow_age_child_max` (label: "Dzieci: do X lat"), `campsflow_age_youth_max` (label: "Młodzież: X+1 – Y lat, Dorośli: powyżej Y lat")

### Success Criteria

#### Automated Verification:
- `npm run analyse` passes
- `npm run lint` passes

#### Manual Verification:
- WP Admin → Campsflow → Ustawienia → Settings tab shows two age threshold fields with defaults 12 and 17
- Change `child_max` to 10, save, re-sync → Bieszczady (10–16) age groups update accordingly

---

## Phase 3: REST Endpoint + Shared Renderer

### Overview

Extract event card rendering from `ListingShortcode` into `EventCardRenderer`. Create `EventsEndpoint` that builds `WP_Query` from URL params and returns `{"html": "..."}`. Register the endpoint in plugin bootstrap. Adapt `ListingShortcode` to add classes and data attributes needed by Phase 4 JS.

### Changes Required

- **NEW** `src/Presentation/EventCardRenderer.php`
  - `renderCard(int $eventId): string` — HTML for one `<article class="cf-card">` (extracted from `ListingShortcode::renderEventCard()`)
  - `renderSessionRow(int $sessionId): string` — HTML for one `<li class="cf-session">` (extracted from `ListingShortcode::renderSessionRow()`)
  - `renderGrid(array $postIds): string` — wraps rendered cards in `<div class="cf-grid">`
  - `renderEmpty(): string` — `<p class="cf-empty">Brak wydarzeń spełniających kryteria.</p>`

- **NEW** `src/Api/EventsEndpoint.php`
  - `register()`: `add_action('rest_api_init', [$this, 'registerRoute'])`
  - `registerRoute()`: registers `campsflow/v1` namespace, route `/events`, GET, `permission_callback => '__return_true'`
  - `handle(WP_REST_Request $request): WP_REST_Response`:
    - Accepted params (all optional, sanitised with `sanitize_text_field`): `category`, `age`, `destination`, `transport`, `eventClass`, `dateFrom`, `dateTo`
    - Build `$taxQuery` (relation `AND`) for non-empty taxonomy params:
      - `category` → `cf_event_category`, field `slug`
      - `age` → `cf_age_group`, field `slug`
      - `destination` → `cf_destination`, field `slug`
      - `transport` → `cf_transport_type`, field `slug`
    - Build `$metaQuery` for non-empty meta params:
      - `eventClass` → `['key' => 'cf_event_class', 'value' => $val, 'compare' => '=']`
      - `dateFrom` → `['key' => 'cf_date_earliest', 'value' => $val, 'compare' => '>=', 'type' => 'DATE']`
      - `dateTo` → `['key' => 'cf_date_earliest', 'value' => $val, 'compare' => '<=', 'type' => 'DATE']`
    - Run `WP_Query` with `post_type => 'cf_event'`, `post_status => 'publish'`, `posts_per_page => 50`, `orderby => 'title'`, `order => 'ASC'`
    - Render result with `EventCardRenderer`; return `new WP_REST_Response(['html' => $html], 200)`

- **MOD** `campsflow.php:85–90`
  - Register `new EventsEndpoint()` and call `->register()`

- **MOD** `src/Presentation/ListingShortcode.php`
  - `renderEventCard()`: delegate to `new EventCardRenderer()->renderCard($eventId)`
  - `renderSessionRow()`: delegate to `new EventCardRenderer()->renderSessionRow($sessionId)`
  - `renderEventsView()`: use `EventCardRenderer::renderGrid()` / `renderEmpty()`
  - `render()`: wrap results section in `<div class="cf-search-results">`
  - `renderFilters()`: change `<form class="cf-filters"` to `<form class="cf-search-form cf-filters"` and add `data-endpoint="<?php echo esc_url(rest_url('campsflow/v1/events')); ?>"` attribute

### Success Criteria

#### Automated Verification:
- `npm run test:integration` passes
- `npm run analyse` passes
- `npm run lint` passes

#### Manual Verification:
- `curl http://localhost:8890/wp-json/campsflow/v1/events` returns JSON `{"html":"<div class=\"cf-grid\">..."}`
- `curl "http://localhost:8890/wp-json/campsflow/v1/events?category=active"` returns only events with that category
- `curl "http://localhost:8890/wp-json/campsflow/v1/events?destination=bieszczady"` returns only Bieszczady event
- `curl "http://localhost:8890/wp-json/campsflow/v1/events?eventClass=YOUTH_CAMP"` returns all fixture events
- No PHP notices in `npm run env:logs`

---

## Phase 4: Vanilla JS

### Overview

Add `assets/js/search-filters.js` (IIFE, no build step). Intercepts `change` events on `cf-search-form`, debounces 300 ms, shows skeleton, fetches the REST endpoint, replaces `cf-search-results` contents. Enqueue from `ListingShortcode`. Add skeleton CSS.

### Changes Required

- **NEW** `assets/js/search-filters.js`
  - IIFE; find `form.cf-search-form` and the sibling-or-descendent `div.cf-search-results` (use `document.querySelector` by class; bail if absent)
  - Read endpoint URL from `form.dataset.endpoint`
  - `showSkeleton()`: render 3 `<div class="cf-skeleton__card"></div>` inside a `<div class="cf-skeleton">` in the results container
  - `doSearch()`: `fetch(endpoint + '?' + new URLSearchParams(new FormData(form)).toString())` → `.then(r => r.json())` → `.then(data => { results.innerHTML = data.html; })` → `.catch(() => { results.innerHTML = '<p class="cf-error">Błąd wyszukiwania. Spróbuj ponownie.</p>'; })`
  - Debounce 300 ms on `select` and `checkbox` `change` events; 500 ms on `input[type=date]` `input` events
  - Prevent default `submit` event and call `doSearch()`

- **MOD** `assets/css/campsflow.css` (append at end)
  - `.cf-skeleton` — grid matching `--cf-columns`
  - `.cf-skeleton__card` — grey rounded rectangle, 200 px height, shimmer animation (`@keyframes cf-shimmer`)

- **MOD** `campsflow.php` (wp_enqueue_scripts closure, alongside existing CSS enqueue)
  - Add `wp_enqueue_script('campsflow-search-filters', CAMPSFLOW_PLUGIN_URL . 'assets/js/search-filters.js', [], CAMPSFLOW_VERSION, true)`

### Success Criteria

#### Automated Verification:
- `npm run analyse` passes
- `npm run lint` passes

#### Manual Verification:
- Category dropdown change → results update without page reload, skeleton appears for ≥100 ms
- Destination dropdown change → results filter to matching event(s)
- "Wyjazd po" date input → results filter by `cf_date_earliest`
- JS disabled (devtools) → form submits via GET, results update after page reload
- No console errors

---

## Phase 5: Elementor Widgets (blocked — wait for R-002 impl_reviewed)

### Overview

Two new Elementor widgets: `SearchFilterWidget` and `SearchResultsWidget`. Each widget uses `FieldConfig` / `FieldSorter` from R-002 for per-field visibility, order, and default configuration. The filter widget renders `<form class="cf-search-form">` with configurable fields; the results widget renders `<div class="cf-search-results">` with initial query results.

### Changes Required

- **NEW** `src/Presentation/SearchFilterWidget.php`
  - Extends `\Elementor\Widget_Base`; name: `campsflow-search-filter`; title: "CampsFlow: Filtr wyszukiwania"
  - Controls: one toggle + priority control per filter field (category, age, destination, transport, event_class, date_from, date_to) using `FieldSorter`
  - `render()`: instantiate `FieldSorter`, get ordered `FieldConfig[]`, render visible fields in order inside `<form class="cf-search-form" data-endpoint="...">`

- **NEW** `src/Presentation/SearchResultsWidget.php`
  - Extends `\Elementor\Widget_Base`; name: `campsflow-search-results`; title: "CampsFlow: Wyniki wyszukiwania"
  - Controls: columns (RESPONSIVE NUMBER), accent color, gap (same pattern as `ElementorWidget`)
  - `render()`: render `<div class="cf-search-results" style="--cf-columns:{n}">` with initial cards from `EventCardRenderer`

- **MOD** `src/Presentation/ElementorIntegration.php`
  - Register both new widgets via `\Elementor\Plugin::$instance->widgets_manager->register()`

### Success Criteria

#### Automated Verification:
- `npm run analyse` passes
- `npm run lint` passes

#### Manual Verification:
- Both widgets appear in Elementor editor under "CampsFlow" category
- Toggle a filter field off in Elementor controls → field absent from rendered form
- Add both widgets to a page → AJAX search works end-to-end

---

## Phase 6: WPBakery Widgets (blocked — wait for R-002 impl_reviewed)

### Overview

WPBakery equivalents of Phase 5 widgets. Two new shortcodes registered via `WpBakeryIntegration`.

### Changes Required

- **NEW** `src/Presentation/SearchFilterShortcode.php`
  - `[campsflow_search_filter fields="category,age,destination" ]`
  - `render()` outputs same `<form class="cf-search-form">` HTML as `SearchFilterWidget`

- **NEW** `src/Presentation/SearchResultsShortcode.php`
  - `[campsflow_search_results columns="3"]`
  - `render()` outputs same `<div class="cf-search-results">` as `SearchResultsWidget`

- **MOD** `src/Presentation/WpBakeryIntegration.php`
  - Register two `vc_map()` entries for both shortcodes with appropriate params

- **MOD** `campsflow.php`
  - Register `SearchFilterShortcode` and `SearchResultsShortcode`

### Success Criteria

#### Automated Verification:
- `npm run analyse` passes
- `npm run lint` passes

#### Manual Verification:
- Both shortcodes appear in WPBakery element picker under CampsFlow group
- Add both to a WPBakery page → AJAX search works end-to-end

---

## Progress

### Phase 1: Sync Extensions

#### Automated
- [x] 1.1 Create DestinationTaxonomy — SLUG=cf_destination, hierarchical, read-only caps, rewrite slug=kierunek — 4a9d832
- [x] 1.2 Create TransportTypeTaxonomy — SLUG=cf_transport_type, non-hierarchical, read-only caps, rewrite slug=transport — 4a9d832
- [x] 1.3 Register both in campsflow.php — 4a9d832
- [x] 1.4 SyncRunner::saveEventMeta — save cf_min_age, cf_max_age, cf_date_earliest — 4a9d832
- [x] 1.5 SyncRunner::setAgeGroupTerms — configurable threshold logic (Dzieci/Młodzież/Dorośli) — 4a9d832
- [x] 1.6 SyncRunner::setDestinationTerms — parent=country ISO, child=destination name, wp_insert_term guard — 4a9d832
- [x] 1.7 SyncRunner::setTransportTypeTerms — collect unique transport.type strings from turnusy — 4a9d832
- [x] 1.8 SyncRunner::upsertEvent — call setDestinationTerms + setTransportTypeTerms — 4a9d832
- [x] 1.9 SyncNotice — add DestinationTaxonomy::SLUG + TransportTypeTaxonomy::SLUG to MANAGED_TAXONOMIES — 4a9d832
- [x] 1.10 PHPStan level 8 + PHPCS clean — 4a9d832
- [x] 1.11 Integration tests pass — 4a9d832

#### Manual
- [x] 1.12 Sync fixtures → cf_destination admin column shows Bieszczady, Wybrzeże Zachodniopomorskie, Tatry — 4a9d832
- [x] 1.13 cf_transport_type = "bus" for all three fixture events — 4a9d832
- [x] 1.14 cf_age_group = "Dzieci, Młodzież" for Bieszczady (10–16, defaults child_max=12 youth_max=17) — 4a9d832
- [x] 1.15 cf_age_group = "Młodzież" only for Surf Camp (13–17) — 4a9d832
- [x] 1.16 cf_date_earliest = "2026-06-27" for Bieszczady — 4a9d832

### Phase 2: Settings Extensions

#### Automated
- [x] 2.1 SettingsPage::registerSettings — add campsflow_age_child_max (default '12', absint) + campsflow_age_youth_max (default '17', absint) — a2dfc91
- [x] 2.2 SettingsPage::renderSettingsTab — add age threshold section with two number inputs — a2dfc91
- [x] 2.3 PHPStan level 8 + PHPCS clean — a2dfc91

#### Manual
- [x] 2.4 Settings tab shows age threshold fields with defaults 12 and 17 — a2dfc91
- [x] 2.5 Change thresholds, save, re-sync → cf_age_group terms update — a2dfc91

### Phase 3: REST Endpoint + Shared Renderer

#### Automated
- [x] 3.1 Create EventCardRenderer — renderCard(), renderSessionRow(), renderGrid(), renderEmpty()
- [x] 3.2 ListingShortcode — delegate renderEventCard + renderSessionRow to EventCardRenderer
- [x] 3.3 ListingShortcode — wrap results in cf-search-results div, add data-endpoint + cf-search-form class to form
- [x] 3.4 Create EventsEndpoint — registerRoute, handle() with WP_Query + JSON {html} response
- [x] 3.5 Register EventsEndpoint in campsflow.php
- [x] 3.6 PHPStan level 8 + PHPCS clean
- [x] 3.7 Integration tests pass

#### Manual
- [x] 3.8 GET /wp-json/campsflow/v1/events returns {html: "<div class=\"cf-grid\">..."}
- [x] 3.9 category, destination, eventClass params filter results correctly
- [x] 3.10 No PHP notices in env logs

### Phase 4: Vanilla JS

#### Automated
- [ ] 4.1 Create assets/js/search-filters.js — IIFE, debounce, skeleton, fetch+json, error state
- [ ] 4.2 Add .cf-skeleton + .cf-skeleton__card shimmer styles to assets/css/campsflow.css
- [ ] 4.3 campsflow.php wp_enqueue_scripts — add search-filters.js enqueue (CAMPSFLOW_PLUGIN_URL)
- [ ] 4.4 PHPStan + PHPCS clean

#### Manual
- [ ] 4.5 Filter change → AJAX update with skeleton visible
- [ ] 4.6 Date filter → results filter by cf_date_earliest
- [ ] 4.7 JS disabled → GET fallback works

### Phase 5: Elementor Widgets (blocked — wait for R-002 impl_reviewed)

#### Automated
- [ ] 5.1 Create SearchFilterWidget — FieldSorter controls, render()
- [ ] 5.2 Create SearchResultsWidget — columns + style controls, render() via EventCardRenderer
- [ ] 5.3 ElementorIntegration — register both widgets
- [ ] 5.4 PHPStan + PHPCS clean

#### Manual
- [ ] 5.5 Both widgets appear in Elementor editor CampsFlow category
- [ ] 5.6 End-to-end AJAX search on Elementor page

### Phase 6: WPBakery Widgets (blocked — wait for R-002 impl_reviewed)

#### Automated
- [ ] 6.1 Create SearchFilterShortcode
- [ ] 6.2 Create SearchResultsShortcode
- [ ] 6.3 WpBakeryIntegration — register both shortcodes via vc_map
- [ ] 6.4 Register shortcodes in campsflow.php
- [ ] 6.5 PHPStan + PHPCS clean

#### Manual
- [ ] 6.6 Both shortcodes appear in WPBakery element picker
- [ ] 6.7 End-to-end AJAX search on WPBakery page
