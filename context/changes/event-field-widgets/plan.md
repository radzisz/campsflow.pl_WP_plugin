# Atomowe widgety Elementora dla pól cf_event

**Change:** event-field-widgets
**Status:** planned
**Date:** 2026-05-26

---

## Desired End State

8 atomic Elementor widgets registered under the "CampsFlow" panel category, each rendering one cf_event field:

| Widget class | Elementor name | Source |
|---|---|---|
| `EventFieldWidget` | `campsflow_event_field` | post_title / cf_* scalars / cf_description subfields / cf_custom_fields |
| `EventContactWidget` | `campsflow_event_contact` | `cf_contact` JSON |
| `EventDocumentsWidget` | `campsflow_event_documents` | `cf_documents` JSON array |
| `EventLeadImageWidget` | `campsflow_event_lead_image` | `cf_lead_image_url` → `<img loading="lazy">` |
| `EventLeadVideoWidget` | `campsflow_event_lead_video` | `cf_lead_video_url` → responsive iframe/`<video>` |
| `EventGalleryWidget` | `campsflow_event_gallery` | `cf_multimedia_urls` → CSS grid + `<dialog>` OR custom attr |
| `EventTagsWidget` | `campsflow_event_tags` | `cf_tag` taxonomy |
| `EventAgeGroupWidget` | `campsflow_event_age_groups` | `cf_age_group` taxonomy |

---

## Current State Analysis

- `ElementorIntegration::registerWidget()` currently registers `ElementorWidget` + `EventSessionsWidget`
- Widget pattern: `final class X extends \Elementor\Widget_Base`, private `register*Section()` helpers, `render()` calls `get_settings_for_display()`
- PHPStan level 8; every Presentation class is excluded individually in `phpstan.neon`; dead exclusion `EventMetaWidget.php` still present (file deleted in commit efb5247)
- `assets/css/campsflow.css` provides: `.cf-contact-box`, `.cf-contact-box__name`, `.cf-contact-link`, `.cf-docs-list`, `.cf-doc-link`, `.cf-gallery__grid`, `.cf-gallery__item`, `.cf-gallery__img`, `.cf-video-wrap`, `.cf-tag`, `.cf-tag--age`, `.cf-section`, `.cf-section__title`; CSS custom properties `--cf-accent`, `--cf-gap`, etc.
- cf_event meta keys in use:
  - `cf_description` JSON: `{general: HTML, program: HTML, priceInclude: HTML}`
  - `cf_contact` JSON: `{firstname, lastname, email, phone}`
  - `cf_documents` JSON array: `[{url, name}]`
  - `cf_lead_image_url` string, `cf_lead_video_url` string
  - `cf_multimedia_urls` JSON array of URL strings
  - `cf_custom_fields` JSON array: `[{key, label, type, value}]`
  - `cf_reservation_url` string (scalar, EventFieldWidget SELECT only — raw URL output)
- Taxonomies `cf_tag`, `cf_age_group` registered on cf_event CPT
- `src/Widget/FieldConfig.php`, `src/Widget/FieldSorter.php` exist from R-002 Phase 1 — not used by these widgets

---

## What We're NOT Doing

- No EventLocalizationWidget (`cf_localization`) — dedicated widget for location is future scope
- No EventInstructionsWidget — use EventFieldWidget with description subfields instead
- No cf_general_terms widget
- No abstract base Widget class — copy the pattern to avoid indirection (3 widgets ≠ abstraction threshold)
- No Elementor Pro dynamic tags — widget-only approach keeps it compatible with free Elementor
- No custom field key discovery (no SELECT populated from actual meta values) — user types key free-text
- No cf_video_urls widget (video gallery) — out of scope; cf_multimedia_urls covers the main gallery use case

---

## Design Decisions

### EventFieldWidget SELECT options

```
post_title           → "Tytuł imprezy"
cf_reservation_url   → "URL rezerwacji"
cf_lead_image_url    → "URL zdjęcia głównego (surowy)"
cf_lead_video_url    → "URL wideo głównego (surowy)"
cf_desc_general      → "Opis ogólny"        (maps to cf_description.general)
cf_desc_program      → "Program"             (maps to cf_description.program)
cf_desc_price_include → "Co w cenie"         (maps to cf_description.priceInclude)
custom               → "Pole własne (klucz)" (reads from cf_custom_fields by key)
```

