---
change_id: search-filters
type: plan-brief
---

# Plan Brief: AJAX search and filter for events listing

## What

6-phase implementation of filterable events listing:

| Phase | What | Status |
|---|---|---|
| 1 | Sync extensions: `cf_destination` + `cf_transport_type` taxonomies, `cf_min_age`/`cf_max_age`/`cf_date_earliest` meta, age groups rework | unblocked |
| 2 | Settings: configurable age group thresholds (child_max/youth_max) | unblocked |
| 3 | REST endpoint `GET /wp-json/campsflow/v1/events` + `EventCardRenderer` extraction | unblocked |
| 4 | Vanilla JS `assets/js/search-filters.js` — debounce, skeleton loader, fetch | unblocked |
| 5 | Elementor SearchFilterWidget + SearchResultsWidget | **blocked: R-002** |
| 6 | WPBakery SearchFilterShortcode + SearchResultsShortcode | **blocked: R-002** |

## Key contracts

**REST endpoint**: `GET /wp-json/campsflow/v1/events`
- Params: `category`, `age`, `destination`, `transport`, `eventClass`, `dateFrom`, `dateTo` (all optional, all slugs/strings)
- Returns: `{"html": "<div class=\"cf-grid\">...</div>"}`
- Date filter: `cf_date_earliest >= dateFrom AND cf_date_earliest <= dateTo` (single meta, approximate)

**New taxonomy slugs**: `cf_destination` (hierarchical), `cf_transport_type`

**New event meta**: `cf_min_age` (int), `cf_max_age` (int), `cf_date_earliest` (YYYY-MM-DD string)

**Age group computation** (replaces raw "10–16 lat" terms):
- Dzieci: [minAge, maxAge] overlaps [4, child_max] (default 12)
- Młodzież: [minAge, maxAge] overlaps [child_max+1, youth_max] (default 17)
- Dorośli: maxAge > youth_max

## Files changed (Phases 1–4)

New:
- `src/Taxonomy/DestinationTaxonomy.php`
- `src/Taxonomy/TransportTypeTaxonomy.php`
- `src/Presentation/EventCardRenderer.php`
- `src/Api/EventsEndpoint.php`
- `assets/js/search-filters.js`

Modified:
- `campsflow.php` — register 4 new classes
- `src/Sync/SyncRunner.php` — saveEventMeta + setAgeGroupTerms + 2 new term methods
- `src/Admin/SettingsPage.php` — age threshold settings
- `src/Admin/SyncNotice.php` — MANAGED_TAXONOMIES
- `src/Presentation/ListingShortcode.php` — delegate to EventCardRenderer, add data attrs, enqueue JS
- `assets/css/campsflow.css` — skeleton styles

## Key risks

- `wp_insert_term` for destination hierarchy must guard against race conditions (check `term_exists` first — covered in Phase 1 spec).
- Old "10–16 lat" terms become orphans after re-sync; they're read-only by capability so they linger in the DB. Acceptable.
- Phases 5–6 depend on `FieldConfig::$lockedValue` / `$defaultValue` from R-002 — not available until R-002 is `impl_reviewed`.
