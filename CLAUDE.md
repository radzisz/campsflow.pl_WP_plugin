# Campsflow WP Plugin

WordPress plugin integrujący WordPressa z systemem rezerwacji Campsflow.pl.

## Kontekst projektu

Campsflow.pl to SaaS multi-tenant do zarządzania rezerwacjami na obozy i rekolekcje.
Tenanci (organizatorzy) mają własne strony WordPress i chcą:
- Wyświetlać ofertę (imprezy + turnusy) z pełnym SEO — dane synchronizowane lokalnie
- Przyjmować zapisy przez formularz Campsflow osadzony w iframe (na dedykowanej stronie WP)

Plugin działa jako **klient** wobec Campsflow public API — nie ma własnego backendu.

## Architektura — trzy warstwy

```
Campsflow API
     │
     ▼
[1] SYNCHRONIZATOR          ← pobiera dane, transformuje, zapisuje do WP CPT
     │
     ▼
WP Local DB (CPT posts)
     │
     ▼
[2] PREZENTACJA             ← WP_Query na lokalnych danych, shortcodes/widgets
     │
     ▼
Strona ofertowa (SEO)
     │  klik "Rezerwuj"
     ▼
[3] REJESTRACJA             ← dedykowana strona WP z iframe Campsflow
```

**Klucz:** iframe pojawia się TYLKO na stronie rejestracji, nigdy na stronach ofertowych.

---

## Model danych

### Encje Campsflow

- **Impreza** — produkt obozowy (opis, lokalizacja, program, grupa wiekowa)
- **Turnus** — konkretny termin imprezy (daty, cena, liczba miejsc)
- Relacja: jedna impreza → 1..N turnusów

### CPT w WP

**`cf_event`** (impreza):
```
post_title                → nazwa imprezy
post_content              → opis
post_status               → publish / draft (mapowane ze statusu Campsflow)
meta: cf_event_id         → UUID z Campsflow (klucz synchronizacji)
meta: cf_localization     → JSON: { destination, name, address: {city,…}, lat, lng, … }
meta: cf_contact          → JSON: { email, phone, … }
meta: cf_documents        → JSON array
meta: cf_general_terms    → tekst
meta: cf_instructions     → tekst
meta: cf_multimedia_urls  → JSON array URL-i zdjęć
meta: cf_lead_image_url   → URL głównego zdjęcia
meta: cf_video_urls       → JSON array URL-i wideo
meta: cf_lead_video_url   → URL głównego wideo
meta: cf_event_class      → typ: YOUTH_CAMP | KIDS_CAMP | FAMILY_CAMP | …
meta: cf_custom_fields    → JSON array pól własnych
meta: cf_date_earliest    → data najbliższego turnusu (Y-m-d)
meta: cf_event_min_price  → cena od (grosze, int)
meta: cf_currency         → waluta (domyślnie PLN)
meta: cf_min_age / cf_max_age → przedział wiekowy
taksonomia: cf_event_tag      → tagi obozu ("góry", "morze", …)
taksonomia: cf_event_category → kategoria ("Obozy językowe", …)
taksonomia: cf_age_group      → "8-12 lat", "13-16 lat", …
taksonomia: cf_destination    → kraj → region (hierarchiczna)
taksonomia: cf_season         → "lato", "zima", "wiosna", "jesień"
taksonomia: cf_transport_type → typ transportu
```

**`cf_turnus`** (turnus, child of cf_event):
```
post_title                      → nazwa turnusu
post_parent                     → ID posta cf_event
meta: cf_turnus_id              → UUID z Campsflow (klucz synchronizacji)
meta: cf_event_id               → UUID imprezy nadrzędnej
meta: cf_turnus_name            → nazwa wyświetlana
meta: cf_date_from              → data rozpoczęcia (Y-m-d)
meta: cf_date_to                → data zakończenia (Y-m-d)
meta: cf_number_of_days         → liczba dni (int)
meta: cf_price_from             → cena od (grosze, int)
meta: cf_currency               → waluta
meta: cf_transport              → JSON: { type, … }
meta: cf_meeting_points_start   → JSON array punktów zbiórki
meta: cf_meeting_points_return  → JSON array punktów powrotu
meta: cf_seats_available        → wolne miejsca (int)
meta: cf_seats_all              → wszystkie miejsca (int)
meta: cf_availability           → kubełek: available | few_left | almost_full | full
meta: cf_season                 → sezon (lato | zima | wiosna | jesień)
meta: cf_custom_fields          → JSON array pól własnych
```

