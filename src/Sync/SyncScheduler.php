<?php
declare(strict_types=1);

namespace Campsflow\Sync;

final class SyncScheduler {

	public const HOOK = 'campsflow_sync';

	/** @var array<string, string> */
	public const INTERVALS = array(
		'manual'           => 'Tylko ręcznie',
		'campsflow_15min'  => 'Co 15 minut',
		'campsflow_30min'  => 'Co 30 minut',
		'hourly'           => 'Co godzinę',
		'campsflow_2hours' => 'Co 2 godziny',
		'campsflow_6hours' => 'Co 6 godzin',
		'twicedaily'       => 'Co 12 godzin',
		'daily'            => 'Co 24 godziny',
	);

	public function register(): void {
		add_filter( 'cron_schedules', array( $this, 'addCustomIntervals' ) );
		add_action( self::HOOK, array( $this, 'runSync' ) );
		add_action( 'admin_init', array( self::class, 'ensureScheduled' ) );
	}

	/**
	 * @param array<string, array<string, int|string>> $schedules
	 * @return array<string, array<string, int|string>>
	 */
	public function addCustomIntervals( array $schedules ): array {
		$schedules['campsflow_15min']  = array(
			'interval' => 900,
			'display'  => 'Co 15 minut',
		);
		$schedules['campsflow_30min']  = array(
			'interval' => 1800,
			'display'  => 'Co 30 minut',
		);
		$schedules['campsflow_2hours'] = array(
			'interval' => 7200,
			'display'  => 'Co 2 godziny',
		);
		$schedules['campsflow_6hours'] = array(
			'interval' => 21600,
			'display'  => 'Co 6 godzin',
		);
		return $schedules;
	}

	public function runSync(): void {
		$startMs = (int) round( microtime( true ) * 1000 );
		$error   = null;

		try {
			$stats = ( new SyncRunner() )->run();
		} catch ( \Throwable $e ) {
			$stats = new SyncStats();
			$error = $e->getMessage();
		}

		$durationMs = (int) round( microtime( true ) * 1000 ) - $startMs;

		update_option( 'campsflow_last_sync', current_time( 'Y-m-d H:i:s' ) );
		SyncLog::record( $stats, $durationMs, $error );
	}

	public static function ensureScheduled(): void {
		$interval = (string) get_option( 'campsflow_sync_interval', 'hourly' );
		if ( $interval === 'manual' ) {
			return;
		}

		if ( ! wp_next_scheduled( self::HOOK ) ) {
			self::reschedule();
		}
	}

	public static function activate(): void {
		self::reschedule();
	}

	public static function deactivate(): void {
		$timestamp = wp_next_scheduled( self::HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::HOOK );
		}
	}

	public static function reschedule(): void {
		self::deactivate();

		$interval = (string) get_option( 'campsflow_sync_interval', 'hourly' );
		if ( $interval === 'manual' ) {
			return;
		}

		if ( ! wp_next_scheduled( self::HOOK ) ) {
			wp_schedule_event( time(), $interval, self::HOOK );
		}
	}
}
