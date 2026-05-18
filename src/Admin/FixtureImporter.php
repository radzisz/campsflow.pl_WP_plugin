<?php
declare(strict_types=1);

namespace Campsflow\Admin;

use Campsflow\PostType\EventPostType;
use Campsflow\Sync\SyncLog;
use Campsflow\Sync\SyncRunner;

final class FixtureImporter
{
    public function register(): void
    {
        add_action('admin_post_cf_sync_now', [$this, 'handleImport']);
    }

    public function handleImport(): void
    {
        if (! current_user_can('manage_options')) {
            wp_die(esc_html__('Brak uprawnień.', 'campsflow'));
        }

        check_admin_referer('cf_sync_now');

        $startMs = (int) round(microtime(true) * 1000);
        $error   = null;

        try {
            $stats = (new SyncRunner())->run();
        } catch (\Throwable $e) {
            $stats = new \Campsflow\Sync\SyncStats();
            $error = $e->getMessage();
        }

        $durationMs = (int) round(microtime(true) * 1000) - $startMs;

        update_option('campsflow_last_sync', current_time('Y-m-d H:i:s'));
        SyncLog::record($stats, $durationMs, $error);

        wp_redirect(add_query_arg(
            ['page' => 'cf-settings', 'tab' => 'history', 'cf_synced' => $stats->totalSessions()],
            admin_url('edit.php?post_type=' . EventPostType::SLUG),
        ));
        exit;
    }
}
