<?php
/**
 * Template: Szczegóły imprezy (single cf_event)
 * URL: /obozy/{slug}/
 * Theme override: copy to theme root as single-cf_event.php
 */
defined('ABSPATH') || exit;

use Campsflow\PostType\SessionPostType;
use Campsflow\Sync\AvailabilityBucket;

get_header();

if (! have_posts()) { get_footer(); return; }
the_post();

$eventId      = (int) get_the_ID();
$regPage      = get_option('campsflow_registration_page', '/rejestracja/');
$reservUrl    = (string) get_post_meta($eventId, 'cf_reservation_url', true);

$locRaw       = (string) get_post_meta($eventId, 'cf_localization', true);
$loc          = $locRaw ? (json_decode($locRaw, true) ?? []) : [];
$addr         = is_array($loc['address'] ?? null) ? $loc['address'] : [];
$gps          = is_array($loc['gps'] ?? null) ? $loc['gps'] : null;

$descRaw      = (string) get_post_meta($eventId, 'cf_description', true);
$desc         = $descRaw ? (json_decode($descRaw, true) ?? []) : [];

$docsRaw      = (string) get_post_meta($eventId, 'cf_documents', true);
$docs         = $docsRaw ? (json_decode($docsRaw, true) ?? []) : [];

$termsRaw     = (string) get_post_meta($eventId, 'cf_general_terms', true);
$terms        = $termsRaw ? (json_decode($termsRaw, true) ?? []) : [];

$instrRaw     = (string) get_post_meta($eventId, 'cf_instructions', true);
$instr        = $instrRaw ? (json_decode($instrRaw, true) ?? []) : [];

$contactRaw   = (string) get_post_meta($eventId, 'cf_contact', true);
$contact      = $contactRaw ? (json_decode($contactRaw, true) ?? []) : [];

$leadImage     = (string) get_post_meta($eventId, 'cf_lead_image_url', true);
$leadVideo     = (string) get_post_meta($eventId, 'cf_lead_video_url', true);

$multimediaRaw = (string) get_post_meta($eventId, 'cf_multimedia_urls', true);
$galleryUrls   = $multimediaRaw ? (json_decode($multimediaRaw, true) ?? []) : [];
if (! is_array($galleryUrls)) {
    $galleryUrls = [];
}

$videoUrlsRaw = (string) get_post_meta($eventId, 'cf_video_urls', true);
$videoUrls    = $videoUrlsRaw ? (json_decode($videoUrlsRaw, true) ?? []) : [];
if (! is_array($videoUrls)) {
    $videoUrls = [];
}
if ($leadVideo && ! in_array($leadVideo, $videoUrls, true)) {
    array_unshift($videoUrls, $leadVideo);
}

$sessions = new WP_Query([
    'post_type'      => SessionPostType::SLUG,
    'post_status'    => 'publish',
    'post_parent'    => $eventId,
    'posts_per_page' => -1,
    'orderby'        => 'meta_value',
    'meta_key'       => 'cf_date_from',
    'order'          => 'ASC',
]);

$transportIcons = [
    'bus'   => '🚌',
    'train' => '🚆',
    'plain' => '✈️',
    'own'   => '🚗',
];
?>

