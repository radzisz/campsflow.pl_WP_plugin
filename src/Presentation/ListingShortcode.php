<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\PostType\EventPostType;
use Campsflow\PostType\SessionPostType;
use Campsflow\Sync\AvailabilityBucket;
use WP_Query;

final class ListingShortcode
{
    public function register(): void
    {
        add_shortcode('campsflow_listing', [$this, 'render']);
    }

    /**
     * @param array<string, string>|string $atts
     */
    public function render(array|string $atts): string
    {
        $atts = shortcode_atts(
            ['view' => 'events', 'columns' => '3'],
            is_array($atts) ? $atts : [],
            'campsflow_listing'
        );

        $view    = in_array($atts['view'], ['events', 'sessions'], true) ? $atts['view'] : 'events';
        $columns = max(1, min(4, (int) $atts['columns']));

        ob_start();
        echo '<div class="cf-listing" style="--cf-columns:' . esc_attr((string) $columns) . '">';
        $this->renderFilters();

        if ($view === 'events') {
            $this->renderEventsView();
        } else {
            $this->renderSessionsView();
        }

        echo '</div>';
        return (string) ob_get_clean();
    }

    private function renderFilters(): void
    {
        $tags      = get_terms(['taxonomy' => 'cf_tag', 'hide_empty' => true]);
        $ageGroups = get_terms(['taxonomy' => 'cf_age_group', 'hide_empty' => true]);

        if (is_wp_error($tags) || is_wp_error($ageGroups)) {
            return;
        }

        $currentTag = sanitize_text_field($_GET['cf_tag'] ?? '');
        $currentAge = sanitize_text_field($_GET['cf_age'] ?? '');

        echo '<form class="cf-filters" method="get" action="">';

        if (!empty($tags)) {
            echo '<select class="cf-filter" name="cf_tag" onchange="this.form.submit()">';
            echo '<option value="">' . esc_html__('Wszystkie kategorie', 'campsflow') . '</option>';
            foreach ($tags as $tag) {
                assert(is_object($tag) && isset($tag->slug, $tag->name));
                $selected = selected($currentTag, $tag->slug, false);
                echo '<option value="' . esc_attr($tag->slug) . '"' . $selected . '>'
                    . esc_html($tag->name) . '</option>';
            }
            echo '</select>';
        }

        if (!empty($ageGroups)) {
            echo '<select class="cf-filter" name="cf_age" onchange="this.form.submit()">';
            echo '<option value="">' . esc_html__('Wszystkie grupy wiekowe', 'campsflow') . '</option>';
            foreach ($ageGroups as $group) {
                assert(is_object($group) && isset($group->slug, $group->name));
                $selected = selected($currentAge, $group->slug, false);
                echo '<option value="' . esc_attr($group->slug) . '"' . $selected . '>'
                    . esc_html($group->name) . '</option>';
            }
            echo '</select>';
        }

        echo '</form>';
    }

    private function renderEventsView(): void
    {
        $taxQuery = $this->buildTaxQuery();
        $query    = new WP_Query([
            'post_type'      => EventPostType::SLUG,
            'post_status'    => 'publish',
            'posts_per_page' => 24,
            'orderby'        => 'title',
            'order'          => 'ASC',
            'tax_query'      => $taxQuery,
        ]);

        if (!$query->have_posts()) {
            echo '<p class="cf-empty">' . esc_html__('Brak imprez spełniających kryteria.', 'campsflow') . '</p>';
            return;
        }

        echo '<div class="cf-grid">';
        while ($query->have_posts()) {
            $query->the_post();
            $this->renderEventCard((int) get_the_ID());
        }
        echo '</div>';
        wp_reset_postdata();
    }

    private function renderEventCard(int $eventId): void
    {
        $locRaw  = (string) get_post_meta($eventId, 'cf_localization', true);
        $loc     = $locRaw ? (json_decode($locRaw, true) ?? []) : [];
        $city    = is_array($loc['address'] ?? null) ? ($loc['address']['city'] ?? '') : '';
        $dest    = (string) ($loc['destination'] ?? '');
        $leadImg = (string) get_post_meta($eventId, 'cf_lead_image_url', true);

        $sessions = new WP_Query([
            'post_type'      => SessionPostType::SLUG,
            'post_status'    => 'publish',
            'post_parent'    => $eventId,
            'posts_per_page' => -1,
            'orderby'        => 'meta_value',
            'meta_key'       => 'cf_date_from',
            'order'          => 'ASC',
        ]);

        echo '<article class="cf-card">';

        if ($leadImg) {
            echo '<img class="cf-card__image" src="' . esc_url($leadImg) . '" alt="' . esc_attr(get_the_title()) . '" loading="lazy">';
        }

        echo '<div class="cf-card__body">';
        echo '<h3 class="cf-card__title">' . esc_html(get_the_title()) . '</h3>';

        if ($city || $dest) {
            echo '<p class="cf-card__location">';
            if ($dest) echo esc_html($dest);
            if ($dest && $city) echo ' · ';
            if ($city) echo esc_html($city);
            echo '</p>';
        }

        if ($sessions->have_posts()) {
            echo '<ul class="cf-sessions">';
            while ($sessions->have_posts()) {
                $sessions->the_post();
                $this->renderSessionRow((int) get_the_ID());
            }
            echo '</ul>';
            wp_reset_postdata();
        }

        echo '</div></article>';
    }

