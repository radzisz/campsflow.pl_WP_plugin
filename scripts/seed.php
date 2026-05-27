<?php
// Runs inside the wp-env CLI container via: wp eval-file /path/seed.php --allow-root
// Triggers SyncRunner which reads tests/fixtures/api-events.json when no API key is set.

$stats = ( new Campsflow\Sync\SyncRunner() )->run();

printf(
	"Events:   +%d added  ~%d updated  ✗%d inactivated\n",
	$stats->eventsAdded,
	$stats->eventsUpdated,
	$stats->eventsInactivated
);
printf(
	"Sessions: +%d added  ~%d updated  ✗%d inactivated\n",
	$stats->sessionsAdded,
	$stats->sessionsUpdated,
	$stats->sessionsInactivated
);

if ( $stats->isFixture ) {
	echo "Source: fixture (tests/fixtures/api-events.json)\n";
}