### Kubełki dostępności

Plugin **nigdy** nie przechowuje liczby wolnych miejsc — tylko kubełek.
Transformer oblicza go w locie z `seatsTotal` i `seatsReserved` z API.

| Kubełek | Wyświetlane | Warunek (% wolnych miejsc) |
|---|---|---|
| `available` | nic (domyślnie) | > próg `few_left` |
| `few_left` | "Mało miejsc" | ≤ próg `few_left` (domyślnie 30%) |
| `almost_full` | "Na wyczerpaniu" | ≤ próg `almost_full` (domyślnie 10%) |
| `full` | "Brak miejsc" | 0 wolnych |

Progi konfigurowalne w WP Admin → Campsflow → Ustawienia.

Kubełek imprezy = najlepsza dostępność spośród jej turnusów
(impreza jest FULL tylko gdy WSZYSTKIE turnusy są full).

---

## Synchronizator

### Trigger

WP Cron — domyślnie co 60 minut (konfigurowalne). Ręczny trigger w WP Admin → Campsflow → Synchronizacja.

### Przepływ

```
Fetcher → Transformer → SyncRunner
```

- **Fetcher** — HTTP GET do Campsflow public API, obsługuje błędy i timeout
- **Transformer** — mapuje pola API → WP meta, oblicza kubełki, przypisuje taksonomie
- **SyncRunner** — upsert po `cf_event_id` / `cf_turnus_id`, usuwa (trash) pozycje których nie ma w API

### Campsflow Public API

```
GET /api/v1/public/{tenantSlug}/events
    → [{ id, name, description, location, status, sessions: [...] }]
```

`seatsTotal` i `seatsReserved` w odpowiedzi — plugin oblicza kubełek lokalnie.

---

## Prezentacja

### Strony zarządzane przez plugin

Plugin tworzy dwie strony automatycznie przy aktywacji (marker w meta, nie slug):

| Strona | Slug | Shortcode | Meta marker |
|---|---|---|---|
| Rejestracja | `cf-registration` | `[campsflow_registration_form]` | `_campsflow_registration_page` |
| Wyszukiwarka | `cf-search` | `[campsflow_search_filter][campsflow_search_results]` | `_campsflow_search_page` |

Obie można odtworzyć z WP Admin → Campsflow → Ustawienia.

### Shortcodes

| Shortcode | Opis |
|---|---|
| `[campsflow_listing]` | Listing imprez lub turnusów |
| `[campsflow_search_filter]` | Formularz filtrów AJAX |
| `[campsflow_search_filter_field]` | Pojedyncze pole filtru |
| `[campsflow_search_sort]` | Pasek sortowania |
| `[campsflow_search_results]` | Wyniki wyszukiwania (AJAX) |
| `[campsflow_event_breadcrumb]` | Breadcrumb z linkami do wyszukiwarki |
| `[campsflow_event_field field="..."]` | Dowolne pole meta imprezy |
| `[campsflow_event_map]` | Mapa Google/Leaflet |
| `[campsflow_event_tags]` | Tagi imprezy |
| `[campsflow_event_age_groups]` | Grupy wiekowe |
| `[campsflow_event_contact]` | Box kontaktowy |
| `[campsflow_event_documents]` | Lista dokumentów |
| `[campsflow_event_lead_image]` | Zdjęcie główne |
| `[campsflow_event_gallery]` | Galeria zdjęć / slider |
| `[campsflow_event_lead_video]` | Wideo główne |
| `[campsflow_event_sessions]` | Lista turnusów z przyciskami |
| `[campsflow_registration_form]` | Iframe formularza rejestracji |