<main class="cf-page cf-page--single">

  <?php if ($leadImage): ?>
  <div class="cf-event-hero">
    <img class="cf-event-hero__image" src="<?php echo esc_url($leadImage); ?>" alt="<?php echo esc_attr(get_the_title()); ?>" loading="eager">
  </div>
  <?php endif; ?>

  <div class="cf-event-layout">

    <!-- ── Left column ─────────────────────────────────────────── -->
    <article class="cf-event-body">

      <h1 class="cf-event-body__title"><?php the_title(); ?></h1>

      <?php
      $locParts = array_filter([
          $loc['destination'] ?? '',
          $loc['name'] ?? '',
          $addr['city'] ?? '',
      ]);
      if ($locParts):
      ?>
      <p class="cf-event-body__location">
        <span class="dashicons dashicons-location"></span>
        <?php echo esc_html(implode(' · ', $locParts)); ?>
        <?php if ($gps && isset($gps['lat'], $gps['lng'])): ?>
        <a href="https://maps.google.com/?q=<?php echo esc_attr($gps['lat'] . ',' . $gps['lng']); ?>" target="_blank" rel="noopener" class="cf-gps-link" title="<?php esc_attr_e('Pokaż na mapie', 'campsflow'); ?>">
          <span class="dashicons dashicons-location-alt"></span>
        </a>
        <?php endif; ?>
      </p>
      <?php endif; ?>

      <!-- Tags -->
      <?php
      $tags = get_the_terms($eventId, 'cf_tag');
      $ages = get_the_terms($eventId, 'cf_age_group');
      if (($tags && ! is_wp_error($tags)) || ($ages && ! is_wp_error($ages))):
      ?>
      <div class="cf-event-body__tags">
        <?php if ($tags && ! is_wp_error($tags)):
          foreach ($tags as $tag): ?>
          <span class="cf-tag"><?php echo esc_html($tag->name); ?></span>
        <?php endforeach; endif; ?>
        <?php if ($ages && ! is_wp_error($ages)):
          foreach ($ages as $age): ?>
          <span class="cf-tag cf-tag--age"><?php echo esc_html($age->name); ?></span>
        <?php endforeach; endif; ?>
      </div>
      <?php endif; ?>

      <!-- Tabbed description -->
      <?php
      $hasProgram   = ! empty($desc['program']);
      $hasPriceIncl = ! empty($desc['priceInclude']);
      ?>
      <?php if (! empty($desc['general']) || $hasProgram || $hasPriceIncl): ?>
      <div class="cf-tabs-container">
        <div class="cf-tabs-nav" role="tablist">
          <button class="cf-tab-btn active" data-tab="general" role="tab"><?php esc_html_e('Opis obozu', 'campsflow'); ?></button>
          <?php if ($hasProgram): ?>
          <button class="cf-tab-btn" data-tab="program" role="tab"><?php esc_html_e('Program', 'campsflow'); ?></button>
          <?php endif; ?>
          <?php if ($hasPriceIncl): ?>
          <button class="cf-tab-btn" data-tab="price" role="tab"><?php esc_html_e('Co w cenie', 'campsflow'); ?></button>
          <?php endif; ?>
          <?php if (! empty($instr['howToPrepare']) || ! empty($instr['whatToTake'])): ?>
          <button class="cf-tab-btn" data-tab="instructions" role="tab"><?php esc_html_e('Jak się przygotować', 'campsflow'); ?></button>
          <?php endif; ?>
        </div>
        <div class="cf-tab-panel active" data-panel="general">
          <?php echo wp_kses_post($desc['general'] ?? get_the_content()); ?>
        </div>
        <?php if ($hasProgram): ?>
        <div class="cf-tab-panel" data-panel="program">
          <?php echo wp_kses_post($desc['program']); ?>
        </div>
        <?php endif; ?>
        <?php if ($hasPriceIncl): ?>
        <div class="cf-tab-panel" data-panel="price">
          <?php echo wp_kses_post($desc['priceInclude']); ?>
        </div>
        <?php endif; ?>
        <?php if (! empty($instr['howToPrepare']) || ! empty($instr['whatToTake'])): ?>
        <div class="cf-tab-panel" data-panel="instructions">
          <?php if (! empty($instr['howToPrepare'])): ?>
          <h3><?php esc_html_e('Jak się przygotować', 'campsflow'); ?></h3>
          <?php echo wp_kses_post($instr['howToPrepare']); ?>
          <?php endif; ?>
          <?php if (! empty($instr['whatToTake'])): ?>
          <h3><?php esc_html_e('Co zabrać', 'campsflow'); ?></h3>
          <?php echo wp_kses_post($instr['whatToTake']); ?>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Gallery -->
      <?php if (count($galleryUrls) > 1): ?>
      <div class="cf-section cf-gallery">
        <h2 class="cf-section__title"><?php esc_html_e('Galeria', 'campsflow'); ?></h2>
        <div class="cf-gallery__grid">
          <?php foreach ($galleryUrls as $imgUrl):
            if (! is_string($imgUrl) || $imgUrl === '') continue; ?>
          <a href="<?php echo esc_url($imgUrl); ?>" class="cf-gallery__item" target="_blank" rel="noopener">
            <img src="<?php echo esc_url($imgUrl); ?>" alt="" loading="lazy" class="cf-gallery__img">
          </a>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Videos -->
      <?php if (! empty($videoUrls)): ?>
      <div class="cf-section cf-videos">
        <h2 class="cf-section__title"><?php esc_html_e('Wideo', 'campsflow'); ?></h2>
        <?php foreach ($videoUrls as $videoUrl):
          if (! is_string($videoUrl) || $videoUrl === '') continue;
          $embedUrl = null;
          if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $videoUrl, $m)) {
              $embedUrl = 'https://www.youtube-nocookie.com/embed/' . $m[1];
          } elseif (preg_match('/vimeo\.com\/(\d+)/', $videoUrl, $m)) {
              $embedUrl = 'https://player.vimeo.com/video/' . $m[1];
          }
        ?>
          <?php if ($embedUrl): ?>
          <div class="cf-video-wrap">
            <iframe src="<?php echo esc_url($embedUrl); ?>" loading="lazy"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
              allowfullscreen title="<?php esc_attr_e('Film', 'campsflow'); ?>"></iframe>
          </div>
          <?php else: ?>
          <div class="cf-video-wrap">
            <video controls preload="none">
              <source src="<?php echo esc_url($videoUrl); ?>">
            </video>
          </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <!-- General terms -->
      <?php if (! empty(array_filter($terms))): ?>
      <div class="cf-section">
        <h2 class="cf-section__title"><?php esc_html_e('Informacje ogólne', 'campsflow'); ?></h2>
        <dl class="cf-terms-list">
          <?php foreach ([
            'insurance'               => __('Ubezpieczenie', 'campsflow'),
            'drugOrdering'            => __('Leki', 'campsflow'),
            'specialDiet'             => __('Diety specjalne', 'campsflow'),
            'deadlinesAndDocumentsInfo' => __('Terminy i dokumenty', 'campsflow'),
          ] as $key => $label): ?>
          <?php if (! empty($terms[$key])): ?>
          <div class="cf-terms-item">
            <dt><?php echo esc_html($label); ?></dt>
            <dd><?php echo esc_html($terms[$key]); ?></dd>
          </div>
          <?php endif; ?>
          <?php endforeach; ?>
        </dl>
      </div>
      <?php endif; ?>

      <!-- Documents -->
      <?php if (! empty($docs) && is_array($docs)): ?>
      <div class="cf-section">
        <h2 class="cf-section__title"><?php esc_html_e('Dokumenty do pobrania', 'campsflow'); ?></h2>
        <ul class="cf-docs-list">
          <?php foreach ($docs as $doc):
            if (! is_array($doc) || empty($doc['url'])) continue; ?>
          <li>
            <a href="<?php echo esc_url($doc['url']); ?>" target="_blank" rel="noopener" class="cf-doc-link">
              <span class="dashicons dashicons-download"></span>
              <?php echo esc_html($doc['name'] ?? $doc['url']); ?>
            </a>
          </li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>

      <!-- Contact -->
      <?php if (! empty($contact['email']) || ! empty($contact['phone'])): ?>
      <div class="cf-section cf-contact-box">
        <h2 class="cf-section__title"><?php esc_html_e('Kontakt w sprawie obozu', 'campsflow'); ?></h2>
        <?php if (! empty($contact['firstname']) || ! empty($contact['lastname'])): ?>
        <p class="cf-contact-box__name">
          <?php echo esc_html(trim(($contact['firstname'] ?? '') . ' ' . ($contact['lastname'] ?? ''))); ?>
        </p>
        <?php endif; ?>
        <div class="cf-contact-box__links">
          <?php if (! empty($contact['email'])): ?>
          <a href="mailto:<?php echo esc_attr($contact['email']); ?>" class="cf-contact-link">
            <span class="dashicons dashicons-email-alt"></span> <?php echo esc_html($contact['email']); ?>
          </a>
          <?php endif; ?>
          <?php if (! empty($contact['phone'])): ?>
          <a href="tel:<?php echo esc_attr($contact['phone']); ?>" class="cf-contact-link">
            <span class="dashicons dashicons-phone"></span> <?php echo esc_html($contact['phone']); ?>
          </a>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

    </article>

    <!-- ── Right column / sidebar ──────────────────────────────── -->
    <aside class="cf-event-sidebar">

      <div class="cf-sessions-box">
        <h2 class="cf-sessions-box__title"><?php esc_html_e('Dostępne terminy', 'campsflow'); ?></h2>

        <?php if ($sessions->have_posts()): ?>
        <ul class="cf-sessions-box__list">
        <?php while ($sessions->have_posts()): $sessions->the_post();
          $sId         = (int) get_the_ID();
          $dateFrom    = (string) get_post_meta($sId, 'cf_date_from', true);
          $dateTo      = (string) get_post_meta($sId, 'cf_date_to', true);
          $price       = (int)   get_post_meta($sId, 'cf_price_from', true);
          $days        = (int)   get_post_meta($sId, 'cf_number_of_days', true);
          $turnusName  = (string) get_post_meta($sId, 'cf_turnus_name', true);
          $bucket      = AvailabilityBucket::tryFrom((string) get_post_meta($sId, 'cf_availability', true)) ?? AvailabilityBucket::Available;
          $sessionUrl  = (string) get_post_meta($sId, 'cf_reservation_url', true);
          $isFull      = $bucket === AvailabilityBucket::Full;

          $transportRaw  = (string) get_post_meta($sId, 'cf_transport', true);
          $transport     = $transportRaw ? (json_decode($transportRaw, true) ?? []) : [];
          $transportType = (string) ($transport['type'] ?? 'own');
          $transportIcon = $transportIcons[$transportType] ?? '🚗';
          $transportDesc = (string) ($transport['description'] ?? '');

          $f = $dateFrom ? date_create($dateFrom) : null;
          $t = $dateTo   ? date_create($dateTo)   : null;
          $dateLabel  = $f ? ($f->format('j M') . ($t ? '–' . $t->format('j M Y') : '')) : '';
          $priceLabel = $price ? number_format($price / 100, 0, ',', ' ') . ' zł' : '';
        ?>
          <li class="cf-sessions-box__item <?php echo $isFull ? 'cf-sessions-box__item--full' : ''; ?>">
            <?php if ($turnusName): ?>
            <div class="cf-sessions-box__turnus-name"><?php echo esc_html($turnusName); ?></div>
            <?php endif; ?>
            <div class="cf-sessions-box__dates"><?php echo esc_html($dateLabel); ?><?php if ($days): ?> <span class="cf-sessions-box__days">(<?php echo esc_html($days); ?> dni)</span><?php endif; ?></div>
            <div class="cf-sessions-box__meta">
              <?php if ($priceLabel): ?>
              <span class="cf-sessions-box__price"><?php echo esc_html(__('od', 'campsflow') . ' ' . $priceLabel); ?></span>
              <?php endif; ?>
              <?php if ($bucket !== AvailabilityBucket::Available && $bucket->label()): ?>
              <span class="cf-badge cf-badge--<?php echo esc_attr($bucket->value); ?>"><?php echo esc_html($bucket->label()); ?></span>
              <?php endif; ?>
            </div>
            <?php if ($transportDesc): ?>
            <div class="cf-sessions-box__transport" title="<?php echo esc_attr($transportDesc); ?>">
              <span><?php echo $transportIcon; ?></span> <?php echo esc_html($transportDesc); ?>
            </div>
            <?php endif; ?>
            <?php if ($isFull): ?>
            <span class="cf-btn cf-btn--disabled"><?php esc_html_e('Brak miejsc', 'campsflow'); ?></span>
            <?php elseif ($sessionUrl): ?>
            <a class="cf-btn" href="<?php echo esc_url($sessionUrl); ?>" target="_blank" rel="noopener"><?php esc_html_e('Zapisz się', 'campsflow'); ?></a>
            <?php endif; ?>
          </li>
        <?php endwhile; wp_reset_postdata(); ?>
        </ul>
        <?php else: ?>
        <p class="cf-empty"><?php esc_html_e('Brak dostępnych terminów.', 'campsflow'); ?></p>
        <?php endif; ?>
      </div>

      <!-- Localization details -->
      <?php if (! empty($loc)): ?>
      <div class="cf-location-box">
        <h3 class="cf-location-box__title"><?php esc_html_e('Lokalizacja', 'campsflow'); ?></h3>
        <?php if (! empty($addr)): ?>
        <address class="cf-location-box__address">
          <?php if (! empty($addr['address'])): echo esc_html($addr['address']) . '<br>'; endif; ?>
          <?php if (! empty($addr['code']) || ! empty($addr['city'])): ?>
          <?php echo esc_html(trim(($addr['code'] ?? '') . ' ' . ($addr['city'] ?? ''))); ?><br>
          <?php endif; ?>
        </address>
        <?php endif; ?>
        <?php if (! empty($loc['phone'])): ?>
        <a href="tel:<?php echo esc_attr($loc['phone']); ?>" class="cf-location-box__link">
          <span class="dashicons dashicons-phone"></span> <?php echo esc_html($loc['phone']); ?>
        </a>
        <?php endif; ?>
        <?php if (! empty($loc['email'])): ?>
        <a href="mailto:<?php echo esc_attr($loc['email']); ?>" class="cf-location-box__link">
          <span class="dashicons dashicons-email-alt"></span> <?php echo esc_html($loc['email']); ?>
        </a>
        <?php endif; ?>
        <?php if (! empty($loc['webpage'])): ?>
        <a href="<?php echo esc_url($loc['webpage']); ?>" target="_blank" rel="noopener" class="cf-location-box__link">
          <span class="dashicons dashicons-admin-site-alt3"></span> <?php echo esc_html($loc['webpage']); ?>
        </a>
        <?php endif; ?>
        <?php if ($gps && isset($gps['lat'], $gps['lng'])): ?>
        <a href="https://maps.google.com/?q=<?php echo esc_attr($gps['lat'] . ',' . $gps['lng']); ?>" target="_blank" rel="noopener" class="cf-location-box__link">
          <span class="dashicons dashicons-location"></span> <?php esc_html_e('Pokaż na mapie', 'campsflow'); ?>
        </a>
        <?php endif; ?>
      </div>
      <?php endif; ?>

    </aside>

  </div><!-- .cf-event-layout -->

</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.cf-tab-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var tab = this.dataset.tab;
            var container = this.closest('.cf-tabs-container');
            container.querySelectorAll('.cf-tab-btn').forEach(function(b) { b.classList.remove('active'); });
            container.querySelectorAll('.cf-tab-panel').forEach(function(p) { p.classList.remove('active'); });
            this.classList.add('active');
            container.querySelector('.cf-tab-panel[data-panel="' + tab + '"]').classList.add('active');
        });
    });
});
</script>

<?php get_footer(); ?>