`cf_lead_image_url` and `cf_lead_video_url` in the SELECT output the **raw URL** only. `EventLeadImageWidget` and `EventLeadVideoWidget` produce proper HTML elements — the distinction is intentional.

### Render mode (field != custom)

| Mode | Logic |
|---|---|
| `text` | `esc_html($value)` |
| `html` | `wp_kses_post($value)` |
| `auto` | `strip_tags($value) !== $value` → `wp_kses_post`; else → `esc_html` |

### Custom field resolution (field = custom)

Decode `cf_custom_fields` JSON array; find `{key}` match; apply render mode matching `EventSessionsWidget::renderCustomFieldValue()` type logic (html/number/date/boolean/text), then fall through to the widget's `render_mode` for string/unknown types.

### Empty state

Content control `editor_placeholder` TEXT on all widgets. Shown only when `\Elementor\Plugin::$instance->editor->is_edit_mode()` returns `true` AND the field value is empty. No frontend output when empty.

### Style controls

Widgets with `show_label` (EventFieldWidget, EventContactWidget, EventDocumentsWidget, EventTagsWidget, EventAgeGroupWidget): Style tab has **two independent subsections**:
- **Nagłówek** (`cf-field__label` / `cf-contact-box__label` etc.): `Group_Control_Typography` + `COLOR` — lets tenant style the label as H2, bold, different color, etc.
- **Wartość** (primary content element): `Group_Control_Typography` + `COLOR`

This allows heading-style labels without a separate Elementor Heading widget, while keeping layout-level conditional hiding simple (if field is empty, both label and value disappear together).

Media widgets (Lead Image, Lead Video): Style tab with `max_width` SLIDER + `border_radius` SLIDER instead of typography.

Gallery widget: Style tab with `gap` SLIDER + `img_radius` SLIDER (condition: built-in mode only).

### Gallery two modes

**Built-in:** renders `.cf-gallery__grid` with `<figure class="cf-gallery__item">` thumbnails; a single `<dialog>` element used as lightbox; ~10 lines of inline `<script>` (vanilla JS, no deps) for click-to-open + backdrop-click-to-close. Dialog ID: `cf-gal-{widget_id}`.

**Custom:** renders `<div class="{custom_class}" data-{custom_attr}='[json url array]'></div>`. Tenant provides their own JS library. Default `custom_attr` = `cf-gallery`.

### FieldValueRenderer utility

Pure-PHP service in `src/Widget/FieldValueRenderer.php` with one public method:
```php
public function applyRenderMode(string $value, string $mode): string
```
Handles the text/html/auto branching. No WP dependency. Unit-testable without Brain\Monkey WP function mocking.

---

## Phase 1: EventFieldWidget

### Overview

Create the base scalar-field widget. Establishes the pattern (controls structure, meta resolution, edit-mode placeholder) that phases 2–5 follow. Introduces `FieldValueRenderer` as a unit-testable helper.

### Changes Required

**`src/Widget/FieldValueRenderer.php`** — new
- `final class FieldValueRenderer`
- `public function applyRenderMode(string $value, string $mode): string`
  - `'text'` → `esc_html($value)`
  - `'html'` → `wp_kses_post($value)`
  - `'auto'` → `strip_tags($value) !== $value ? wp_kses_post($value) : esc_html($value)`

**`src/Presentation/EventFieldWidget.php`** — new
- `final class EventFieldWidget extends \Elementor\Widget_Base`
- `get_name()` → `'campsflow_event_field'`, `get_title()` → `'CampsFlow — Pole imprezy'`, `get_icon()` → `'eicon-text'`, `get_categories()` → `array( 'campsflow' )`
- Content section controls:
  - `field` SELECT (8 options above), default `'post_title'`
  - `custom_key` TEXT, condition `field == custom`
  - `render_mode` SELECT (`text` / `html` / `auto`), default `'auto'`, condition `field != custom`
  - `show_label` SWITCHER
  - `label_text` TEXT, condition `show_label == yes`
  - `editor_placeholder` TEXT