### Widgety Elementor

Dwie kategorie w panelu Elementor:

**CampsFlow — Wyszukiwanie** (strona listingu):
- `SearchFilterWidget`, `SearchFilterFieldWidget`, `SearchSortWidget`, `SearchResultsWidget`

**CampsFlow — Impreza** (strona pojedynczej imprezy):
- `EventSessionsWidget`, `EventLeadImageWidget`, `EventLeadVideoWidget`, `EventGalleryWidget`
- `EventMapWidget`, `EventFieldWidget`, `EventContactWidget`, `EventDocumentsWidget`
- `EventTagsWidget`, `EventAgeGroupWidget`, `EventBreadcrumbWidget`

### WPBakery

`WpBakeryIntegration` — odpowiedniki powyższych shortcodów jako elementy WPBakery.
`WpBakeryDynamicContent` — dynamic content sources: `cf_location_city`, `cf_price_min`, `cf_availability_label`, itp.

### Rejestracja (iframe)

```
URL iframe: https://ukryteskarby.pl/embed/{tenantSlug}/register?session={uuid}
```

Campsflow serwuje formularz bez nagłówka i stopki (no-chrome embed layout).
Plugin nasłuchuje `postMessage { type: 'CF_RESIZE', height: N }` i dostosowuje wysokość iframe.

---

## Konfiguracja (Config.php)

Priorytety (najwyższy pierwszy): PHP constant → env var → WP option → default.

| Stała / opcja | Domyślnie | Opis |
|---|---|---|
| `CAMPSFLOW_API_URL` / `campsflow_api_url` | `https://api.ukryteskarby.pl` | URL API |
| `CAMPSFLOW_ADMIN_URL` / `campsflow_admin_url` | `https://admin.ukryteskarby.pl` | URL panelu admin |
| `CAMPSFLOW_APP_URL` / `campsflow_app_url` | `https://ukryteskarby.pl` | URL aplikacji (embed) |

Dla wp-env nadpisuj w `.wp-env.json → env → development → config`.

---

## Struktura katalogów

