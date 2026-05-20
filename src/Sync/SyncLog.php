<?php
declare(strict_types=1);

namespace Campsflow\Sync;

final class SyncLog {

	private const OPTION      = 'campsflow_sync_history';
	private const MAX_ENTRIES = 50;

	public static function record( SyncStats $stats, int $durationMs, ?string $error = null ): void {
		$entry = array(
			'synced_at'   => current_time( 'Y-m-d H:i:s' ),
			'duration_ms' => $durationMs,
			'status'      => $error ? 'error' : 'ok',
			'error'       => $error,
			'is_fixture'  => $stats->isFixture,
			'stats'       => $stats->toArray(),
		);

		$history = self::getAll();
		array_unshift( $history, $entry );

		if ( count( $history ) > self::MAX_ENTRIES ) {
			$history = array_slice( $history, 0, self::MAX_ENTRIES );
		}

		update_option( self::OPTION, $history, false );
	}

	/**
	 * @return array<int, array<string, mixed>>
	 */
	public static function getAll(): array {
		$history = get_option( self::OPTION, array() );
		return is_array( $history ) ? $history : array();
	}

	public static function getAggregateStats(): AggregateStats {
		$history = self::getAll();
		$agg     = new AggregateStats();

		foreach ( $history as $entry ) {
			if ( ! is_array( $entry ) || ( $entry['status'] ?? '' ) !== 'ok' ) {
				continue;
			}

			$s                              = $entry['stats'] ?? array();
			$agg->totalEventsAdded         += (int) ( $s['events']['added'] ?? 0 );
			$agg->totalEventsUpdated       += (int) ( $s['events']['updated'] ?? 0 );
			$agg->totalEventsInactivated   += (int) ( $s['events']['inactivated'] ?? 0 );
			$agg->totalSessionsAdded       += (int) ( $s['sessions']['added'] ?? 0 );
			$agg->totalSessionsUpdated     += (int) ( $s['sessions']['updated'] ?? 0 );
			$agg->totalSessionsInactivated += (int) ( $s['sessions']['inactivated'] ?? 0 );
			++$agg->totalRuns;
		}

		return $agg;
	}
}