- Style section controls (two subsections):
  - **Nagłówek pola:** `Group_Control_Typography` name `'label_typography'`, selector `{{WRAPPER}} .cf-field__label`; `label_color` COLOR → `{{WRAPPER}} .cf-field__label { color: {{VALUE}} }`
  - **Wartość pola:** `Group_Control_Typography` name `'field_typography'`, selector `{{WRAPPER}} .cf-field__value`; `field_color` COLOR → `{{WRAPPER}} .cf-field__value { color: {{VALUE}} }`
- `render()`:
  1. `$postId = (int) get_the_ID();`
  2. If `!$postId`: in edit mode → echo placeholder paragraph; return
  3. Resolve `$value` via `resolveFieldValue($postId, $field, $customKey)`
  4. If `$value === ''`: in edit mode + placeholder set → echo placeholder; return
  5. Output: `<div class="cf-field">` + optional `<span class="cf-field__label">` + `<div class="cf-field__value">` + rendered value + `</div></div>`
- `private resolveFieldValue(int $postId, string $field, string $customKey): string`
  - `post_title` → `(string) get_the_title($postId)`
  - `cf_reservation_url`, `cf_lead_image_url`, `cf_lead_video_url` → `(string) get_post_meta($postId, $field, true)`
  - `cf_desc_general`, `cf_desc_program`, `cf_desc_price_include` → `resolveDescriptionSubfield($postId, $field)`
  - `custom` → `resolveCustomField($postId, $customKey)`
  - default → `''`
- `private resolveDescriptionSubfield(int $postId, string $fieldKey): string`
  - `get_post_meta($postId, 'cf_description', true)` → json_decode → return subfield ('general' / 'program' / 'priceInclude') as string
- `private resolveCustomField(int $postId, string $customKey): string`
  - Decode `cf_custom_fields`; find `{key}` match; return `(string) ($item['value'] ?? '')`
- `private renderValue(string $value, string $mode): string`
  - delegates to `(new FieldValueRenderer())->applyRenderMode($value, $mode)`

**`tests/Unit/Widget/FieldValueRendererTest.php`** — new
- Brain\Monkey setup/teardown in setUp/tearDown
- Mock `esc_html` as passthrough (`when('esc_html')->returnArg()`)
- Mock `wp_kses_post` as passthrough (`when('wp_kses_post')->returnArg()`)
- Test: `applyRenderMode('plain text', 'auto')` returns `'plain text'` (no HTML, esc_html branch)
- Test: `applyRenderMode('<p>html</p>', 'auto')` returns `'<p>html</p>'` (HTML detected, wp_kses_post branch)
- Test: `applyRenderMode('<p>html</p>', 'text')` returns `'<p>html</p>'` (text mode forces esc_html — passthrough in test)
- Test: `applyRenderMode('plain', 'html')` returns `'plain'` (html mode forces wp_kses_post — passthrough in test)
- Test: empty string → returns `''` in all modes

**`src/Presentation/ElementorIntegration.php`** — modify `registerWidget()`: add `$manager->register( new EventFieldWidget() )`

**`phpstan.neon`** — add `src/Presentation/EventFieldWidget.php` to `excludePaths`; remove dead `src/Presentation/EventMetaWidget.php` entry

### Success Criteria

#### Automated Verification:
- [ ] `npm run test:unit` passes (FieldValueRendererTest all green)
- [ ] `npm run lint` passes (PHPCS clean)
- [ ] `npm run analyse` passes (PHPStan level 8)

#### Manual Verification:
- [ ] EventFieldWidget appears in Elementor widget panel under "CampsFlow"
- [ ] Selecting `cf_desc_general` on a cf_event post renders HTML description with `auto` mode
- [ ] Editor placeholder text shows in Elementor editor on a non-event page

---

## Phase 2: EventContactWidget + EventDocumentsWidget

### Overview

Two structured-data widgets. Decode a JSON meta value and render using existing CSS classes. No new utility classes needed — simpler than Phase 1.

### Changes Required