```
campsflow.php
src/
├── Admin/
│   ├── AdminColumns.php        ← kolumny i filtry w listach CPT
│   ├── ElementorLinks.php      ← linki "Edytuj w Elementor" w admin
│   ├── FixtureImporter.php     ← import danych testowych (fixture)
│   ├── SettingsPage.php        ← WP Admin → Campsflow → Ustawienia
│   └── SyncNotice.php          ← pasek powiadomień o synchronizacji
├── Api/
│   └── EventsEndpoint.php      ← REST GET /wp-json/campsflow/v1/events
├── PostType/
│   ├── EventPostType.php       ← CPT cf_event
│   ├── PostStatus.php          ← niestandardowe statusy postów
│   └── SessionPostType.php     ← CPT cf_turnus
├── Presentation/
│   ├── ElementorIntegration.php← rejestracja kategorii i widgetów Elementor
│   ├── ElementorWidget.php     ← widget listing (generyczny)
│   ├── Event*Widget.php        ← widgety strony imprezy (11 plików)
│   ├── Search*Widget.php       ← widgety wyszukiwarki (4 pliki)
│   ├── Event*Shortcode.php     ← shortcodes strony imprezy
│   ├── Search*Shortcode.php    ← shortcodes wyszukiwarki
│   ├── FilterRenderMethods.php ← wspólny renderer filtrów
│   ├── EventMapRenderMethods.php
│   ├── EventCardRenderer.php   ← renderer karty imprezy
│   ├── ListingShortcode.php
│   ├── RegistrationFormShortcode.php
│   ├── SearchPage.php          ← auto-create/restore strony wyszukiwarki
│   ├── TemplateLoader.php
│   ├── WpBakeryIntegration.php
│   └── WpBakeryDynamicContent.php
├── Sync/
│   ├── AggregateStats.php
│   ├── AvailabilityBucket.php  ← enum kubełków dostępności
│   ├── SyncLog.php
│   ├── SyncRunner.php          ← upsert CPT po cf_event_id / cf_turnus_id
│   ├── SyncScheduler.php       ← WP Cron hooks
│   ├── SyncStats.php
│   ├── TransformedTurnus.php   ← DTO turnusu po transformacji
│   └── Transformer.php         ← mapowanie API → WP meta + kubełki
├── Taxonomy/
│   ├── AgeGroupTaxonomy.php    ← cf_age_group
│   ├── CampTagTaxonomy.php     ← cf_tag (legacy)
│   ├── DestinationTaxonomy.php ← cf_destination (hierarchiczna)
│   ├── EventCategoryTaxonomy.php ← cf_event_category
│   ├── EventTagTaxonomy.php    ← cf_event_tag
│   ├── SeasonTaxonomy.php      ← cf_season
│   └── TransportTypeTaxonomy.php ← cf_transport_type
├── Widget/
│   ├── FieldConfig.php         ← konfiguracja pól dynamicznych
│   ├── FieldSorter.php
│   └── FieldValueRenderer.php
├── Config.php                  ← URL-e API/app z priorytetem constant > env > option
└── CurrencyFormatter.php       ← formatowanie cen (grosze → "1 500 zł")
assets/
├── css/campsflow.css           ← CSS custom properties, brak !important
└── js/
    ├── registration.js         ← postMessage resize listener
    ├── search-filters.js       ← AJAX filtry + pagination
    └── event-map.js            ← Leaflet / Google Maps
tests/
├── fixtures/
│   ├── api-events.json         ← mockowe dane API
│   └── seed-events.json        ← dane seed (92 imprezy, generowane przez scripts/gen_seed.py)
├── Integration/Sync/
│   └── WpWriterTest.php
└── Unit/
    ├── Sync/TransformerTest.php
    └── Widget/FieldSorterTest.php, FieldValueRendererTest.php
```

---

## Dev environment

### Wymagania

- Docker Desktop (uruchomiony)
- Node.js 18+

PHP i Composer działają przez Docker — nic nie instalujesz lokalnie.

### Uruchomienie

```bash
npm install                 # instaluje @wordpress/env
npm run composer:install    # instaluje zależności PHP przez Docker
npm run env:start           # uruchamia WordPress + MySQL w Dockerze
```

WordPress dostępny pod:
- http://localhost:8890 — frontend (plugin aktywny)
- http://localhost:8890/wp-admin — admin / password

### Zatrzymanie i reset

```bash
npm run env:stop      # zatrzymuje kontenery
npm run env:clean     # czyści bazę danych (świeży WP)
npm run env:logs      # logi PHP z kontenera
```

---

## Testowanie

### Unit tests — izolowana logika PHP (Brain\Monkey)

Nie wymagają działającego WP. Testują: Transformer, kubełki, mapowania, FieldSorter.

```bash
npm run test:unit
```

### Integration tests — pełne WP (wymaga env:start)

```bash
npm run env:start           # musi być uruchomiony
npm run test:integration
```

### Wszystkie testy + statyczna analiza

```bash
npm run test          # unit + integration
npm run analyse       # PHPStan level 8
npm run lint          # WordPress Coding Standards
npm run lint:fix      # auto-fix PHPCS
npm run test:coverage # HTML do ./coverage/
```

---

## Zasady kodowania

Obowiązują wszystkie reguły z globalnego `~/.claude/CLAUDE.md` oraz:

