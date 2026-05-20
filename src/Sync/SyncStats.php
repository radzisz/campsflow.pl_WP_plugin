<?php
declare(strict_types=1);

namespace Campsflow\Sync;

final class SyncStats {

	public int $eventsAdded         = 0;
	public int $eventsUpdated       = 0;
	public int $eventsInactivated   = 0;
	public int $sessionsAdded       = 0;
	public int $sessionsUpdated     = 0;
	public int $sessionsInactivated = 0;
	public bool $isFixture          = false;

	public function totalSessions(): int {
		return $this->sessionsAdded + $this->sessionsUpdated;
	}

	/** @return array<string, array<string, int>> */
	public function toArray(): array {
		return array(
			'events'   => array(
				'added'       => $this->eventsAdded,
				'updated'     => $this->eventsUpdated,
				'inactivated' => $this->eventsInactivated,
			),
			'sessions' => array(
				'added'       => $this->sessionsAdded,
				'updated'     => $this->sessionsUpdated,
				'inactivated' => $this->sessionsInactivated,
			),
		);
	}
}
