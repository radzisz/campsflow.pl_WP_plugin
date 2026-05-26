# Plan Brief: Atomowe widgety Elementora dla pól cf_event

**Change:** event-field-widgets | **Phases:** 5 | **Status:** planned

## What we're building

8 atomic Elementor widgets, each rendering exactly one cf_event field. Tenant composes an event page by placing widgets into an Elementor template for the cf_event CPT.

## Widget inventory

| Phase | Widgets | Source |
|---|---|---|
| 1 | EventFieldWidget | SELECT: post_title, cf_reservation_url, cf_lead_image/video_url (raw), cf_description.{general,program,priceInclude}, custom key |
| 2 | EventContactWidget, EventDocumentsWidget | cf_contact JSON, cf_documents JSON array |
| 3 | EventLeadImageWidget, EventLeadVideoWidget | cf_lead_image_url → `<img>`; cf_lead_video_url → iframe/`<video>` |
| 4 | EventGalleryWidget | cf_multimedia_urls → CSS grid + `<dialog>` lightbox OR custom data-attr |
| 5 | EventTagsWidget, EventAgeGroupWidget | cf_tag, cf_age_group taxonomy terms → pills |

## Key design decisions settled

- **Render mode** (EventFieldWidget): `text` = esc_html, `html` = wp_kses_post, `auto` = detect HTML tags
- **Custom field** (EventFieldWidget): SELECT option `custom` + free-text key input; resolves from cf_custom_fields array
- **Taxonomies**: dedicated widgets (not in EventFieldWidget SELECT)
- **Style controls**: Content tab + one Style tab section per widget (typography/color; media widgets get max-width/border-radius instead)
- **Empty state**: `editor_placeholder` control, shown only in Elementor edit mode
- **Gallery built-in**: vanilla JS inline `<script>` (~10 lines), `<dialog>` element as lightbox, no external deps
- **No abstract base class** — copy the Widget_Base pattern; duplication at this scale is fine

## Critical files

- `src/Presentation/ElementorIntegration.php` — registerWidget() updated each phase
- `phpstan.neon` — each new Presentation file must be added to excludePaths
- `src/Widget/FieldValueRenderer.php` — new utility for renderMode logic (unit-testable)
- `assets/css/campsflow.css` — all CSS classes already exist; no new CSS needed for phases 1–3, 5; gallery built-in mode uses `.cf-gallery__grid`, `.cf-gallery__item`

## CI commands

```bash
npm run test:unit    # PHPUnit unit tests (Docker PHP 8.2)
npm run lint         # PHPCS — WordPress Coding Standards
npm run analyse      # PHPStan level 8
```

## Resume commands by phase

```
/10x-implement event-field-widgets phase 1
/10x-implement event-field-widgets phase 2
/10x-implement event-field-widgets phase 3
/10x-implement event-field-widgets phase 4
/10x-implement event-field-widgets phase 5
```
