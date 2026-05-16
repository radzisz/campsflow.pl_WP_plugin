<?php
declare(strict_types=1);

namespace Campsflow\Sync;

final readonly class TransformedTurnus
{
    public function __construct(
        public string             $turnusId,
        public string             $dateFrom,
        public string             $dateTo,
        public int                $priceGrosze,
        public AvailabilityBucket $availabilityBucket,
    ) {}
}
