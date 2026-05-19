<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Campsflow\PostType\SessionPostType;
use Campsflow\Sync\AvailabilityBucket;
use Elementor\Controls_Manager;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;
use WP_Query;

/**
 * Elementor widget: sessions list with "Zapisz się" buttons and optional meeting points.
 */
final class EventSessionsWidget extends Widget_Base
{
    public function get_name(): string
    {
        return 'campsflow_event_sessions';
    }

    public function get_title(): string
    {
        return __('CampsFlow — Turnusy', 'campsflow');
    }

    public function get_icon(): string
    {
        return 'eicon-calendar';
    }

    public function get_categories(): array
    {
        return ['campsflow'];
    }

    protected function register_controls(): void
    {
        $this->registerContentSection();
        $this->registerStyleSection();
    }

    private function registerContentSection(): void
    {
        $this->start_controls_section('section_content', [
            'label' => __('Zawartość', 'campsflow'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);
        $this->add_control('title', [
            'label'   => __('Nagłówek sekcji', 'campsflow'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Dostępne terminy', 'campsflow'),
        ]);
        $this->add_control('button_label', [
            'label'   => __('Tekst przycisku', 'campsflow'),
            'type'    => Controls_Manager::TEXT,
            'default' => __('Zapisz się', 'campsflow'),
        ]);
        $this->add_control('show_meeting_points', [
            'label'    => __('Punkty zbiórki', 'campsflow'),
            'type'     => Controls_Manager::SWITCHER,
            'default'  => '',
            'label_on' => __('Tak', 'campsflow'),
            'label_off'=> __('Nie', 'campsflow'),
        ]);
        $this->end_controls_section();
    }

    private function registerStyleSection(): void
    {
        $this->start_controls_section('section_style_box', [
            'label' => __('Box', 'campsflow'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);
        $this->add_control('accent_color', [
            'label'     => __('Kolor akcentu', 'campsflow'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#2563eb',
            'selectors' => [
                '{{WRAPPER}} .cf-sessions-box__title' => 'border-color: {{VALUE}}',
                '{{WRAPPER}} .cf-btn'                 => 'background: {{VALUE}}',
            ],
        ]);
        $this->add_control('box_bg', [
            'label'     => __('Tło boksu', 'campsflow'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#ffffff',
            'selectors' => ['{{WRAPPER}} .cf-sessions-box' => 'background: {{VALUE}}'],
        ]);
        $this->add_control('box_radius', [
            'label'     => __('Zaokrąglenie', 'campsflow'),
            'type'      => Controls_Manager::SLIDER,
            'range'     => ['px' => ['min' => 0, 'max' => 32]],
            'default'   => ['size' => 10, 'unit' => 'px'],
            'selectors' => ['{{WRAPPER}} .cf-sessions-box' => 'border-radius: {{SIZE}}{{UNIT}}'],
        ]);
        $this->add_group_control(Group_Control_Box_Shadow::get_type(), [
            'name'     => 'box_shadow',
            'selector' => '{{WRAPPER}} .cf-sessions-box',
        ]);
        $this->end_controls_section();

        $this->start_controls_section('section_style_typography', [
            'label' => __('Typografia', 'campsflow'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'title_typography',
            'label'    => __('Nagłówek', 'campsflow'),
            'selector' => '{{WRAPPER}} .cf-sessions-box__title',
        ]);
        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'dates_typography',
            'label'    => __('Daty turnusu', 'campsflow'),
            'selector' => '{{WRAPPER}} .cf-sessions-box__dates',
        ]);
        $this->end_controls_section();
    }

    protected function render(): void
    {
        $s                 = $this->get_settings_for_display();
        $postId            = (int) get_the_ID();
        $buttonLabel       = sanitize_text_field($s['button_label'] ?? __('Zapisz się', 'campsflow'));
        $title             = sanitize_text_field($s['title'] ?? __('Dostępne terminy', 'campsflow'));
        $showMeetingPoints = ($s['show_meeting_points'] ?? '') === 'yes';

        if (! $postId) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p style="color:#999">' . esc_html__('[Podgląd — otwórz na stronie wydarzenia]', 'campsflow') . '</p>';
            }
            return;
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

        echo '<div class="cf-sessions-box">';
        if ($title) echo '<h2 class="cf-sessions-box__title">' . esc_html($title) . '</h2>';
        if (! $sessions->have_posts()) {
            echo '<p class="cf-empty">' . esc_html__('Brak dostępnych terminów.', 'campsflow') . '</p>';
            echo '</div>';
            return;
        }
        echo '<ul class="cf-sessions-box__list">';
        while ($sessions->have_posts()) {
            $sessions->the_post();
            $this->echoSessionItem((int) get_the_ID(), $buttonLabel, $showMeetingPoints);
        }
        wp_reset_postdata();
        echo '</ul></div>';
    }

    // ── Session helpers ───────────────────────────────────────────────────────

    private function echoSessionItem(int $sId, string $buttonLabel, bool $showMeetingPoints): void
    {
        $dateFrom  = (string) get_post_meta($sId, 'cf_date_from', true);
        $dateTo    = (string) get_post_meta($sId, 'cf_date_to', true);
        $price     = (int)    get_post_meta($sId, 'cf_price_from', true);
        $days      = (int)    get_post_meta($sId, 'cf_number_of_days', true);
        $name      = (string) get_post_meta($sId, 'cf_turnus_name', true);
        $reservUrl = (string) get_post_meta($sId, 'cf_reservation_url', true);
        $transport = json_decode((string) get_post_meta($sId, 'cf_transport', true), true);
        $bucket    = AvailabilityBucket::tryFrom((string) get_post_meta($sId, 'cf_availability', true))
                        ?? AvailabilityBucket::Available;
        $isFull    = $bucket === AvailabilityBucket::Full;
        $f          = $dateFrom ? date_create($dateFrom) : null;
        $t          = $dateTo   ? date_create($dateTo)   : null;
        $dateLabel  = $f ? ($f->format('j M') . ($t ? '–' . $t->format('j M Y') : '')) : '';
        $priceLabel = $price ? 'od ' . number_format($price / 100, 0, ',', ' ') . ' zł' : '';
        $tDesc      = is_array($transport) ? (string) ($transport['description'] ?? '') : '';
        $tType      = is_array($transport) ? (string) ($transport['type'] ?? 'own') : 'own';

        echo '<li class="cf-sessions-box__item' . ($isFull ? ' cf-sessions-box__item--full' : '') . '">';
        if ($name) echo '<div class="cf-sessions-box__name">' . esc_html($name) . '</div>';
        echo '<div class="cf-sessions-box__dates">' . esc_html($dateLabel);
        if ($days > 0) {
            echo ' <span class="cf-sessions-box__days">(' . esc_html((string) $days) . ' ' . esc_html__('dni', 'campsflow') . ')</span>';
        }
        echo '</div>';
        if ($tType !== 'own' && $tDesc) {
            echo '<div class="cf-sessions-box__transport"><span class="dashicons dashicons-car"></span> ' . esc_html($tDesc) . '</div>';
        }
        if ($showMeetingPoints) $this->echoMeetingPoints($sId);
        echo '<div class="cf-sessions-box__meta">';
        if ($priceLabel) echo '<span class="cf-sessions-box__price">' . esc_html($priceLabel) . '</span>';
        if ($bucket !== AvailabilityBucket::Available && $bucket->label()) {
            echo '<span class="cf-badge cf-badge--' . esc_attr($bucket->value) . '">' . esc_html($bucket->label()) . '</span>';
        }
        echo '</div>';
        if ($isFull) {
            echo '<span class="cf-btn cf-btn--disabled">' . esc_html__('Brak miejsc', 'campsflow') . '</span>';
        } elseif ($reservUrl) {
            echo '<a class="cf-btn" href="' . esc_url($reservUrl) . '" target="_blank" rel="noopener">' . esc_html($buttonLabel) . '</a>';
        }
        echo '</li>';
    }

    private function echoMeetingPoints(int $sId): void
    {
        $start = json_decode((string) get_post_meta($sId, 'cf_meeting_points_start', true), true);
        if (! is_array($start) || empty($start)) return;
        echo '<div class="cf-meeting-points">';
        echo '<strong class="cf-meeting-points__label">' . esc_html__('Zbiórka', 'campsflow') . ':</strong><ul>';
        foreach (array_slice($start, 0, 5) as $mp) {
            if (! is_array($mp)) continue;
            $mpName    = (string) ($mp['name'] ?? '');
            $mpAddress = (string) ($mp['address'] ?? '');
            $mpDate    = (string) ($mp['date'] ?? '');
            $mpHour    = (string) ($mp['hour'] ?? '');
            $mpGps     = is_array($mp['gps'] ?? null) ? $mp['gps'] : null;
            $addrLabel = implode(', ', array_filter([$mpName, $mpAddress]));
            $timeLabel = implode(' ', array_filter([$mpDate, $mpHour]));
            echo '<li class="cf-mp__item">';
            if ($timeLabel) echo '<span class="cf-mp__time">' . esc_html($timeLabel) . '</span> ';
            echo '<span class="cf-mp__place">' . esc_html($addrLabel) . '</span>';
            if ($mpGps && isset($mpGps['lat'], $mpGps['lng'])) {
                $url = 'https://www.google.com/maps?q=' . $mpGps['lat'] . ',' . $mpGps['lng'];
                echo ' <a href="' . esc_url($url) . '" target="_blank" rel="noopener">' . esc_html__('Mapa', 'campsflow') . '</a>';
            }
            echo '</li>';
        }
        echo '</ul></div>';
    }
}
