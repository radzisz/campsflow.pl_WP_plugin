<?php
declare(strict_types=1);

namespace Campsflow\Sync;

final readonly class TransformedTurnus
{
    public function __construct(
        public string             $turnusId,
        public string             $name,
        public string             $dateFrom,
        public string             $dateTo,
        public int                $numberOfDays,
        public int                $priceGrosze,
        public string             $transport,       // JSON
        public string             $meetingPointsStart, // JSON
        public string             $meetingPointsReturn, // JSON
        public int                $seatsAvailable,
        public int                $seatsAll,
        public string             $reservationUrl,
        public AvailabilityBucket $availabilityBucket,
    ) {}
}
