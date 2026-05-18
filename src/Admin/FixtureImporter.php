<?php
declare(strict_types=1);

namespace Campsflow\Admin;

use Campsflow\PostType\EventPostType;
use Campsflow\PostType\PostStatus;
use Campsflow\PostType\SessionPostType;
use Campsflow\Sync\AvailabilityBucket;
use Campsflow\Sync\SyncLog;
use Campsflow\Sync\SyncStats;
use Campsflow\Sync\Transformer;
use Campsflow\Taxonomy\AgeGroupTaxonomy;
use Campsflow\Taxonomy\CampTagTaxonomy;

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

        $startMs     = (int) round(microtime(true) * 1000);
        $fixturePath = CAMPSFLOW_PLUGIN_DIR . 'tests/fixtures/api-events.json';

        if (! file_exists($fixturePath)) {
            wp_die(esc_html__('Plik fixtures nie istnieje.', 'campsflow'));
        }

        $json   = file_get_contents($fixturePath);
        $events = json_decode((string) $json, true);
        assert(is_array($events));

        $transformer = new Transformer();
        $stats       = new SyncStats();
        $seenEventIds   = [];
        $seenSessionIds = [];

        foreach ($events as $event) {
            assert(is_array($event));
            [$eventPostId, $isNew] = $this->upsertEvent($event, $stats);
            $seenEventIds[] = (string) $event['id'];

            foreach (($event['turnusy'] ?? []) as $turnus) {
                assert(is_array($turnus));
                $transformed = $transformer->transformTurnus($turnus);
                $this->upsertSession($turnus, $transformed->availabilityBucket, $eventPostId, $stats);
                $seenSessionIds[] = (string) $turnus['id'];
            }
        }

        $this->inactivateMissing(EventPostType::SLUG, 'cf_event_id', $seenEventIds, $stats, true);
        $this->inactivateMissing(SessionPostType::SLUG, 'cf_session_id', $seenSessionIds, $stats, false);

        $durationMs = (int) round(microtime(true) * 1000) - $startMs;
        update_option('campsflow_last_sync', current_time('Y-m-d H:i:s'));
        SyncLog::record($stats, $durationMs);

        wp_redirect(add_query_arg(
            ['page' => 'cf-settings', 'tab' => 'history', 'cf_synced' => $stats->totalSessions()],
            admin_url('edit.php?post_type=' . EventPostType::SLUG),
        ));
        exit;
    }

    /**
     * @param array<string, mixed> $event
     * @return array{int, bool}
     */
    private function upsertEvent(array $event, SyncStats $stats): array
    {
        $cfId      = (string) $event['id'];
        $existing  = $this->findByMeta(EventPostType::SLUG, 'cf_event_id', $cfId);
        $cfStatus  = (string) ($event['status'] ?? 'published');
        $wpStatus  = $cfStatus === 'published' ? 'publish' : PostStatus::INACTIVE;

        $postData = [
            'post_type'    => EventPostType::SLUG,
            'post_status'  => $wpStatus,
            'post_title'   => (string) ($event['name'] ?? ''),
            'post_content' => (string) ($event['description'] ?? ''),
        ];

        if ($existing) {
            $postData['ID'] = $existing;
            wp_update_post($postData);
            $postId = $existing;
            $stats->eventsUpdated++;
        } else {
            $postId = (int) wp_insert_post($postData);
            $stats->eventsAdded++;
        }

        update_post_meta($postId, 'cf_event_id', $cfId);

        if (isset($event['location']) && is_array($event['location'])) {
            update_post_meta($postId, 'cf_location', wp_json_encode($event['location']));
        }

        if (! empty($event['tags']) && is_array($event['tags'])) {
            wp_set_object_terms($postId, $event['tags'], CampTagTaxonomy::SLUG);
        }

        $this->setAgeGroupTerms($postId, $event);

        return [$postId, ! $existing];
    }

    /**
     * @param array<string, mixed> $turnus
     */
    private function upsertSession(array $turnus, AvailabilityBucket $bucket, int $eventPostId, SyncStats $stats): void
    {
        $cfId     = (string) $turnus['id'];
        $existing = $this->findByMeta(SessionPostType::SLUG, 'cf_session_id', $cfId);
        $dateFrom = (string) ($turnus['dateFrom'] ?? '');
        $title    = $dateFrom
            ? date_create($dateFrom)?->format('d.m.Y') ?? $dateFrom
            : $cfId;

        $cfStatus = (string) ($turnus['status'] ?? 'published');
        $wpStatus = $cfStatus === 'published' ? 'publish' : PostStatus::INACTIVE;

        $postData = [
            'post_type'   => SessionPostType::SLUG,
            'post_status' => $wpStatus,
            'post_title'  => $title,
            'post_parent' => $eventPostId,
        ];

        if ($existing) {
            $postData['ID'] = $existing;
            wp_update_post($postData);
            $postId = $existing;
            $stats->sessionsUpdated++;
        } else {
            $postId = (int) wp_insert_post($postData);
            $stats->sessionsAdded++;
        }

        update_post_meta($postId, 'cf_session_id', $cfId);
        update_post_meta($postId, 'cf_date_from', $dateFrom);
        update_post_meta($postId, 'cf_date_to', (string) ($turnus['dateTo'] ?? ''));
        update_post_meta($postId, 'cf_price', (int) ($turnus['price'] ?? 0));
        update_post_meta($postId, 'cf_availability', $bucket->value);
    }

    /**
     * @param string[] $seenIds
     */
    private function inactivateMissing(
        string $postType,
        string $metaKey,
        array $seenIds,
        SyncStats $stats,
        bool $isEvent,
    ): void {
        $existing = get_posts([
            'post_type'   => $postType,
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields'      => 'ids',
        ]);

        foreach ($existing as $postId) {
            $cfId = (string) get_post_meta((int) $postId, $metaKey, true);
            if (! $cfId || in_array($cfId, $seenIds, true)) {
                continue;
            }

            wp_update_post(['ID' => (int) $postId, 'post_status' => PostStatus::INACTIVE]);

            if ($isEvent) {
                $stats->eventsInactivated++;
            } else {
                $stats->sessionsInactivated++;
            }
        }
    }

    private function findByMeta(string $postType, string $metaKey, string $metaValue): int
    {
        $posts = get_posts([
            'post_type'   => $postType,
            'post_status' => 'any',
            'meta_key'    => $metaKey,
            'meta_value'  => $metaValue,
            'numberposts' => 1,
            'fields'      => 'ids',
        ]);

        return ! empty($posts) ? (int) $posts[0] : 0;
    }

    /**
     * @param array<string, mixed> $event
     */
    private function setAgeGroupTerms(int $postId, array $event): void
    {
        $minAge = isset($event['minAge']) ? (int) $event['minAge'] : null;
        $maxAge = isset($event['maxAge']) ? (int) $event['maxAge'] : null;

        if ($minAge === null || $maxAge === null) {
            return;
        }

        wp_set_object_terms($postId, [$minAge . '–' . $maxAge . ' lat'], AgeGroupTaxonomy::SLUG);
    }
}
