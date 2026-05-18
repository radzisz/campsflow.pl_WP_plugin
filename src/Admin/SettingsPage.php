<?php
declare(strict_types=1);

namespace Campsflow\Admin;

use Campsflow\PostType\EventPostType;
use Campsflow\Sync\SyncScheduler;

final class SettingsPage
{
    private const TAB_SYNC     = 'sync';
    private const TAB_SETTINGS = 'settings';

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
            __('CampsFlow — Ustawienia', 'campsflow'),
            __('Ustawienia', 'campsflow'),
            'manage_options',
            'cf-settings',
            [$this, 'renderPage'],
        );
    }

    public function registerSettings(): void
    {
        $defaults = [
            'campsflow_tenant_slug'     => '',
            'campsflow_api_key'         => '',
            'campsflow_sync_interval'   => 'hourly',
            'campsflow_few_left_pct'    => '30',
            'campsflow_almost_full_pct' => '10',
        ];

        foreach ($defaults as $key => $default) {
            register_setting('campsflow_sync', $key, [
                'sanitize_callback' => 'sanitize_text_field',
                'default'           => $default,
            ]);
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

        $activeTab = in_array($_GET['tab'] ?? '', [self::TAB_SYNC, self::TAB_SETTINGS], true)
            ? $_GET['tab']
            : self::TAB_SYNC;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('CampsFlow', 'campsflow') . '</h1>';

        $this->renderTabs($activeTab);
        $this->renderStatusBar();

        if ($activeTab === self::TAB_SYNC) {
            $this->renderSyncTab();
        } else {
            $this->renderSettingsTab();
        }

        echo '</div>';
        $this->renderStyles();
    }

    private function renderTabs(string $activeTab): void
    {
        $base = admin_url('edit.php?post_type=' . EventPostType::SLUG . '&page=cf-settings');
        $tabs = [
            self::TAB_SYNC     => __('Synchronizacja', 'campsflow'),
            self::TAB_SETTINGS => __('Ustawienia', 'campsflow'),
        ];

        echo '<nav class="nav-tab-wrapper cf-tabs">';
        foreach ($tabs as $slug => $label) {
            $active = $activeTab === $slug ? ' nav-tab-active' : '';
            $url    = esc_url(add_query_arg('tab', $slug, $base));
            echo '<a href="' . $url . '" class="nav-tab' . $active . '">' . esc_html($label) . '</a>';
        }
        echo '</nav>';
    }

    private function renderStatusBar(): void
    {
        $nextRun = wp_next_scheduled(SyncScheduler::HOOK);
        $lastRun = get_option('campsflow_last_sync', null);
        $synced  = isset($_GET['cf_synced']) ? (int) $_GET['cf_synced'] : null;

        echo '<div class="cf-status-bar">';

        if ($nextRun) {
            $diff = max(0, $nextRun - time());
            echo '<span class="cf-status-bar__dot cf-status-bar__dot--ok"></span>';
            echo esc_html__('Synchronizacja aktywna', 'campsflow') . ' · ';
            echo esc_html__('Następna za:', 'campsflow') . ' <strong>' . esc_html($this->humanDiff($diff)) . '</strong>';
        } else {
            echo '<span class="cf-status-bar__dot cf-status-bar__dot--warn"></span>';
            echo esc_html__('Brak zaplanowanej synchronizacji', 'campsflow');
        }

        if ($lastRun) {
            echo ' &nbsp;·&nbsp; ' . esc_html__('Ostatnia:', 'campsflow') . ' <strong>' . esc_html($lastRun) . '</strong>';
        }

        if ($synced !== null) {
            echo ' &nbsp;·&nbsp; <strong class="cf-status-bar__ok">✓ ';
            echo esc_html(sprintf(__('Zsynchronizowano %d turnusów', 'campsflow'), $synced));
            echo '</strong>';
        }

        echo '</div>';
    }

    private function renderSyncTab(): void
    {
        $tenantSlug = (string) get_option('campsflow_tenant_slug', '');
        $apiKey     = (string) get_option('campsflow_api_key', '');
        $interval   = (string) get_option('campsflow_sync_interval', 'hourly');

        echo '<form method="post" action="options.php" class="cf-form">';
        settings_fields('campsflow_sync');

        echo '<div class="cf-form__group">';
        echo '<label class="cf-form__label" for="campsflow_tenant_slug">' . esc_html__('Tenant slug', 'campsflow') . '</label>';
        echo '<input class="cf-form__input" type="text" id="campsflow_tenant_slug" name="campsflow_tenant_slug" value="' . esc_attr($tenantSlug) . '" placeholder="np. moj-oboz">';
        echo '<p class="cf-form__desc">' . esc_html__('Identyfikator organizatora w systemie Campsflow.', 'campsflow') . '</p>';
        echo '</div>';

        echo '<div class="cf-form__group">';
        echo '<label class="cf-form__label" for="campsflow_api_key">' . esc_html__('API Key', 'campsflow') . '</label>';
        echo '<input class="cf-form__input cf-form__input--key" type="password" id="campsflow_api_key" name="campsflow_api_key" value="' . esc_attr($apiKey) . '" autocomplete="new-password">';
        if ($apiKey) {
            echo '<p class="cf-form__desc cf-form__desc--set">✓ ' . esc_html__('Klucz jest ustawiony. Wpisz nowy żeby zmienić.', 'campsflow') . '</p>';
        } else {
            echo '<p class="cf-form__desc">' . esc_html__('Klucz API z panelu Campsflow (Ustawienia → Integracje).', 'campsflow') . '</p>';
        }
        echo '</div>';

        echo '<div class="cf-form__group">';
        echo '<label class="cf-form__label" for="campsflow_sync_interval">' . esc_html__('Częstotliwość synchronizacji', 'campsflow') . '</label>';
        echo '<div class="cf-form__inline">';
        echo '<select class="cf-form__select" id="campsflow_sync_interval" name="campsflow_sync_interval">';
        foreach (SyncScheduler::INTERVALS as $value => $label) {
            $selected = selected($interval, $value, false);
            echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';

        // Sync-now button inline — separate form so it doesn't interfere with settings save
        echo '</div></div>';
        submit_button(__('Zapisz', 'campsflow'), 'primary cf-form__submit');
        echo '</form>';

        // Rendered visually next to the select via CSS absolute/flex trick
        echo '<form id="cf-sync-now-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        wp_nonce_field('cf_sync_now');
        echo '<input type="hidden" name="action" value="cf_sync_now">';
        echo '</form>';
        echo '<script>
        document.addEventListener("DOMContentLoaded", function() {
            var select = document.getElementById("campsflow_sync_interval");
            if (!select) return;
            var btn = document.createElement("button");
            btn.type = "submit";
            btn.form = "cf-sync-now-form";
            btn.className = "button button-secondary";
            btn.innerHTML = \'<span class="dashicons dashicons-update" style="margin-top:3px;font-size:16px"></span> \' + ' . wp_json_encode(__('Synchronizuj teraz', 'campsflow')) . ';
            btn.style.cssText = "display:inline-flex;align-items:center;gap:4px;margin-left:8px;vertical-align:middle";
            select.parentNode.insertBefore(btn, select.nextSibling);
        });
        </script>';
    }

    private function renderSettingsTab(): void
    {
        $fewLeft    = (string) get_option('campsflow_few_left_pct', '30');
        $almostFull = (string) get_option('campsflow_almost_full_pct', '10');

        echo '<form method="post" action="options.php" class="cf-form">';
        settings_fields('campsflow_settings');

        echo '<h3>' . esc_html__('Poziomy dostępności miejsc', 'campsflow') . '</h3>';
        echo '<p class="cf-form__desc">' . esc_html__('Określ przy jakim procencie wolnych miejsc pojawia się etykieta ostrzegawcza.', 'campsflow') . '</p>';

        echo '<div class="cf-form__group">';
        echo '<label class="cf-form__label" for="campsflow_few_left_pct">';
        echo '<span class="cf-badge cf-badge--few_left">' . esc_html__('Mało miejsc', 'campsflow') . '</span>';
        echo ' &nbsp;' . esc_html__('poniżej', 'campsflow') . '</label>';
        echo '<div class="cf-form__inline">';
        echo '<input class="cf-form__input cf-form__input--pct" type="number" id="campsflow_few_left_pct" name="campsflow_few_left_pct" value="' . esc_attr($fewLeft) . '" min="1" max="99"> %';
        echo '</div>';
        echo '<p class="cf-form__desc">' . esc_html__('Domyślnie: 30% — pokazuj gdy zostało mniej niż 30% miejsc.', 'campsflow') . '</p>';
        echo '</div>';

        echo '<div class="cf-form__group">';
        echo '<label class="cf-form__label" for="campsflow_almost_full_pct">';
        echo '<span class="cf-badge cf-badge--almost_full">' . esc_html__('Na wyczerpaniu', 'campsflow') . '</span>';
        echo ' &nbsp;' . esc_html__('poniżej', 'campsflow') . '</label>';
        echo '<div class="cf-form__inline">';
        echo '<input class="cf-form__input cf-form__input--pct" type="number" id="campsflow_almost_full_pct" name="campsflow_almost_full_pct" value="' . esc_attr($almostFull) . '" min="1" max="99"> %';
        echo '</div>';
        echo '<p class="cf-form__desc">' . esc_html__('Domyślnie: 10% — pokazuj gdy zostało mniej niż 10% miejsc.', 'campsflow') . '</p>';
        echo '</div>';

        submit_button(__('Zapisz', 'campsflow'), 'primary cf-form__submit');
        echo '</form>';
    }

    private function renderStyles(): void
    {
        echo '<style>
        .cf-tabs { margin-bottom: 0; }
        .cf-status-bar {
            background: #f0f6fc; border-left: 4px solid #2563eb;
            padding: 10px 16px; margin: 0 0 24px; font-size: 13px;
            display: flex; align-items: center; gap: 6px; flex-wrap: wrap;
        }
        .cf-status-bar__dot {
            width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex-shrink: 0;
        }
        .cf-status-bar__dot--ok   { background: #16a34a; }
        .cf-status-bar__dot--warn { background: #f59e0b; }
        .cf-status-bar__ok { color: #16a34a; }
        .cf-form { max-width: 520px; margin-top: 20px; }
        .cf-form__group { margin-bottom: 20px; }
        .cf-form__label { display: flex; align-items: center; gap: 8px; font-weight: 600; margin-bottom: 6px; font-size: 13px; }
        .cf-form__input { width: 100%; max-width: 400px; padding: 7px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .cf-form__input--key { font-family: monospace; letter-spacing: .05em; }
        .cf-form__input--pct { width: 80px; }
        .cf-form__select { padding: 7px 10px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; min-width: 220px; }
        .cf-form__inline { display: flex; align-items: center; gap: 8px; font-size: 14px; }
        .cf-form__desc { color: #6b7280; font-size: 12px; margin: 4px 0 0; }
        .cf-form__desc--set { color: #16a34a; }
        .cf-divider { margin: 28px 0; border-color: #e5e7eb; }
        .cf-form__submit { margin-top: 8px !important; }
        </style>';
    }

    private function humanDiff(int $seconds): string
    {
        if ($seconds < 60)   return $seconds . ' s';
        if ($seconds < 3600) return (int) ($seconds / 60) . ' min';
        return (int) ($seconds / 3600) . ' h ' . (int) (($seconds % 3600) / 60) . ' min';
    }
}
