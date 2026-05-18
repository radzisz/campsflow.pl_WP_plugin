<?php
declare(strict_types=1);

namespace Campsflow\Admin;

use Campsflow\Config;
use Campsflow\PostType\EventPostType;
use Campsflow\Sync\SyncLog;
use Campsflow\Sync\SyncScheduler;

final class SettingsPage
{
    private const TAB_SYNC     = 'sync';
    private const TAB_SETTINGS = 'settings';
    private const TAB_HISTORY  = 'history';

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
            'campsflow_few_left_pct'    => '25',
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

        $activeTab = in_array($_GET['tab'] ?? '', [self::TAB_SYNC, self::TAB_SETTINGS, self::TAB_HISTORY], true)
            ? $_GET['tab']
            : self::TAB_SYNC;

        echo '<div class="wrap">';
        echo '<h1>' . esc_html__('CampsFlow', 'campsflow') . '</h1>';

        $this->renderTabs($activeTab);
        $this->renderStatusBar();

        if ($activeTab === self::TAB_SYNC) {
            $this->renderSyncTab();
        } elseif ($activeTab === self::TAB_HISTORY) {
            $this->renderHistoryTab();
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
            self::TAB_HISTORY  => __('Historia', 'campsflow'),
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
            $diff = $nextRun - time();
            echo '<span class="cf-status-bar__dot cf-status-bar__dot--ok"></span>';
            echo esc_html__('Synchronizacja aktywna', 'campsflow') . ' · ';
            if ($diff > 0) {
                echo esc_html__('Następna za:', 'campsflow') . ' <strong>' . esc_html($this->humanDiff($diff)) . '</strong>';
            } else {
                echo '<strong>' . esc_html__('Oczekuje na uruchomienie (odwiedź stronę publiczną)', 'campsflow') . '</strong>';
            }
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
        echo '<div class="cf-form__interval-row">';
        echo '<select class="cf-form__select" id="campsflow_sync_interval" name="campsflow_sync_interval">';
        foreach (SyncScheduler::INTERVALS as $value => $label) {
            $selected = selected($interval, $value, false);
            echo '<option value="' . esc_attr($value) . '"' . $selected . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';

        // Button is OUTSIDE the settings form via the HTML `form` attribute (HTML5 standard).
        // The hidden sync-now form is rendered below (id="cf-sync-now-form").
        echo '<button type="submit" form="cf-sync-now-form" class="button button-secondary cf-sync-now-btn">';
        echo '<span class="dashicons dashicons-update"></span> ';
        echo esc_html__('Synchronizuj teraz', 'campsflow');
        echo '</button>';

        echo '</div></div>';
        submit_button(__('Zapisz', 'campsflow'), 'primary cf-form__submit');
        echo '</form>';

        // Hidden sync-now form — associated to the button above via id="cf-sync-now-form"
        echo '<form id="cf-sync-now-form" method="post" action="' . esc_url(admin_url('admin-post.php')) . '" style="display:none">';
        wp_nonce_field('cf_sync_now');
        echo '<input type="hidden" name="action" value="cf_sync_now">';
        echo '</form>';
        echo '<script>
        document.querySelector(".cf-sync-now-btn").addEventListener("click", function(e) {
            e.preventDefault();
            var btn = this;
            btn.classList.add("is-syncing");
            btn.disabled = true;
            document.getElementById("cf-sync-now-form").submit();
        });
        </script>';

        $this->renderUrlInfo();
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
        echo '<p class="cf-form__desc">' . esc_html__('Domyślnie: 25% — pokazuj gdy zostało mniej niż 25% miejsc.', 'campsflow') . '</p>';
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

    private function renderUrlInfo(): void
    {
        $tenantSlug = (string) get_option('campsflow_tenant_slug', '');
        $apiUrl     = Config::apiUrl();
        $adminUrl   = Config::adminUrl();
        $apiOverride   = Config::isOverridden('CAMPSFLOW_API_URL');
        $adminOverride = Config::isOverridden('CAMPSFLOW_ADMIN_URL');
        $eventsUrl  = $tenantSlug ? Config::eventsEndpoint($tenantSlug) : $apiUrl . '/api/v1/public/{tenant_slug}/events';

        echo '<hr class="cf-divider">';
        echo '<h3>' . esc_html__('Adresy URL synchronizacji', 'campsflow') . '</h3>';
        echo '<table class="cf-url-table">';
        $this->urlRow('API', $apiUrl, $apiOverride, 'CAMPSFLOW_API_URL');
        $this->urlRow('Events endpoint', $eventsUrl, $apiOverride, 'CAMPSFLOW_API_URL');
        $this->urlRow('Panel Campsflow', $adminUrl, $adminOverride, 'CAMPSFLOW_ADMIN_URL');
        echo '</table>';

        echo '<details class="cf-url-override">';
        echo '<summary>' . esc_html__('Jak nadpisać URL (np. localhost do testów)', 'campsflow') . '</summary>';
        echo '<div class="cf-url-override__body">';
        echo '<p class="cf-form__desc">' . esc_html__('Opcja 1 — stała PHP w wp-config.php:', 'campsflow') . '</p>';
        echo '<pre class="cf-code">define(\'CAMPSFLOW_API_URL\', \'http://localhost:3000\');
define(\'CAMPSFLOW_ADMIN_URL\', \'http://localhost:3001\');</pre>';
        echo '<p class="cf-form__desc">' . esc_html__('Opcja 2 — w .wp-env.json (środowisko deweloperskie):', 'campsflow') . '</p>';
        echo '<pre class="cf-code">"config": {
  "CAMPSFLOW_API_URL": "http://host.docker.internal:3000",
  "CAMPSFLOW_ADMIN_URL": "http://host.docker.internal:3001"
}</pre>';
        echo '<p class="cf-form__desc">' . esc_html__('Zmienne środowiskowe (OS / Docker):', 'campsflow') . '</p>';
        echo '<pre class="cf-code">CAMPSFLOW_API_URL=http://localhost:3000</pre>';
        echo '</div></details>';
    }

    private function urlRow(string $label, string $url, bool $overridden, string $constant): void
    {
        $badge = $overridden
            ? '<span class="cf-url-badge cf-url-badge--override" title="' . esc_attr($constant) . '">' . esc_html__('nadpisany', 'campsflow') . '</span>'
            : '<span class="cf-url-badge cf-url-badge--default">' . esc_html__('domyślny', 'campsflow') . '</span>';

        echo '<tr>';
        echo '<td class="cf-url-table__label">' . esc_html($label) . '</td>';
        echo '<td><code class="cf-url-code">' . esc_html($url) . '</code></td>';
        echo '<td>' . $badge . '</td>';
        echo '</tr>';
    }

    private function renderHistoryTab(): void
    {
        $history = SyncLog::getAll();
        $agg     = SyncLog::getAggregateStats();

        // Aggregate stats cards
        echo '<div class="cf-stat-grid">';
        $this->statCard(__('Imprezy dodane', 'campsflow'), $agg->totalEventsAdded, 'added');
        $this->statCard(__('Imprezy zaktualizowane', 'campsflow'), $agg->totalEventsUpdated, 'updated');
        $this->statCard(__('Imprezy nieaktywne', 'campsflow'), $agg->totalEventsInactivated, 'inactivated');
        $this->statCard(__('Turnusy dodane', 'campsflow'), $agg->totalSessionsAdded, 'added');
        $this->statCard(__('Turnusy zaktualizowane', 'campsflow'), $agg->totalSessionsUpdated, 'updated');
        $this->statCard(__('Turnusy nieaktywne', 'campsflow'), $agg->totalSessionsInactivated, 'inactivated');
        echo '</div>';

        if (empty($history)) {
            echo '<p class="cf-history__empty">' . esc_html__('Brak historii synchronizacji. Uruchom pierwszą synchronizację.', 'campsflow') . '</p>';
            return;
        }

        // History table
        echo '<table class="widefat cf-history-table">';
        echo '<thead><tr>';
        echo '<th>' . esc_html__('Data', 'campsflow') . '</th>';
        echo '<th>' . esc_html__('Status', 'campsflow') . '</th>';
        echo '<th>' . esc_html__('Czas', 'campsflow') . '</th>';
        echo '<th>' . esc_html__('Imprezy +/↻/×', 'campsflow') . '</th>';
        echo '<th>' . esc_html__('Turnusy +/↻/×', 'campsflow') . '</th>';
        echo '</tr></thead>';
        echo '<tbody>';

        foreach ($history as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $status   = (string) ($entry['status'] ?? 'ok');
            $stats    = $entry['stats'] ?? [];
            $ev       = $stats['events'] ?? [];
            $se       = $stats['sessions'] ?? [];
            $duration = isset($entry['duration_ms']) ? round((int) $entry['duration_ms'] / 1000, 1) . ' s' : '—';
            $error    = (string) ($entry['error'] ?? '');

            echo '<tr class="cf-history-row cf-history-row--' . esc_attr($status) . '">';
            echo '<td><strong>' . esc_html((string) ($entry['synced_at'] ?? '')) . '</strong></td>';
            echo '<td>';
            if ($status === 'ok') {
                echo '<span class="cf-badge-status cf-badge-status--ok">✓ OK</span>';
            } else {
                echo '<span class="cf-badge-status cf-badge-status--error">✗ Błąd</span>';
                if ($error) {
                    echo '<br><small style="color:#991b1b">' . esc_html($error) . '</small>';
                }
            }
            echo '</td>';
            echo '<td>' . esc_html($duration) . '</td>';
            echo '<td class="cf-history-stats">';
            echo '<span class="cf-stat--added">+' . (int) ($ev['added'] ?? 0) . '</span> ';
            echo '<span class="cf-stat--updated">↻' . (int) ($ev['updated'] ?? 0) . '</span> ';
            echo '<span class="cf-stat--inactivated">×' . (int) ($ev['inactivated'] ?? 0) . '</span>';
            echo '</td>';
            echo '<td class="cf-history-stats">';
            echo '<span class="cf-stat--added">+' . (int) ($se['added'] ?? 0) . '</span> ';
            echo '<span class="cf-stat--updated">↻' . (int) ($se['updated'] ?? 0) . '</span> ';
            echo '<span class="cf-stat--inactivated">×' . (int) ($se['inactivated'] ?? 0) . '</span>';
            echo '</td>';
            echo '</tr>';
        }

        echo '</tbody></table>';
    }

    private function statCard(string $label, int $value, string $type): void
    {
        echo '<div class="cf-stat-card cf-stat-card--' . esc_attr($type) . '">';
        echo '<div class="cf-stat-card__value">' . esc_html((string) $value) . '</div>';
        echo '<div class="cf-stat-card__label">' . esc_html($label) . '</div>';
        echo '</div>';
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
        .cf-form__interval-row { display: flex; align-items: center; gap: 10px; }
        .cf-sync-now-btn { display: inline-flex !important; align-items: center; gap: 5px; }
        .cf-sync-now-btn .dashicons { font-size: 16px; width: 16px; height: 16px; margin-top: 0; }
        .cf-sync-now-btn.is-syncing { opacity: .7; cursor: default; }
        .cf-sync-now-btn.is-syncing .dashicons { animation: cf-spin 1s linear infinite; }
        @keyframes cf-spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

        /* URL table */
        .cf-url-table { border-collapse: collapse; margin-bottom: 12px; }
        .cf-url-table td { padding: 5px 12px 5px 0; vertical-align: middle; font-size: 13px; }
        .cf-url-table__label { color: #6b7280; white-space: nowrap; min-width: 130px; }
        .cf-url-code { background: #f3f4f6; padding: 2px 6px; border-radius: 3px; font-size: 12px; word-break: break-all; }
        .cf-url-badge { font-size: 11px; padding: 1px 6px; border-radius: 10px; font-weight: 600; white-space: nowrap; }
        .cf-url-badge--default  { background: #f3f4f6; color: #6b7280; }
        .cf-url-badge--override { background: #fef3c7; color: #92400e; }
        .cf-url-override { margin-top: 4px; }
        .cf-url-override summary { cursor: pointer; font-size: 13px; color: #2563eb; }
        .cf-url-override summary:hover { text-decoration: underline; }
        .cf-url-override__body { margin-top: 10px; }
        .cf-code { background: #1e1e2e; color: #cdd6f4; padding: 10px 14px; border-radius: 6px; font-size: 12px; overflow-x: auto; }

        /* Stat grid */
        .cf-stat-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 20px 0 28px; max-width: 700px; }
        .cf-stat-card { background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 14px 16px; text-align: center; }
        .cf-stat-card--added       { border-top: 3px solid #16a34a; }
        .cf-stat-card--updated     { border-top: 3px solid #2563eb; }
        .cf-stat-card--inactivated { border-top: 3px solid #9ca3af; }
        .cf-stat-card__value { font-size: 2rem; font-weight: 700; line-height: 1; }
        .cf-stat-card--added .cf-stat-card__value       { color: #16a34a; }
        .cf-stat-card--updated .cf-stat-card__value     { color: #2563eb; }
        .cf-stat-card--inactivated .cf-stat-card__value { color: #9ca3af; }
        .cf-stat-card__label { font-size: 11px; color: #6b7280; margin-top: 4px; }

        /* History table */
        .cf-history-table { margin-top: 8px; }
        .cf-history-table th { font-weight: 600; font-size: 12px; text-transform: uppercase; letter-spacing: .04em; color: #6b7280; }
        .cf-history-row--error td { background: #fff5f5; }
        .cf-history__empty { color: #9ca3af; margin-top: 24px; }
        .cf-badge-status { font-size: 12px; font-weight: 700; padding: 2px 7px; border-radius: 4px; }
        .cf-badge-status--ok    { background: #dcfce7; color: #16a34a; }
        .cf-badge-status--error { background: #fee2e2; color: #991b1b; }
        .cf-history-stats { font-family: monospace; font-size: 13px; letter-spacing: .03em; }
        .cf-stat--added       { color: #16a34a; font-weight: 600; }
        .cf-stat--updated     { color: #2563eb; font-weight: 600; }
        .cf-stat--inactivated { color: #9ca3af; font-weight: 600; }
        </style>';
    }

    private function humanDiff(int $seconds): string
    {
        if ($seconds < 60)   return $seconds . ' s';
        if ($seconds < 3600) return (int) ($seconds / 60) . ' min';
        return (int) ($seconds / 3600) . ' h ' . (int) (($seconds % 3600) / 60) . ' min';
    }
}
