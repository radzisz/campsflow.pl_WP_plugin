<?php
declare(strict_types=1);

namespace Campsflow\Admin;

use Campsflow\PostType\EventPostType;
use Campsflow\Sync\SyncScheduler;

final class SettingsPage
{
    public function register(): void
    {
        add_action('admin_menu', [$this, 'addMenuPage']);
        add_action('admin_init', [$this, 'registerSettings']);
        add_action('update_option_campsflow_sync_interval', [$this, 'onIntervalChange'], 10, 0);
    }

    public function addMenuPage(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . EventPostType::SLUG,
            __('Campsflow — Ustawienia', 'campsflow'),
            __('Ustawienia', 'campsflow'),
            'manage_options',
            'cf-settings',
            [$this, 'renderPage'],
        );
    }

    public function registerSettings(): void
    {
        $options = [
            'campsflow_tenant_slug'    => '',
            'campsflow_api_url'        => 'https://campsflow.pl',
            'campsflow_admin_url'      => 'https://admin.campsflow.pl',
            'campsflow_sync_interval'  => 'hourly',
            'campsflow_few_left_pct'   => '30',
            'campsflow_almost_full_pct'=> '10',
            'campsflow_reg_page'          => '/rejestracja/',
            'campsflow_listing_page_slug' => 'obozy',
        ];

        foreach ($options as $key => $default) {
            register_setting('campsflow_settings', $key, [
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => $default,
            ]);
        }
    }

    public function onIntervalChange(): void
    {
        SyncScheduler::reschedule();
    }

    public function renderPage(): void
    {
        if (! current_user_can('manage_options')) {
            return;
        }

        $nextRun = wp_next_scheduled('campsflow_sync');
        $lastRun = get_option('campsflow_last_sync', null);

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('Campsflow — Ustawienia', 'campsflow') . '</h1>';

        // Sync status bar
        echo '<div class="cf-sync-status">';
        if ($nextRun) {
            $diff = $nextRun - time();
            echo '<span class="cf-sync-status__ok dashicons dashicons-yes-alt"></span> ';
            echo esc_html__('Synchronizacja aktywna.', 'campsflow') . ' ';
            echo esc_html__('Następna za:', 'campsflow') . ' <strong>' . esc_html($this->humanDiff($diff)) . '</strong>';
        } else {
            echo '<span class="dashicons dashicons-warning" style="color:#f59e0b"></span> ';
            echo esc_html__('Synchronizacja nie jest zaplanowana.', 'campsflow');
        }

        if ($lastRun) {
            echo ' &nbsp;·&nbsp; ' . esc_html__('Ostatnia synchronizacja:', 'campsflow') . ' <strong>' . esc_html($lastRun) . '</strong>';
        }

        $synced = isset($_GET['cf_synced']) ? (int) $_GET['cf_synced'] : null;
        if ($synced !== null) {
            echo ' &nbsp;·&nbsp; <strong style="color:#16a34a">✓ ' . sprintf(
                esc_html__('Zsynchronizowano %d turnusów', 'campsflow'),
                $synced
            ) . '</strong>';
        }

        echo '</div>';

        echo '<form method="post" action="options.php">';
        settings_fields('campsflow_settings');

        echo '<table class="form-table" role="presentation"><tbody>';

        $this->row(
            __('Tenant slug', 'campsflow'),
            'campsflow_tenant_slug',
            'text',
            __('Slug tenanta z Campsflow (np. <code>moj-oboz</code>)', 'campsflow'),
        );

        $this->row(
            __('API URL', 'campsflow'),
            'campsflow_api_url',
            'url',
            __('Bazowy URL Campsflow API (domyślnie: https://campsflow.pl)', 'campsflow'),
        );

        $this->row(
            __('Link do panelu Campsflow', 'campsflow'),
            'campsflow_admin_url',
            'url',
            __('URL panelu administracyjnego — pojawi się w banerze na listach CPT', 'campsflow'),
        );

        $this->rowSelect(
            __('Częstotliwość synchronizacji', 'campsflow'),
            'campsflow_sync_interval',
            SyncScheduler::INTERVALS,
            __('Jak często plugin pobiera dane z Campsflow API', 'campsflow'),
        );

        $this->row(
            __('"Mało miejsc" poniżej (%)', 'campsflow'),
            'campsflow_few_left_pct',
            'number',
            __('Próg procentu wolnych miejsc dla etykiety "Mało miejsc"', 'campsflow'),
        );

        $this->row(
            __('"Na wyczerpaniu" poniżej (%)', 'campsflow'),
            'campsflow_almost_full_pct',
            'number',
            __('Próg procentu wolnych miejsc dla etykiety "Na wyczerpaniu"', 'campsflow'),
        );

        $this->row(
            __('Strona rejestracji', 'campsflow'),
            'campsflow_reg_page',
            'text',
            __('Ścieżka lub URL strony z shortcodem [campsflow_registration_form]', 'campsflow'),
        );

        $this->row(
            __('Slug strony z listą obozów', 'campsflow'),
            'campsflow_listing_page_slug',
            'text',
            __('Slug strony WP z listą obozów (np. <code>obozy</code>) — używany przez link "Edytuj listę" w menu', 'campsflow'),
        );

        echo '</tbody></table>';
        submit_button(__('Zapisz ustawienia', 'campsflow'));
        echo '</form>';

        echo '<hr>';
        echo '<h2>' . esc_html__('Synchronizacja', 'campsflow') . '</h2>';
        echo '<p>' . esc_html__('Uruchom synchronizację ręcznie — pobiera dane z Campsflow API i aktualizuje imprezy oraz turnusy.', 'campsflow') . '</p>';
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('cf_sync_now');
        echo '<input type="hidden" name="action" value="cf_sync_now">';
        submit_button(__('Synchronizuj teraz', 'campsflow'), 'secondary');
        echo '</form>';

        echo '<style>
        .cf-sync-status {
            background: #f0f6fc; border-left: 4px solid #2563eb;
            padding: 10px 14px; margin: 10px 0 20px;
            border-radius: 0 4px 4px 0; font-size: 13px;
        }
        .cf-sync-status__ok { color: #16a34a; }
        </style>';

        echo '</div>';
    }

    private function row(string $label, string $option, string $type, string $desc): void
    {
        $value = esc_attr((string) get_option($option, ''));
        $extra = $type === 'number' ? 'min="0" max="100" style="width:80px"' : 'style="width:400px"';

        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr($option) . '">' . esc_html($label) . '</label></th>';
        echo '<td>';
        echo '<input type="' . esc_attr($type) . '" id="' . esc_attr($option) . '" name="' . esc_attr($option) . '" value="' . $value . '" ' . $extra . '>';
        echo '<p class="description">' . wp_kses($desc, ['code' => []]) . '</p>';
        echo '</td></tr>';
    }

    /**
     * @param array<string, string> $options
     */
    private function rowSelect(string $label, string $option, array $options, string $desc): void
    {
        $current = (string) get_option($option, 'hourly');

        echo '<tr>';
        echo '<th scope="row"><label for="' . esc_attr($option) . '">' . esc_html($label) . '</label></th>';
        echo '<td>';
        echo '<select id="' . esc_attr($option) . '" name="' . esc_attr($option) . '">';
        foreach ($options as $value => $optLabel) {
            $selected = selected($current, $value, false);
            echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($optLabel) . '</option>';
        }
        echo '</select>';
        echo '<p class="description">' . esc_html($desc) . '</p>';
        echo '</td></tr>';
    }

    private function humanDiff(int $seconds): string
    {
        if ($seconds < 60)   return $seconds . ' s';
        if ($seconds < 3600) return (int) ($seconds / 60) . ' min';
        return (int) ($seconds / 3600) . ' h ' . (int) (($seconds % 3600) / 60) . ' min';
    }
}