**`src/Presentation/EventContactWidget.php`** — new
- `get_name()` → `'campsflow_event_contact'`, `get_title()` → `'CampsFlow — Kontakt'`, `get_icon()` → `'eicon-person'`
- Content: `show_label` SWITCHER, `label_text` TEXT (condition show_label), `editor_placeholder` TEXT
- Style: `Group_Control_Typography` → `{{WRAPPER}} .cf-contact-box`; `contact_color` COLOR → `{{WRAPPER}} .cf-contact-box { color: {{VALUE}} }`
- `render()`: decode `cf_contact` JSON; if empty + edit mode → placeholder; output:
  ```html
  <div class="cf-contact-box">
    <div class="cf-contact-box__name">{firstname} {lastname}</div>
    <a class="cf-contact-link" href="mailto:{email}">{email}</a>
    <a class="cf-contact-link" href="tel:{phone}">{phone}</a>
  </div>
  ```
  All values via `esc_html()` / `esc_url()` / `esc_attr()`

**`src/Presentation/EventDocumentsWidget.php`** — new
- `get_name()` → `'campsflow_event_documents'`, `get_title()` → `'CampsFlow — Dokumenty'`, `get_icon()` → `'eicon-document-file'`
- Content: `show_label` SWITCHER, `label_text` TEXT (condition), `show_icon` SWITCHER (default `'yes'`), `editor_placeholder` TEXT
- Style: `Group_Control_Typography` → `{{WRAPPER}} .cf-docs-list`; `link_color` COLOR → `{{WRAPPER}} .cf-doc-link { color: {{VALUE}} }`
- `render()`: decode `cf_documents` JSON array; if empty + edit mode → placeholder; output:
  ```html
  <ul class="cf-docs-list">
    <li><a class="cf-doc-link" href="{url}" target="_blank" rel="noopener">{name}</a></li>
  </ul>
  ```

**`src/Presentation/ElementorIntegration.php`** — add `EventContactWidget` + `EventDocumentsWidget` registrations

**`phpstan.neon`** — add both new files to `excludePaths`

### Success Criteria

#### Automated Verification:
- [ ] `npm run test:unit` passes
- [ ] `npm run lint` passes
- [ ] `npm run analyse` passes

#### Manual Verification:
- [ ] Both widgets appear in Elementor panel under "CampsFlow"
- [ ] EventContactWidget renders name + clickable email + clickable phone
- [ ] EventDocumentsWidget renders clickable document list with file names

---

## Phase 3: EventLeadImageWidget + EventLeadVideoWidget

### Overview

Media widgets that produce proper HTML elements. `EventLeadVideoWidget` detects YouTube/Vimeo URLs for iframe embeds; other URLs get a `<video>` element.

### Changes Required

**`src/Presentation/EventLeadImageWidget.php`** — new
- `get_name()` → `'campsflow_event_lead_image'`, `get_title()` → `'CampsFlow — Zdjęcie główne'`, `get_icon()` → `'eicon-image'`
- Content: `alt_text` TEXT (override; empty = post title), `link_url` URL, `open_new_tab` SWITCHER, `editor_placeholder` TEXT
- Style: `max_width` SLIDER px 50–1920 default 100% → `{{WRAPPER}} .cf-lead-img { max-width: {{SIZE}}{{UNIT}} }`; `img_radius` SLIDER 0–64 → `{{WRAPPER}} .cf-lead-img { border-radius: {{SIZE}}{{UNIT}} }`
- `render()`: get `cf_lead_image_url`; if empty + edit mode → placeholder; `$alt = $s['alt_text'] ?: get_the_title($postId)`;
  wrap in optional `<a>` if `link_url` set; output `<img class="cf-lead-img" src="{url}" alt="{alt}" loading="lazy">`

