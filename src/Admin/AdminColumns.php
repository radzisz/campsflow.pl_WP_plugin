<?php
declare(strict_types=1);

namespace Campsflow\Admin;

use Campsflow\Config;
use Campsflow\PostType\EventPostType;
use Campsflow\PostType\SessionPostType;

/**
 * Adds "Otwórz w CampsFlow" action column to cf_event and cf_session list tables.
 */
final class AdminColumns
{
    public function register(): void
    {
        add_filter('manage_' . EventPostType::SLUG . '_posts_columns', [$this, 'addEventColumn']);
        add_action('manage_' . EventPostType::SLUG . '_posts_custom_column', [$this, 'renderEventColumn'], 10, 2);

        add_filter('manage_' . SessionPostType::SLUG . '_posts_columns', [$this, 'addSessionColumn']);
        add_action('manage_' . SessionPostType::SLUG . '_posts_custom_column', [$this, 'renderSessionColumn'], 10, 2);
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function addEventColumn(array $columns): array
    {
        $columns['cf_open'] = '<span class="dashicons dashicons-external" title="' . esc_attr__('Otwórz w CampsFlow', 'campsflow') . '"></span>';
        return $columns;
    }

    public function renderEventColumn(string $column, int $postId): void
    {
        if ($column !== 'cf_open') {
            return;
        }

        $cfEventId  = (string) get_post_meta($postId, 'cf_event_id', true);
        $tenantSlug = (string) get_option('campsflow_tenant_slug', '');

        if (! $cfEventId || ! $tenantSlug) {
            echo '<span style="color:#d1d5db">—</span>';
            return;
        }

        $url = Config::adminEventUrl($tenantSlug, $cfEventId);
        $this->renderLink($url, $cfEventId);
    }

    /**
     * @param array<string, string> $columns
     * @return array<string, string>
     */
    public function addSessionColumn(array $columns): array
    {
        $columns['cf_open'] = '<span class="dashicons dashicons-external" title="' . esc_attr__('Otwórz w CampsFlow', 'campsflow') . '"></span>';
        return $columns;
    }

    public function renderSessionColumn(string $column, int $postId): void
    {
        if ($column !== 'cf_open') {
            return;
        }

        $cfSessionId = (string) get_post_meta($postId, 'cf_session_id', true);
        $tenantSlug  = (string) get_option('campsflow_tenant_slug', '');

        if (! $cfSessionId || ! $tenantSlug) {
            echo '<span style="color:#d1d5db">—</span>';
            return;
        }

        // Sessions need the parent event ID for the URL
        $eventPostId = (int) wp_get_post_parent_id($postId);
        $cfEventId   = $eventPostId
            ? (string) get_post_meta($eventPostId, 'cf_event_id', true)
            : '';

        $url = $cfEventId
            ? Config::adminSessionUrl($tenantSlug, $cfEventId, $cfSessionId)
            : Config::adminUrl() . '/' . $tenantSlug;

        $this->renderLink($url, $cfSessionId);
    }

    private function renderLink(string $url, string $cfId): void
    {
        $shortId = substr($cfId, 0, 8) . '…';
        echo '<a href="' . esc_url($url) . '" target="_blank" rel="noopener" class="cf-open-link" title="' . esc_attr($cfId) . '">';
        echo '<span class="dashicons dashicons-external"></span>';
        echo '<span class="cf-open-link__id">' . esc_html($shortId) . '</span>';
        echo '</a>';
        echo '<style>
            .cf-open-link { display:inline-flex; align-items:center; gap:4px; text-decoration:none; color:#2563eb; font-size:12px; }
            .cf-open-link:hover { color:#1d4ed8; }
            .cf-open-link .dashicons { font-size:14px; width:14px; height:14px; }
            .cf-open-link__id { font-family:monospace; font-size:11px; color:#6b7280; }
            .column-cf_open { width:120px; }
        </style>';
    }
}
