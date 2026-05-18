<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

use Elementor\Controls_Manager;
use Elementor\Group_Control_Typography;
use Elementor\Widget_Base;

/**
 * Elementor widget: event meta (location, tags, age groups, description).
 * Works on any page where a cf_event post is the context
 * (single-cf_event.php or Elementor Pro Theme Builder template).
 */
final class EventMetaWidget extends Widget_Base
{
    public function get_name(): string
    {
        return 'campsflow_event_meta';
    }

    public function get_title(): string
    {
        return __('CampsFlow — Szczegóły imprezy', 'campsflow');
    }

    public function get_icon(): string
    {
        return 'eicon-info-circle';
    }

    public function get_categories(): array
    {
        return ['campsflow'];
    }

    protected function register_controls(): void
    {
        $this->start_controls_section('section_content', [
            'label' => __('Widoczność sekcji', 'campsflow'),
            'tab'   => Controls_Manager::TAB_CONTENT,
        ]);

        $this->add_control('show_location', [
            'label'        => __('Lokalizacja', 'campsflow'),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'label_on'     => __('Tak', 'campsflow'),
            'label_off'    => __('Nie', 'campsflow'),
        ]);

        $this->add_control('show_tags', [
            'label'        => __('Tagi', 'campsflow'),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'label_on'     => __('Tak', 'campsflow'),
            'label_off'    => __('Nie', 'campsflow'),
        ]);

        $this->add_control('show_description', [
            'label'        => __('Opis', 'campsflow'),
            'type'         => Controls_Manager::SWITCHER,
            'default'      => 'yes',
            'label_on'     => __('Tak', 'campsflow'),
            'label_off'    => __('Nie', 'campsflow'),
        ]);

        $this->end_controls_section();

        $this->start_controls_section('section_style', [
            'label' => __('Styl', 'campsflow'),
            'tab'   => Controls_Manager::TAB_STYLE,
        ]);

        $this->add_control('accent_color', [
            'label'     => __('Kolor akcentu (tagi)', 'campsflow'),
            'type'      => Controls_Manager::COLOR,
            'default'   => '#2563eb',
            'selectors' => ['{{WRAPPER}} .cf-tag' => 'background: {{VALUE}}20; color: {{VALUE}}'],
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'location_typography',
            'label'    => __('Lokalizacja', 'campsflow'),
            'selector' => '{{WRAPPER}} .cf-event-body__location',
        ]);

        $this->add_group_control(Group_Control_Typography::get_type(), [
            'name'     => 'description_typography',
            'label'    => __('Opis', 'campsflow'),
            'selector' => '{{WRAPPER}} .cf-event-body__description',
        ]);

        $this->end_controls_section();
    }

    protected function render(): void
    {
        $settings = $this->get_settings_for_display();
        $postId   = (int) get_the_ID();

        if (! $postId) {
            if (\Elementor\Plugin::$instance->editor->is_edit_mode()) {
                echo '<p style="color:#999">' . esc_html__('[Podgląd — otwórz na stronie imprezy]', 'campsflow') . '</p>';
            }
            return;
        }

        $location = json_decode((string) get_post_meta($postId, 'cf_location', true), true);
        $city     = is_array($location) ? ($location['city'] ?? '') : '';
        $dest     = is_array($location) ? ($location['destination'] ?? '') : '';
        $locName  = is_array($location) ? ($location['name'] ?? '') : '';

        echo '<div class="cf-event-body">';

        if ($settings['show_location'] === 'yes' && ($city || $dest || $locName)) {
            $parts = array_filter([$dest, $locName, $city]);
            echo '<p class="cf-event-body__location">';
            echo '<span class="dashicons dashicons-location"></span>';
            echo esc_html(implode(' · ', $parts));
            echo '</p>';
        }

        if ($settings['show_tags'] === 'yes') {
            $tags = get_the_terms($postId, 'cf_tag');
            $ages = get_the_terms($postId, 'cf_age_group');

            if (($tags && ! is_wp_error($tags)) || ($ages && ! is_wp_error($ages))) {
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

        if ($settings['show_description'] === 'yes') {
            $content = get_post_field('post_content', $postId);
            if ($content) {
                echo '<div class="cf-event-body__description">';
                echo wp_kses_post(apply_filters('the_content', $content));
                echo '</div>';
            }
        }

        echo '</div>';
    }
}