**`src/Presentation/EventLeadVideoWidget.php`** — new
- `get_name()` → `'campsflow_event_lead_video'`, `get_title()` → `'CampsFlow — Wideo główne'`, `get_icon()` → `'eicon-youtube'`
- Content: `aspect_ratio` SELECT (`16-9` / `4-3` / `1-1`, default `16-9`), `editor_placeholder` TEXT
- Style: `max_width` SLIDER 200–1920 default 100% → `{{WRAPPER}} .cf-video-wrap { max-width: {{SIZE}}{{UNIT}} }`
- `render()`: get `cf_lead_video_url`; if empty + edit mode → placeholder
  - YouTube detect: `youtu.be/` or `youtube.com/watch?v=` → extract video ID → `<iframe src="https://www.youtube.com/embed/{id}">`
  - Vimeo detect: `vimeo.com/{id}` → `<iframe src="https://player.vimeo.com/video/{id}">`
  - Other → `<video src="{url}" controls>`
  - Wrap in `<div class="cf-video-wrap" style="--cf-ratio:{padding-top%}">` (16:9=56.25%, 4:3=75%, 1:1=100%)
  - `<iframe>` attributes: `frameborder="0"`, `allowfullscreen`, `loading="lazy"`

**`src/Presentation/ElementorIntegration.php`** — add both registrations

**`phpstan.neon`** — add both to `excludePaths`

### Success Criteria

#### Automated Verification:
- [ ] `npm run test:unit` passes
- [ ] `npm run lint` passes
- [ ] `npm run analyse` passes

#### Manual Verification:
- [ ] EventLeadImageWidget renders `<img loading="lazy">` with the stored image URL
- [ ] EventLeadVideoWidget renders `<iframe>` inside `.cf-video-wrap` for a YouTube URL
- [ ] Aspect ratio control changes the video container's padding-top percentage

---

## Phase 4: EventGalleryWidget

### Overview

Gallery widget with two modes. Built-in mode uses native `<dialog>` element as lightbox with ~10 lines of inline vanilla JS. Custom mode emits a configurable CSS class + `data-*` JSON attribute for tenant's own component library.

### Changes Required

**`src/Presentation/EventGalleryWidget.php`** — new
- `get_name()` → `'campsflow_event_gallery'`, `get_title()` → `'CampsFlow — Galeria'`, `get_icon()` → `'eicon-gallery-grid'`
- Content controls:
  - `mode` SELECT (`built-in` / `custom`), default `'built-in'`
  - `columns` NUMBER 2–6 default 3, condition `mode == built-in` → `{{WRAPPER}} .cf-gallery__grid { --cf-gallery-cols: {{VALUE}} }`
  - `custom_class` TEXT, condition `mode == custom`
  - `custom_attr` TEXT default `'cf-gallery'`, condition `mode == custom`
  - `editor_placeholder` TEXT
- Style controls:
  - `gallery_gap` SLIDER 4–48px default 12, condition `mode == built-in` → `{{WRAPPER}} .cf-gallery__grid { gap: {{SIZE}}{{UNIT}} }`
  - `img_radius` SLIDER 0–32 default 6, condition `mode == built-in` → `{{WRAPPER}} .cf-gallery__item img { border-radius: {{SIZE}}{{UNIT}} }`
- `render()`:
  - Decode `cf_multimedia_urls` JSON array; if empty + edit mode → placeholder; if empty → return
  - Built-in mode:
    1. `$dialogId = 'cf-gal-' . esc_attr( $this->get_id() )`
    2. Output `.cf-gallery__grid` with `<figure class="cf-gallery__item">` per URL; each figure has `<img class="cf-gallery__img" src="{url}" loading="lazy" data-dialog="{dialogId}">`
    3. Output `<dialog id="{dialogId}" class="cf-gallery-dialog"><img class="cf-gallery-dialog__img" src="" alt=""><button class="cf-gallery-dialog__close">×</button></dialog>`
    4. Call `echoInlineDialogScript($dialogId)`
  - Custom mode:
    1. `$class = sanitize_html_class( $s['custom_class'] )`
    2. `$attr = sanitize_key( $s['custom_attr'] )`
    3. `$json = wp_json_encode( $urls )`
    4. Output `<div class="{$class}" data-{$attr}='{$json}'></div>`
- `private echoInlineDialogScript(string $dialogId): void`
  ```html
  <script>
  (function(){
    var d=document.getElementById('{dialogId}'),i=d.querySelector('img');
    document.querySelectorAll('[data-dialog="{dialogId}"]').forEach(function(el){
      el.addEventListener('click',function(){i.src=el.src;d.showModal();});
    });
    d.addEventListener('click',function(e){if(e.target===d)d.close();});
    d.querySelector('button').addEventListener('click',function(){d.close();});
  })();
  </script>
  ```
  Inline script is safe — no user-supplied values interpolated; only `$dialogId` which is `esc_attr`-escaped.

