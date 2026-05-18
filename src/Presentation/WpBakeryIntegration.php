<?php
declare(strict_types=1);

namespace Campsflow\Presentation;

/**
 * WPBakery Page Builder integration.
 * Maps our shortcodes to drag-and-drop elements in the WPBakery editor.
 * Only activates when WPBakery is loaded.
 */
final class WpBakeryIntegration
{
    public function register(): void
    {
        add_action('vc_before_init', [$this, 'mapElements']);
    }

    public function mapElements(): void
    {
        $this->mapListing();
        $this->mapEventMeta();
        $this->mapEventSessions();
    }

    private function mapListing(): void
    {
        vc_map([
            'name'        => __('CampsFlow — Lista obozów', 'campsflow'),
            'base'        => 'campsflow_listing',
            'category'    => 'CampsFlow',
            'icon'        => 'dashicons-flag',
            'description' => __('Wyświetla listę imprez lub turnusów z filtrami', 'campsflow'),
            'params'      => [
                [
                    'type'        => 'dropdown',
                    'heading'     => __('Widok', 'campsflow'),
                    'param_name'  => 'view',
                    'value'       => [
                        __('Lista imprez', 'campsflow')          => 'events',
                        __('Lista turnusów (płaska)', 'campsflow') => 'sessions',
                    ],
                    'description' => __('Imprezowy — z wyborem turnusu. Płaski — bezpośrednie zapisy.', 'campsflow'),
                ],
                [
                    'type'        => 'dropdown',
                    'heading'     => __('Kolumny', 'campsflow'),
                    'param_name'  => 'columns',
                    'value'       => ['1' => '1', '2' => '2', '3' => '3', '4' => '4'],
                    'std'         => '3',
                ],
            ],
        ]);
    }

    private function mapEventMeta(): void
    {
        vc_map([
            'name'        => __('CampsFlow — Szczegóły imprezy', 'campsflow'),
            'base'        => 'campsflow_event_meta',
            'category'    => 'CampsFlow',
            'icon'        => 'dashicons-info-outline',
            'description' => __('Lokalizacja, tagi i opis aktualnej imprezy', 'campsflow'),
            'params'      => [
                [
                    'type'       => 'checkbox',
                    'heading'    => __('Widoczność', 'campsflow'),
                    'param_name' => 'show',
                    'value'      => [
                        __('Lokalizacja', 'campsflow') => 'location',
                        __('Tagi', 'campsflow')        => 'tags',
                        __('Opis', 'campsflow')        => 'description',
                    ],
                    'std'        => 'location,tags,description',
                ],
            ],
        ]);
    }

    private function mapEventSessions(): void
    {
        vc_map([
            'name'        => __('CampsFlow — Turnusy', 'campsflow'),
            'base'        => 'campsflow_event_sessions',
            'category'    => 'CampsFlow',
            'icon'        => 'dashicons-calendar-alt',
            'description' => __('Lista terminów z cenami i przyciskami zapisu', 'campsflow'),
            'params'      => [
                [
                    'type'       => 'textfield',
                    'heading'    => __('Nagłówek sekcji', 'campsflow'),
                    'param_name' => 'title',
                    'value'      => __('Dostępne terminy', 'campsflow'),
                ],
                [
                    'type'       => 'textfield',
                    'heading'    => __('Tekst przycisku', 'campsflow'),
                    'param_name' => 'button_label',
                    'value'      => __('Zapisz się', 'campsflow'),
                ],
            ],
        ]);
    }
}
