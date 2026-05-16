<?php
/**
 * Template: Szczegóły imprezy z listą turnusów
 * URL: /obozy/{slug}/
 *
 * Theme override: copy this file to your theme root as single-cf_event.php
 */
defined('ABSPATH') || exit;

use Campsflow\PostType\SessionPostType;
use Campsflow\PostType\PostStatus;
use Campsflow\Sync\AvailabilityBucket;

get_header();

if (! have_posts()) {
    get_footer();
    return;
}

the_post();

$eventId  = get_the_ID();
$location = json_decode((string) get_post_meta($eventId, 'cf_location', true), true);
$city     = is_array($location) ? ($location['city'] ?? '') : '';
$dest     = is_array($location) ? ($location['destination'] ?? '') : '';
$locName  = is_array($location) ? ($location['name'] ?? '') : '';
$regPage  = get_option('campsflow_registration_page', '/rejestracja/');

$sessions = new WP_Query([
    'post_type'      => SessionPostType::SLUG,
    'post_status'    => 'publish',
    'post_parent'    => $eventId,
    'posts_per_page' => -1,
    'orderby'        => 'meta_value',
    'meta_key'       => 'cf_date_from',
    'order'          => 'ASC',
]);
?>

<main id="cf-main" class="cf-page cf-page--single">

    <?php if (has_post_thumbnail()): ?>
    <div class="cf-event-hero">
        <?php the_post_thumbnail('large', ['class' => 'cf-event-hero__image', 'loading' => 'eager']); ?>
    </div>
    <?php endif; ?>

    <div class="cf-event-layout">

        <article class="cf-event-body">

            <h1 class="cf-event-body__title"><?php the_title(); ?></h1>

            <?php if ($dest || $city): ?>
            <p class="cf-event-body__location">
                <span class="dashicons dashicons-location"></span>
                <?php
                $parts = array_filter([$dest, $locName, $city]);
                echo esc_html(implode(' · ', $parts));
                ?>
            </p>
            <?php endif; ?>

            <div class="cf-event-body__tags">
                <?php
                $tags = get_the_terms($eventId, 'cf_tag');
                if ($tags && ! is_wp_error($tags)) {
                    foreach ($tags as $tag) {
                        echo '<span class="cf-tag">' . esc_html($tag->name) . '</span>';
                    }
                }

                $ages = get_the_terms($eventId, 'cf_age_group');
                if ($ages && ! is_wp_error($ages)) {
                    foreach ($ages as $age) {
                        echo '<span class="cf-tag cf-tag--age">' . esc_html($age->name) . '</span>';
                    }
                }
                ?>
            </div>

            <div class="cf-event-body__description">
                <?php the_content(); ?>
            </div>

        </article>

        <aside class="cf-event-sidebar">

            <div class="cf-sessions-box">
                <h2 class="cf-sessions-box__title">
                    <?php esc_html_e('Dostępne terminy', 'campsflow'); ?>
                </h2>

                <?php if ($sessions->have_posts()): ?>
                <ul class="cf-sessions-box__list">
                <?php while ($sessions->have_posts()): $sessions->the_post();
                    $sId       = get_the_ID();
                    $dateFrom  = (string) get_post_meta($sId, 'cf_date_from', true);
                    $dateTo    = (string) get_post_meta($sId, 'cf_date_to', true);
                    $price     = (int)   get_post_meta($sId, 'cf_price', true);
                    $bucket    = AvailabilityBucket::tryFrom(
                        (string) get_post_meta($sId, 'cf_availability', true)
                    ) ?? AvailabilityBucket::Available;
                    $sessionUuid = (string) get_post_meta($sId, 'cf_session_id', true);
                    $isFull      = $bucket === AvailabilityBucket::Full;
                    $registerUrl = $isFull ? '#' : esc_url(add_query_arg('session', $sessionUuid, $regPage));

                    $f = $dateFrom ? date_create($dateFrom) : null;
                    $t = $dateTo   ? date_create($dateTo)   : null;
                    $dateLabel = $f
                        ? ($f->format('j M') . ($t ? '–' . $t->format('j M Y') : ''))
                        : '';
                    $priceLabel = $price ? number_format($price / 100, 0, ',', ' ') . ' zł' : '';
                ?>
                    <li class="cf-sessions-box__item <?php echo $isFull ? 'cf-sessions-box__item--full' : ''; ?>">
                        <div class="cf-sessions-box__dates"><?php echo esc_html($dateLabel); ?></div>
                        <div class="cf-sessions-box__meta">
                            <?php if ($priceLabel): ?>
                            <span class="cf-sessions-box__price"><?php echo esc_html($priceLabel); ?></span>
                            <?php endif; ?>
                            <?php if ($bucket !== AvailabilityBucket::Available && $bucket->label()): ?>
                            <span class="cf-badge cf-badge--<?php echo esc_attr($bucket->value); ?>">
                                <?php echo esc_html($bucket->label()); ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($isFull): ?>
                        <span class="cf-btn cf-btn--disabled"><?php esc_html_e('Brak miejsc', 'campsflow'); ?></span>
                        <?php else: ?>
                        <a class="cf-btn" href="<?php echo $registerUrl; ?>"><?php esc_html_e('Zapisz się', 'campsflow'); ?></a>
                        <?php endif; ?>
                    </li>
                <?php endwhile; wp_reset_postdata(); ?>
                </ul>
                <?php else: ?>
                <p class="cf-empty"><?php esc_html_e('Brak dostępnych terminów.', 'campsflow'); ?></p>
                <?php endif; ?>
            </div>

        </aside>

    </div>

</main>

<?php get_footer(); ?>
