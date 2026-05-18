<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\PostType\SessionPostType;
use Campsflow\Sync\AvailabilityBucket;
use WP_Query;

/**
 * Shortcodes for single event page widgets.
 * Used by WPBakery, Gutenberg and any other builder.
 * Elementor uses its own widget classes directly.
 *
 * [campsflow_event_meta show="location,tags,description"]
 * [campsflow_event_sessions title="Dostępne terminy" button_label="Zapisz się"]
 */
final class EventShortcodes
{
    public function register(): void
    {
        add_shortcode('campsflow_event_meta', [$this, 'renderMeta']);
        add_shortcode('campsflow_event_sessions', [$this, 'renderSessions']);
    }

    /**
     * @param array<string, string>|string $atts
     */
    public function renderMeta(array|string $atts): string
    {
        $atts   = shortcode_atts(
            ['show' => 'location,tags,description'],
            is_array($atts) ? $atts : [],
            'campsflow_event_meta'
        );
        $show   = array_map('trim', explode(',', $atts['show']));
        $postId = (int) get_the_ID();

        if (! $postId) {
            return '';
        }

        $location = json_decode((string) get_post_meta($postId, 'cf_location', true), true);
        $city     = is_array($location) ? ($location['city'] ?? '') : '';
        $dest     = is_array($location) ? ($location['destination'] ?? '') : '';
        $locName  = is_array($location) ? ($location['name'] ?? '') : '';

        ob_start();
        echo '<div class="cf-event-body">';

        if (in_array('location', $show, true) && ($city || $dest || $locName)) {
            $parts = array_filter([$dest, $locName, $city]);
            echo '<p class="cf-event-body__location">';
            echo '<span class="dashicons dashicons-location"></span>';
            echo esc_html(implode(' · ', $parts));
            echo '</p>';
        }

        if (in_array('tags', $show, true)) {
            $tags = get_the_terms($postId, 'cf_tag');
            $ages = get_the_terms($postId, 'cf_age_group');
            $hasTags = ($tags && ! is_wp_error($tags)) || ($ages && ! is_wp_error($ages));

            if ($hasTags) {
                echo '<div class="cf-event-body__tags">';
                if ($tags && ! is_wp_error($tags)) {
                    foreach ($tags as $tag) {
                        echo '<span class="cf-tag">' . esc_html($tag->name) . '</span>';
                    }
                }
                if ($ages && ! is_wp_error($ages)) {
                    foreach ($ages as $age) {
                        echo '<span class="cf-tag cf-tag--age">' . esc_html($age->name) . '</span>';
                    }
                }
                echo '</div>';
            }
        }

        if (in_array('description', $show, true)) {
            $content = get_post_field('post_content', $postId);
            if ($content) {
                echo '<div class="cf-event-body__description">';
                echo wp_kses_post(apply_filters('the_content', $content));
                echo '</div>';
            }
        }

        echo '</div>';
        return (string) ob_get_clean();
    }

    /**
     * @param array<string, string>|string $atts
     */
    public function renderSessions(array|string $atts): string
    {
        $atts = shortcode_atts(
            [
                'title'        => __('Dostępne terminy', 'campsflow'),
                'button_label' => __('Zapisz się', 'campsflow'),
            ],
            is_array($atts) ? $atts : [],
            'campsflow_event_sessions'
        );

        $postId      = (int) get_the_ID();
        $regPage     = get_option('campsflow_registration_page', '/rejestracja/');
        $buttonLabel = sanitize_text_field($atts['button_label']);
        $title       = sanitize_text_field($atts['title']);

        if (! $postId) {
            return '';
        }

        $sessions = new WP_Query([
            'post_type'      => SessionPostType::SLUG,
            'post_status'    => 'publish',
            'post_parent'    => $postId,
            'posts_per_page' => -1,
            'orderby'        => 'meta_value',
            'meta_key'       => 'cf_date_from',
            'order'          => 'ASC',
        ]);

        ob_start();
        echo '<div class="cf-sessions-box">';

        if ($title) {
            echo '<h2 class="cf-sessions-box__title">' . esc_html($title) . '</h2>';
        }

        if (! $sessions->have_posts()) {
            echo '<p class="cf-empty">' . esc_html__('Brak dostępnych terminów.', 'campsflow') . '</p>';
            echo '</div>';
            return (string) ob_get_clean();
        }

        echo '<ul class="cf-sessions-box__list">';

        while ($sessions->have_posts()) {
            $sessions->the_post();
            $sId         = (int) get_the_ID();
            $dateFrom    = (string) get_post_meta($sId, 'cf_date_from', true);
            $dateTo      = (string) get_post_meta($sId, 'cf_date_to', true);
            $price       = (int)   get_post_meta($sId, 'cf_price', true);
            $bucket      = AvailabilityBucket::tryFrom(
                (string) get_post_meta($sId, 'cf_availability', true)
            ) ?? AvailabilityBucket::Available;
            $sessionUuid = (string) get_post_meta($sId, 'cf_session_id', true);
            $isFull      = $bucket === AvailabilityBucket::Full;

            $f = $dateFrom ? date_create($dateFrom) : null;
            $t = $dateTo   ? date_create($dateTo)   : null;
            $dateLabel  = $f ? ($f->format('j M') . ($t ? '–' . $t->format('j M Y') : '')) : '';
            $priceLabel = $price ? number_format($price / 100, 0, ',', ' ') . ' zł' : '';
            $registerUrl = $isFull ? '#' : esc_url(add_query_arg('session', $sessionUuid, $regPage));

            echo '<li class="cf-sessions-box__item' . ($isFull ? ' cf-sessions-box__item--full' : '') . '">';
            echo '<div class="cf-sessions-box__dates">' . esc_html($dateLabel) . '</div>';
            echo '<div class="cf-sessions-box__meta">';
            if ($priceLabel) {
                echo '<span class="cf-sessions-box__price">' . esc_html($priceLabel) . '</span>';
            }
            if ($bucket !== AvailabilityBucket::Available && $bucket->label()) {
                echo '<span class="cf-badge cf-badge--' . esc_attr($bucket->value) . '">'
                    . esc_html($bucket->label()) . '</span>';
            }
            echo '</div>';

            if ($isFull) {
                echo '<span class="cf-btn cf-btn--disabled">' . esc_html__('Brak miejsc', 'campsflow') . '</span>';
            } else {
                echo '<a class="cf-btn" href="' . $registerUrl . '">' . esc_html($buttonLabel) . '</a>';
            }

            echo '</li>';
        }

        wp_reset_postdata();
        echo '</ul></div>';
        return (string) ob_get_clean();
    }
}