**`src/Presentation/ElementorIntegration.php`** — add `EventGalleryWidget` registration

**`phpstan.neon`** — add to `excludePaths`

### Success Criteria

#### Automated Verification:
- [ ] `npm run test:unit` passes
- [ ] `npm run lint` passes
- [ ] `npm run analyse` passes

#### Manual Verification:
- [ ] Built-in gallery renders a grid of images from cf_multimedia_urls
- [ ] Clicking an image opens the `<dialog>` with the full-size image
- [ ] Clicking outside the image (dialog backdrop) closes the lightbox
- [ ] Custom mode outputs `data-{attr}='["url1","url2"]'` attribute on the wrapper div

---

## Phase 5: EventTagsWidget + EventAgeGroupWidget + CI gate

### Overview

Taxonomy pill widgets + final housekeeping. Clean up dead phpstan exclusion (verify EventMetaWidget entry removed in Phase 1), run full CI suite.

### Changes Required

**`src/Presentation/EventTagsWidget.php`** — new
- `get_name()` → `'campsflow_event_tags'`, `get_title()` → `'CampsFlow — Tagi'`, `get_icon()` → `'eicon-tags'`
- Content: `show_label` SWITCHER, `label_text` TEXT (condition), `editor_placeholder` TEXT
- Style: `pill_color` COLOR → `{{WRAPPER}} .cf-tag { color: {{VALUE}} }`; `pill_bg` COLOR → `{{WRAPPER}} .cf-tag { background: {{VALUE}} }`
- `render()`: `get_the_terms( get_the_ID(), 'cf_tag' )`; if `false` or empty + edit mode → placeholder
  ```html
  <div class="cf-tags">
    <span class="cf-tag">{term->name}</span>
    ...
  </div>
  ```

**`src/Presentation/EventAgeGroupWidget.php`** — new
- `get_name()` → `'campsflow_event_age_groups'`, `get_title()` → `'CampsFlow — Grupy wiekowe'`, `get_icon()` → `'eicon-person'`
- Same pattern as EventTagsWidget but taxonomy = `'cf_age_group'`, pill class = `'cf-tag cf-tag--age'`
- Style controls: `pill_color` + `pill_bg` → `{{WRAPPER}} .cf-tag--age`

**`src/Presentation/ElementorIntegration.php`** — add both registrations; final state registers 8 widgets total:
```php
$manager->register( new ElementorWidget() );
$manager->register( new EventSessionsWidget() );
$manager->register( new EventFieldWidget() );
$manager->register( new EventContactWidget() );
$manager->register( new EventDocumentsWidget() );
$manager->register( new EventLeadImageWidget() );
$manager->register( new EventLeadVideoWidget() );
$manager->register( new EventGalleryWidget() );
$manager->register( new EventTagsWidget() );
$manager->register( new EventAgeGroupWidget() );
```

**`phpstan.neon`** — add both to `excludePaths`; verify `EventMetaWidget.php` entry is absent

### Success Criteria

#### Automated Verification:
- [ ] `npm run test:unit` passes (all tests including FieldValueRendererTest)
- [ ] `npm run lint` passes (all 10 new + 2 modified files PHPCS clean)
- [ ] `npm run analyse` passes (PHPStan level 8)

#### Manual Verification:
- [ ] EventTagsWidget shows taxonomy tags as `.cf-tag` pills on a cf_event post
- [ ] EventAgeGroupWidget shows age group tags as `.cf-tag.cf-tag--age` pills
- [ ] All 8 CampsFlow-specific widgets visible in Elementor widget panel
- [ ] A complete cf_event template built from all 8 new widgets renders without errors

---

## Progress

### Phase 1: EventFieldWidget

