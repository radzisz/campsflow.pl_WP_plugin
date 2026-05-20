<?php
declare(strict_types=1);

namespace Campsflow\Sync;

final class AggregateStats {

	public int $totalRuns                = 0;
	public int $totalEventsAdded         = 0;
	public int $totalEventsUpdated       = 0;
	public int $totalEventsInactivated   = 0;
	public int $totalSessionsAdded       = 0;
	public int $totalSessionsUpdated     = 0;
	public int $totalSessionsInactivated = 0;
}