    private function renderSessionsView(): void
    {
        $taxQuery = $this->buildTaxQuery();
        $query    = new WP_Query([
            'post_type'      => SessionPostType::SLUG,
            'post_status'    => 'publish',
            'posts_per_page' => 50,
            'orderby'        => 'meta_value',
            'meta_key'       => 'cf_date_from',
            'order'          => 'ASC',
            'tax_query'      => $taxQuery,
        ]);

        if (!$query->have_posts()) {
            echo '<p class="cf-empty">' . esc_html__('Brak turnusów spełniających kryteria.', 'campsflow') . '</p>';
            return;
        }

        echo '<div class="cf-sessions-flat">';
        $currentEvent = 0;
        while ($query->have_posts()) {
            $query->the_post();
            $sessionId = (int) get_the_ID();
            $eventId   = (int) wp_get_post_parent_id($sessionId);

            if ($eventId !== $currentEvent) {
                if ($currentEvent !== 0) echo '</ul>';
                echo '<h3 class="cf-sessions-flat__event">' . esc_html(get_the_title($eventId)) . '</h3>';
                echo '<ul class="cf-sessions">';
                $currentEvent = $eventId;
            }

            $this->renderSessionRow($sessionId);
        }
        echo '</ul></div>';
        wp_reset_postdata();
    }

    private function renderSessionRow(int $sessionId): void
    {
        $dateFrom    = (string) get_post_meta($sessionId, 'cf_date_from', true);
        $dateTo      = (string) get_post_meta($sessionId, 'cf_date_to', true);
        $price       = (int) get_post_meta($sessionId, 'cf_price_from', true);
        $turnusName  = (string) get_post_meta($sessionId, 'cf_turnus_name', true);
        $bucket      = AvailabilityBucket::tryFrom(
            (string) get_post_meta($sessionId, 'cf_availability', true)
        ) ?? AvailabilityBucket::Available;

        $reservUrl = (string) get_post_meta($sessionId, 'cf_reservation_url', true);
        $isFull    = $bucket === AvailabilityBucket::Full;

        echo '<li class="cf-session">';
        if ($turnusName) {
            echo '<span class="cf-session__name">' . esc_html($turnusName) . '</span>';
        }
        echo '<span class="cf-session__dates">' . esc_html($this->formatDateRange($dateFrom, $dateTo)) . '</span>';
        echo '<span class="cf-session__price">' . esc_html($this->formatPrice($price)) . '</span>';

        if ($bucket !== AvailabilityBucket::Available && $bucket->label()) {
            echo '<span class="cf-badge cf-badge--' . esc_attr($bucket->value) . '">'
                . esc_html($bucket->label()) . '</span>';
        }

        if ($isFull) {
            echo '<span class="cf-btn cf-btn--disabled">' . esc_html__('Brak miejsc', 'campsflow') . '</span>';
        } elseif ($reservUrl) {
            echo '<a class="cf-btn" href="' . esc_url($reservUrl) . '" target="_blank" rel="noopener">' . esc_html__('Zapisz się', 'campsflow') . '</a>';
        }

        echo '</li>';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function buildTaxQuery(): array
    {
        $query = [];

        $tag = sanitize_text_field($_GET['cf_tag'] ?? '');
        if ($tag) {
            $query[] = ['taxonomy' => 'cf_tag', 'field' => 'slug', 'terms' => $tag];
        }

        $age = sanitize_text_field($_GET['cf_age'] ?? '');
        if ($age) {
            $query[] = ['taxonomy' => 'cf_age_group', 'field' => 'slug', 'terms' => $age];
        }

        return $query;
    }

    private function formatDateRange(string $from, string $to): string
    {
        if (!$from) return '';
        $f = date_create($from);
        $t = $to ? date_create($to) : null;
        if (!$f) return $from;

        $fmt = 'j M Y';
        return $t
            ? $f->format('j M') . '–' . $t->format('j M Y')
            : $f->format($fmt);
    }

    private function formatPrice(int $grosze): string
    {
        if ($grosze <= 0) return '';
        return number_format($grosze / 100, 0, ',', ' ') . ' zł';
    }
}
