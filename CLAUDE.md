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

- PSR-4 autoloading, namespace `Campsflow\`
- WordPress Coding Standards dla hooków, nonces, capabilities
- Sanityzacja WSZYSTKICH danych wejściowych: `sanitize_text_field()`, `absint()`, itp.
- Escapowanie WSZYSTKICH danych wyjściowych: `esc_html()`, `esc_url()`, `esc_attr()`
- Brak bezpośrednich zapytań `$wpdb` — tylko WP_Query i post/option meta API
- Nonces dla wszystkich akcji admin
- Capabilities check przed każdą akcją admin (`current_user_can()`)
- `declare(strict_types=1)` w każdym pliku PHP

<!-- BEGIN @przeprogramowani/10x-cli -->

## 10xDevs AI Toolkit — Module 1, Lesson 4

Onboard the agent to the project you scaffolded in Lesson 3 with the **agent-context chain**:

```
(/10x-init  →  /10x-shape  →  /10x-prd  →  /10x-tech-stack-selector  →  /10x-bootstrapper)  →  /10x-agents-md  →  /10x-rule-review  →  /10x-lesson
```

The PRD → tech-stack → bootstrap chain ships from Lessons 1–3 (re-included so you can fix the project mid-flight). `/10x-agents-md`, `/10x-rule-review`, and `/10x-lesson` are the lesson's main topics. The chain extends in Lesson 5 to the infra/deploy step.

### Task Router — Where to start

| Skill | Use it when |
| --- | --- |
| **Agent context (lesson focus)** | |
| `/10x-agents-md` | The repo is scaffolded but the agent has no project-specific onboarding. Inspects the repo (package manifest, README, scripts, lint/test config, layout, commit history) and writes a concise, ordered "Repository Guidelines" to `AGENTS.md` (or, when invoked from a subdirectory, a directory-level `AGENTS.md` reframed around local conventions and the dominant unit). Use as an alternative to the host's built-in `/init` or as a fallback for tools without one. Repo-level body targets ~200 lines; directory-level guides target 120–250 words. |
| `/10x-rule-review <path>` | You have a rules-for-AI file (`AGENTS.md`, `CLAUDE.md`, `.cursor/rules/*.mdc`, `.github/copilot-instructions.md`, `.windsurfrules`, nested per-area files) and want a 5-axis scorecard: length, embedded code/config snippets, precision of language, redundancy with public knowledge, and rule ordering. Tool-agnostic — scores the artifact's condition, not the project. Default output is read-only; only Check 5 (reorder) may edit, and only with explicit approval. |
| `/10x-lesson [seed]` | You spotted a recurring rule worth surfacing for future runs of `/10x-frame`, `/10x-research`, `/10x-plan`, `/10x-plan-review`, `/10x-implement`, and `/10x-impl-review`. Appends a single entry (Context / Problem / Rule / Applies to) to `context/foundation/lessons.md`. Self-bootstraps the file with the canonical `# Lessons Learned` header on first use. Append-only — never reorders or rewrites prior entries. |
| **Re-run upstream if needed** | |
| `/10x-init` / `/10x-shape` / `/10x-prd` / `/10x-tech-stack-selector` / `/10x-bootstrapper` / `/10x-stack-assess` / `/10x-health-check` | Bundled so you can fix the PRD, swap the stack, or re-scaffold mid-flight. If `/10x-rule-review` flags a `FAIL` you can't shrink your way out of, that often points back to ambiguous PRD or stack decisions — re-run the upstream skill rather than padding `AGENTS.md` with corrections. |

### How the chain hands off

- `/10x-agents-md` writes (or surgically updates) `AGENTS.md` at the resolved scope. Repo-level scope = the file lives at the repo root and frames the project as a whole; directory-level scope = the file lives next to the code it governs and reframes around the local unit, dropping repo-wide framing entirely. The skill never silently overwrites — it switches to an update flow when the target exists.
- `/10x-rule-review` reads any rules-for-AI markdown file you point it at and prints a 5-check scorecard (`OK` / `WARN` / `FAIL`) with concrete fixes. It does not depend on `/10x-agents-md` having run; you can review `.cursor/rules/`, copilot instructions, or a hand-written `CLAUDE.md` the same way.
- `/10x-lesson` self-bootstraps `context/foundation/lessons.md` on first use, then appends one Context/Problem/Rule/Applies-to entry per invocation. The file is consumed as a prior by the planning- and review-phase skills introduced later in the workflow — `/10x-frame`, `/10x-research`, `/10x-plan`, `/10x-plan-review`, `/10x-implement`, `/10x-impl-review`.

### What the lesson's skills capture (and what they do NOT)

- **`/10x-agents-md` captures**: project structure, build/test/lint commands actually present in scripts, commit conventions inferred from history, repo-specific tripwires the agent would otherwise miss, references to canonical files via `@`-paths instead of pasting their content. Directory-level scope additionally captures: local naming/layout patterns inferred from siblings, allowed/forbidden imports, the test pattern used by neighbours, and tripwires visible in the immediate area.
- **`/10x-agents-md` does NOT** paste in the contents of `tsconfig.json` / `eslint.config` / framework docs the agent already knows; it does NOT generate generic "write clean code" intentions; it does NOT replace the host's built-in `/init` when one exists — it's positioned as an alternative or fallback, not a default.
- **`/10x-rule-review` captures**: a length verdict (OK ≤ 200 non-empty lines, WARN 201–500, FAIL 501+), code/config blocks that should be `@`-references instead, vague-intention language, redundancy with framework docs the agent already has from training, and a Check 5 reorder proposal that surfaces critical rules to the top.
- **`/10x-rule-review` does NOT** edit the file by default; it does NOT score project content (architecture, stack choices) — it scores the rule artifact's condition; it does NOT generate a "fixed version" of the file (Check 5 may move sections with explicit approval, never rewrite rule wording).
- **`/10x-lesson` captures**: one entry per invocation with a short imperative H2 title (the title IS the rule), Context (subsystem / phase / file pattern, specific enough to pattern-match), Problem (what concretely breaks without the rule, ideally with a past incident), Rule (1–2 imperative sentences pasteable verbatim into a future review finding), Applies to (subset of `frame`, `research`, `plan`, `plan-review`, `implement`, `impl-review`, or `all`).
- **`/10x-lesson` does NOT** edit or remove existing lessons — the file is append-only by design (rewriting recurring rules without thought is the failure mode this convention prevents); it does NOT batch multiple rules per invocation; it does NOT pre-fill fields proactively (the user does the writing — that's the price of capturing rules outside a structured review).

### The inclusion test (the filter for AGENTS.md / CLAUDE.md)

Before you add a rule to any rules-for-AI file, ask: *could the agent know this without this file? Could public training data — books, blogs, repos in this stack — have prepared it for this?* If yes, drop it. If no, keep it. The file is onboarding for an agent that already knows TypeScript / Python / your framework but does NOT know your local conventions.

Belongs:
- non-obvious project conventions (error-response shape, file naming, allowed import paths)
- project-specific traps and "embarrassing" workarounds tied to history or dependency bugs
- referenced canonical files via `@`-paths (e.g. `@src/features/users/user.service.ts` as a pattern reference, not pasted code)

Does NOT belong:
- mainstream framework documentation
- README content the agent will read anyway (link with `@README.md`)
- popular generic advice ("use TypeScript strict mode") that's already enforced by config
- intention statements ("write clean code", "follow good practices") — convert to a checkable behaviour or drop

### U-shaped attention and granular rules

LLMs attend most strongly to the start and end of context (Lost-in-the-Middle / U-shaped attention). A long monolithic `CLAUDE.md` puts its middle rules in the weakest attention zone. Two practical consequences:

1. **Most important rules go to the top** of any rule file.
2. **Per-area rules belong next to their code** — nested `AGENTS.md` / `CLAUDE.md` inside `src/api/`, `.cursor/rules/*.mdc` with file globs, etc. Granular files are loaded selectively and arrive whole near the start of their own section, instead of being buried at line 400 of one big file.

`/10x-rule-review` Check 5 (reorder) operationalizes consequence (1); the inclusion test plus directory-level `/10x-agents-md` operationalizes consequence (2).

### The five-pattern calibration drill

Before writing a rule, validate that the agent actually breaks the convention without it. Pick one pattern from your project (error-response shape, file naming, import style, module structure, date handling). Then:

1. Ask the agent to implement against the pattern 3–5 times from a clean state, no rule.
2. Note where it broke the convention; capture run time, files explored, and visible cost/tokens if the host surfaces them.
3. Add a 1–3-sentence rule to the appropriate scope (root or area-level).
4. Re-run the same task in a fresh session and compare convention adherence, time, files, and iterations.

If the agent already trends toward the convention without the rule, you don't need the rule. If it systematically picks the wrong pattern, you've found a high-leverage rule to add. This drill is what "earning a rule from a recurring failure" actually looks like.

### Hierarchy and tool interop

- **Claude Code** loads `CLAUDE.md` from the user dir (`~/.claude/CLAUDE.md`), the repo root, and any subdirectory the agent works under. Deeper files override or supplement higher ones.
- **Codex** and **GitHub Copilot** load `AGENTS.md` from the current directory upward — closest file wins.
- One canonical file is preferable to three duplicates. A common pattern: `AGENTS.md` as source of truth, `CLAUDE.md` as a thin Claude-Code shim with `@AGENTS.md` import, `.github/copilot-instructions.md` only if Copilot needs its own additions. Symlink (`ln -s AGENTS.md CLAUDE.md`) is the simplest deduplication when tools require both names.
- Auto-memory (e.g. Claude Code's `~/.claude/projects/<dir-with-slashes-as-dashes>/memory/MEMORY.md`) is local to the machine and not a substitute for `AGENTS.md`. Team-binding rules live in the repo; auto-memory is a personal cache, periodically reviewable.

### Inner-loop hooks (deterministic feedback without prompting)

Mechanical, non-pickable checks belong in hooks (e.g. Claude Code's `PostToolUse`), not in the rule file. The agent finishes an edit; a formatter or fast lint runs; the result feeds back without you reminding it. Settings template (`settings.json.template`) ships in the lesson pack as the wiring entry point. Keep procedural workflows (deeper review, release checklist, deploy on sandbox) in skills, and reserve hooks for deterministic tool signals.

### Foundation paths used by this lesson

- `AGENTS.md` / `CLAUDE.md` (and per-area variants) — `/10x-agents-md` output
- `context/foundation/lessons.md` — `/10x-lesson` output (append-only register, consumed by future planning/review skills)
- `context/foundation/prd.md`, `context/foundation/tech-stack.md` — inputs from earlier lessons, still present
- `docs/reference/contract-surfaces.md` — load-bearing names registry (scaffolded by `/10x-init`)

### Universal language

The shipped skills carry no 10xDevs / cohort / certification references. `/10x-agents-md` discovers from the repo it's invoked in; `/10x-rule-review` is tool-agnostic and treats every file as "a rules-for-AI artifact"; `/10x-lesson` writes one entry shape regardless of project domain. The 5-pattern calibration drill is illustrative — substitute patterns from your own stack.

Skills must not write to `context/archive/`. Archived changes are immutable; if a resolved target path starts with `context/archive/`, abort with: "This change is archived. Open a new change with `/10x-new` instead."

<!-- END @przeprogramowani/10x-cli -->