- PSR-4 autoloading, namespace `Campsflow\`
- WordPress Coding Standards dla hooków, nonces, capabilities
- Sanityzacja WSZYSTKICH danych wejściowych: `sanitize_text_field()`, `absint()`, itp.
- Escapowanie WSZYSTKICH danych wyjściowych: `esc_html()`, `esc_url()`, `esc_attr()`
- Brak bezpośrednich zapytań `$wpdb` — tylko WP_Query i post/option meta API
- Nonces dla wszystkich akcji admin
- Capabilities check przed każdą akcją admin (`current_user_can()`)
- `declare(strict_types=1)` w każdym pliku PHP
- Elementor: `get_name()` nigdy nie zmieniaj — jest zapisany w bazie jako typ widgetu
- Strony zarządzane przez plugin: szukaj po meta markerze, nie po slug/tytule (slug może być zmieniony przez użytkownika)

<!-- BEGIN @przeprogramowani/10x-cli -->

## 10xDevs AI Toolkit - Module 2, Lesson 2

Turn one roadmap item into the first implementation cycle with the **change planning chain**:

```
/10x-roadmap -> /10x-new -> /10x-plan -> /10x-plan-review -> /10x-implement
```

`/10x-new`, `/10x-plan`, `/10x-plan-review`, and `/10x-implement` are the lesson focus. `/10x-frame` and `/10x-research` are not required rituals here; they are escalation paths introduced in the next lesson.

### Task Router - Where to start

| Skill | Use it when |
| --- | --- |
| **Change setup (lesson focus)** | |
| `/10x-new <change-id>` | You selected a roadmap item and need a stable change folder. Creates `context/changes/<change-id>/change.md` so planning, implementation, progress, commits, and later review all share one identity. Use AFTER roadmap selection, BEFORE `/10x-plan`. |
| **Planning (lesson focus)** | |
| `/10x-plan <change-id>` | You have a change folder and need a reviewable implementation plan. Reads roadmap context, foundation docs, codebase evidence, and any existing change notes; writes `plan.md` and `plan-brief.md` with phases, file contracts, success criteria, and `## Progress`. |
| **Plan readiness (lesson focus)** | |
| `/10x-plan-review <change-id>` | You have `plan.md` and need a light pre-code readiness check. Use it to catch missing end state, weak contracts, malformed progress, scope drift, or blind spots before code changes begin. |
| **Implementation (lesson focus)** | |
| `/10x-implement <change-id> phase <n>` | You have an approved plan and want to execute one phase with verification, manual gate, commit ritual, and SHA write-back to `## Progress`. |
| **Lifecycle closure** | |
| `/10x-archive <change-id>` | A change is merged or intentionally closed. Move it out of active `context/changes/` into archive state. |

### How the chain hands off

- `/10x-new` creates the durable change identity.
- `/10x-plan` turns that identity into an implementation contract.
- `/10x-plan-review` checks the plan before the agent mutates code.
- `/10x-implement` executes one planned phase, verifies, asks for manual confirmation when needed, commits, and records progress.

### Lesson boundaries

- Plan is the default router after roadmap selection. Start with `/10x-plan` unless the problem is unclear or external evidence is blocking.
- Do not run `/10x-frame + /10x-research` as ceremony for every change.
- Do not turn this lesson into a full end-to-end product build. A checkpoint with a planned and partially or fully implemented stream is valid.
- Code review of the implemented diff belongs to Lesson 3 via `/10x-impl-review`.
- Lifecycle closure via `/10x-archive` after a change is merged or intentionally closed.

### Paths used by this lesson

- `context/foundation/roadmap.md` - upstream roadmap
- `context/changes/<change-id>/change.md` - change identity
- `context/changes/<change-id>/plan.md` - implementation contract
- `context/changes/<change-id>/plan-brief.md` - compressed handoff
- `context/foundation/lessons.md` - recurring rules and pitfalls
- `docs/reference/contract-surfaces.md` - load-bearing names registry

Skills must not write to `context/archive/`. Archived changes are immutable; if a resolved target path starts with `context/archive/`, abort with: "This change is archived. Open a new change with `/10x-new` instead."

<!-- END @przeprogramowani/10x-cli -->
