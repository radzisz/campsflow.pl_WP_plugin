<?php
declare(strict_types=1);

namespace Campsflow\Sync;

final class SyncScheduler
{
    public const HOOK = 'campsflow_sync';

    /** @var array<string, string> */
    public const INTERVALS = [
        'manual'              => 'Tylko ręcznie',
        'campsflow_15min'     => 'Co 15 minut',
        'campsflow_30min'     => 'Co 30 minut',
        'hourly'              => 'Co godzinę',
        'campsflow_2hours'    => 'Co 2 godziny',
        'campsflow_6hours'    => 'Co 6 godzin',
        'twicedaily'          => 'Co 12 godzin',
        'daily'               => 'Co 24 godziny',
    ];

    public function register(): void
    {
        add_filter('cron_schedules', [$this, 'addCustomIntervals']);
        add_action(self::HOOK, [$this, 'runSync']);
    }

    /**
     * @param array<string, array<string, int|string>> $schedules
     * @return array<string, array<string, int|string>>
     */
    public function addCustomIntervals(array $schedules): array
    {
        $schedules['campsflow_15min']  = ['interval' => 900,   'display' => 'Co 15 minut'];
        $schedules['campsflow_30min']  = ['interval' => 1800,  'display' => 'Co 30 minut'];
        $schedules['campsflow_2hours'] = ['interval' => 7200,  'display' => 'Co 2 godziny'];
        $schedules['campsflow_6hours'] = ['interval' => 21600, 'display' => 'Co 6 godzin'];
        return $schedules;
    }

    public function runSync(): void
    {
        // TODO: replace with real Fetcher when public API is ready
        // SyncRunner::fromApi()->run();
        update_option('campsflow_last_sync', current_time('Y-m-d H:i:s'));
    }

    public static function activate(): void
    {
        self::reschedule();
    }

    public static function deactivate(): void
    {
        $timestamp = wp_next_scheduled(self::HOOK);
        if ($timestamp) {
            wp_unschedule_event($timestamp, self::HOOK);
        }
    }

    public static function reschedule(): void
    {
        self::deactivate();

        $interval = (string) get_option('campsflow_sync_interval', 'hourly');
        if ($interval === 'manual') {
            return;
        }

        if (! wp_next_scheduled(self::HOOK)) {
            wp_schedule_event(time(), $interval, self::HOOK);
        }
    }
}
