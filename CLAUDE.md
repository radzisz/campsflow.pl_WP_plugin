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
     │  klik "Zapisz się"
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
- Różny opis = osobna impreza (turnusy jednej imprezy mają ten sam opis)

### CPT w WP

**`cf_event`** (impreza):
```
post_title           → nazwa imprezy
post_content         → opis
post_status          → publish / draft (mapowane ze statusu Campsflow)
meta: cf_event_id    → UUID z Campsflow (klucz synchronizacji)
meta: cf_location    → lokalizacja (JSON: city, region, coords)
taksonomia: cf_tag         → "góry", "morze", "zimowy", …
taksonomia: cf_age_group   → "8-12 lat", "13-16 lat", …
```

**`cf_session`** (turnus, child of cf_event):
```
post_title              → nazwa turnusu (np. "Turnus I")
post_parent             → ID posta cf_event
meta: cf_session_id     → UUID z Campsflow
meta: cf_date_from      → data rozpoczęcia (Y-m-d)
meta: cf_date_to        → data zakończenia (Y-m-d)
meta: cf_price          → cena w groszach (int)
meta: cf_availability   → kubełek: available | few_left | almost_full | full
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

WP Cron — domyślnie co 60 minut (konfigurowalne). Ręczny trigger dostępny w WP Admin.

### Przepływ

```
Fetcher → Transformer → WpWriter
```

- **Fetcher** — HTTP GET do Campsflow public API, obsługuje błędy i timeout
- **Transformer** — mapuje pola API → WP meta, oblicza kubełki, przypisuje taksonomie
- **WpWriter** — upsert po `cf_event_id` / `cf_session_id`, usuwa (trash) pozycje których nie ma w API

### Campsflow Public API (do implementacji po stronie Campsflow)

```
GET /api/v1/public/{tenantSlug}/events
    → [{ id, name, description, location, status, sessions: [...] }]

GET /api/v1/public/{tenantSlug}/events/{id}
    → { id, name, description, location, status,
        sessions: [{ id, dateFrom, dateTo, price,
                     seatsTotal, seatsReserved }] }
```

`seatsTotal` i `seatsReserved` muszą być w odpowiedzi — plugin oblicza kubełek lokalnie.

---

## Prezentacja

### Dwa tryby listingu

**`view=events`** — lista imprez z agregatem dostępności (dla dużych tenantów):
```
[campsflow_listing view="events"]
```
Użytkownik klika imprezę → strona imprezy → lista turnusów → "Zapisz się"

**`view=sessions`** — płaska lista turnusów (dla małych tenantów):
```
[campsflow_listing view="sessions"]
```
Opcjonalne grupowanie po imprezie jako nagłówek. "Zapisz się" bezpośrednio przy turnusie.

### Przycisk rejestracji

```
[campsflow_register_button session_id="uuid" label="Zapisz się"]
```

Generuje zwykły link: `/rejestracja/?session=uuid` — zero JavaScript.

---

## Rejestracja (iframe)

### Dedykowana strona WP

Jedna strona WP (np. `/rejestracja/`) z shortcodem:
```
[campsflow_registration_form]
```

Plugin tworzy tę stronę automatycznie przy aktywacji jeśli nie istnieje.

### Jak działa iframe

```
URL iframe: https://campsflow.pl/embed/{tenantSlug}/register?session={uuid}
```

Campsflow serwuje formularz bez nagłówka i stopki (no-chrome embed layout).
WP dostarcza nagłówek i stopkę firmy — użytkownik nie opuszcza domeny tenanta.

### Auto-resize

Plugin nasłuchuje `postMessage { type: 'CF_RESIZE', height: N }` od iframe
i dostosowuje jego wysokość. Obsługiwane w `assets/js/registration.js`.

---

## Struktura katalogów

```
campsflow.php                     ← główny plik pluginu (nagłówek, bootstrap)
composer.json
package.json
.wp-env.json
src/
├── PostType/
│   ├── EventPostType.php         ← rejestracja CPT cf_event
│   └── SessionPostType.php       ← rejestracja CPT cf_session
├── Taxonomy/
│   ├── CampTagTaxonomy.php
│   └── AgeGroupTaxonomy.php
├── Sync/
│   ├── Fetcher.php               ← HTTP client (wp_remote_get)
│   ├── Transformer.php           ← mapowanie + kubełki
│   ├── WpWriter.php              ← upsert CPT
│   └── SyncScheduler.php        ← WP Cron hooks
├── Repository/
│   ├── EventRepository.php       ← WP_Query dla imprez
│   └── SessionRepository.php     ← WP_Query dla turnusów
├── Presentation/
│   ├── ListingShortcode.php
│   ├── RegisterButtonShortcode.php
│   └── RegistrationFormShortcode.php
├── Admin/
│   ├── SettingsPage.php
│   └── SyncStatusPage.php
└── Api/
    └── WebhookEndpoint.php       ← REST endpoint dla zdarzeń z Campsflow
assets/
├── css/
│   └── campsflow.css             ← CSS custom properties, brak !important
└── js/
    └── registration.js           ← postMessage resize listener
templates/
├── event-card.php
├── session-row.php
└── registration-iframe.php
tests/
├── Unit/                         ← Brain\Monkey, bez WP
│   └── Sync/
│       └── TransformerTest.php
└── Integration/                  ← WP_UnitTestCase, wymaga wp-env
    └── Sync/
        └── WpWriterTest.php
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

Nie wymagają działającego WP. Testują: Transformer, kubełki, mapowania, walidację.

```bash
npm run test:unit
```

### Integration tests — pełne WP (wymaga env:start)

```bash
npm run env:start           # musi być uruchomiony
npm run test:integration
```

Testują: synchronizację z mockowanym API, zapis do CPT, WP_Query, shortcodes.

### Wszystkie testy + statyczna analiza

```bash
npm run test          # unit + integration
npm run analyse       # PHPStan level 8
npm run lint          # WordPress Coding Standards
npm run lint:fix      # auto-fix PHPCS
npm run test:coverage # HTML do ./coverage/
```

---

## Konfiguracja pluginu (WP Admin → Campsflow → Ustawienia)

| Pole | Domyślnie | Opis |
|---|---|---|
| Tenant slug | — | slug tenanta z Campsflow |
| API base URL | `https://campsflow.pl` | baza URL API |
| Sync interval | `60` | minuty między synchronizacjami |
| Few left threshold | `30` | % wolnych miejsc → "Mało miejsc" |
| Almost full threshold | `10` | % wolnych miejsc → "Na wyczerpaniu" |
| Registration page | `/rejestracja/` | strona z shortcodem rejestracji |

---

## Zasady kodowania

Obowiązują wszystkie reguły z globalnego `~/.claude/CLAUDE.md` oraz:

- TypeScript enum — klucze w `UPPER_SNAKE_CASE`, wartości mogą być dowolne (np. `DAY_CAMP = 'day_camp'`)
- PSR-4 autoloading, namespace `Campsflow\`
- WordPress Coding Standards dla hooków, nonces, capabilities
- Sanityzacja WSZYSTKICH danych wejściowych: `sanitize_text_field()`, `absint()`, itp.
- Escapowanie WSZYSTKICH danych wyjściowych: `esc_html()`, `esc_url()`, `esc_attr()`
- Brak bezpośrednich zapytań `$wpdb` — tylko WP_Query i post/option meta API
- Nonces dla wszystkich akcji admin
- Capabilities check przed każdą akcją admin (`current_user_can()`)
- `declare(strict_types=1)` w każdym pliku PHP

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