#### Automated
- [x] 1.1 Create src/Widget/FieldValueRenderer.php — 7cf0496
- [x] 1.2 Create tests/Unit/Widget/FieldValueRendererTest.php — 7cf0496
- [x] 1.3 Create src/Presentation/EventFieldWidget.php — 7cf0496
- [x] 1.4 Register EventFieldWidget in ElementorIntegration — 7cf0496
- [x] 1.5 Update phpstan.neon (add EventFieldWidget, remove dead EventMetaWidget entry) — 7cf0496
- [x] 1.6 npm run test:unit passes — 7cf0496
- [x] 1.7 npm run lint passes — 7cf0496
- [x] 1.8 npm run analyse passes — 7cf0496

#### Manual
- [x] 1.9 Widget appears in Elementor panel under CampsFlow — 7cf0496
- [x] 1.10 Selecting cf_desc_general renders HTML description with auto mode — 7cf0496
- [x] 1.11 Editor placeholder shows on a non-event page — 7cf0496

### Phase 2: EventContactWidget + EventDocumentsWidget

#### Automated
- [x] 2.1 Create src/Presentation/EventContactWidget.php — 333ffbc
- [x] 2.2 Create src/Presentation/EventDocumentsWidget.php — 333ffbc
- [x] 2.3 Register both in ElementorIntegration — 333ffbc
- [x] 2.4 Update phpstan.neon (add both) — 333ffbc
- [x] 2.5 npm run test:unit passes — 333ffbc
- [x] 2.6 npm run lint passes — 333ffbc
- [x] 2.7 npm run analyse passes — 333ffbc

#### Manual
- [ ] 2.8 Both widgets appear in Elementor panel
- [ ] 2.9 EventContactWidget renders name + clickable email + phone links
- [ ] 2.10 EventDocumentsWidget renders clickable document list

### Phase 3: EventLeadImageWidget + EventLeadVideoWidget

#### Automated
- [x] 3.1 Create src/Presentation/EventLeadImageWidget.php — af268c0
- [x] 3.2 Create src/Presentation/EventLeadVideoWidget.php — af268c0
- [x] 3.3 Register both in ElementorIntegration — af268c0
- [x] 3.4 Update phpstan.neon (add both) — af268c0
- [x] 3.5 npm run test:unit passes — af268c0
- [x] 3.6 npm run lint passes — af268c0
- [x] 3.7 npm run analyse passes — af268c0

#### Manual
- [ ] 3.8 EventLeadImageWidget renders <img loading="lazy"> with correct src
- [ ] 3.9 EventLeadVideoWidget renders iframe inside .cf-video-wrap for YouTube URL
- [ ] 3.10 Aspect ratio control changes video container proportions

### Phase 4: EventGalleryWidget

#### Automated
- [x] 4.1 Create src/Presentation/EventGalleryWidget.php
- [x] 4.2 Register in ElementorIntegration
- [x] 4.3 Update phpstan.neon
- [x] 4.4 npm run test:unit passes
- [x] 4.5 npm run lint passes
- [x] 4.6 npm run analyse passes

#### Manual
- [x] 4.7 Built-in gallery renders image grid from cf_multimedia_urls
- [x] 4.8 Clicking an image opens <dialog> with full-size image
- [x] 4.9 Clicking dialog backdrop closes the lightbox
- [x] 4.10 Custom mode outputs data-{attr}='[json]' on wrapper div

### Phase 5: EventTagsWidget + EventAgeGroupWidget + CI gate

#### Automated
- [ ] 5.1 Create src/Presentation/EventTagsWidget.php
- [ ] 5.2 Create src/Presentation/EventAgeGroupWidget.php
- [ ] 5.3 Register both in ElementorIntegration (10 total)
- [ ] 5.4 Update phpstan.neon (add both, verify EventMetaWidget entry absent)
- [ ] 5.5 npm run test:unit passes
- [ ] 5.6 npm run lint passes
- [ ] 5.7 npm run analyse passes

#### Manual
- [ ] 5.8 EventTagsWidget renders cf_tag terms as pills
- [ ] 5.9 EventAgeGroupWidget renders cf_age_group terms as pills
- [ ] 5.10 All 8 CampsFlow widgets visible in Elementor panel
- [ ] 5.11 Complete cf_event template with all 8 widgets renders without errors
