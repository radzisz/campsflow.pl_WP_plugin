---
change_id: search-filters
title: AJAX search and filter for events listing
status: implementing
created: 2026-05-27
updated: 2026-05-27
archived_at: null
---

## Notes

AJAX search/filter for events listing: REST endpoint + filter form widget (Elementor + WPBakery) + results widget with skeleton loader.

### API nomenclature (v2)

| API field | Type | WP mapping | Notes |
|---|---|---|---|
| `eventClass` | string enum (`YOUTH_CAMP`, …) | meta `cf_event_class` | "dla kogo" filter |
| `eventProcess` | `{id, name}` | meta `cf_event_process_id/name` | type of event (e.g. "Obóz przygodowy") |
| `eventCategories` | `string[]` | taxonomy `cf_event_category` | public, shown in filter + pills |
| `eventTags` | `string[]` | taxonomy `cf_event_tag` | private/internal labels |
| `minAge` / `maxAge` | int | meta `cf_min_age` / `cf_max_age` + `cf_age_group` terms | age groups computed from configurable thresholds |
| `localization.destination` | string | taxonomy `cf_destination` (child term) | to build |
| `localization.address.country` | string (ISO) | taxonomy `cf_destination` (parent term) | to build |
| `turnusy[].transport.type` | string | taxonomy `cf_transport_type` | to build |
| `turnusy[].dateFrom` (min) | date | meta `cf_date_earliest` | to build |

### Age group config (plugin settings)
Thresholds configurable in WP Admin → Campsflow → Settings:
- `cf_age_child_max` (default: 12) — child: minAge–maxAge overlaps [4, child_max]
- `cf_age_youth_max` (default: 17) — youth: overlaps [child_max+1, youth_max]
- adult: maxAge > youth_max

Events can belong to multiple groups (overlapping ranges).
