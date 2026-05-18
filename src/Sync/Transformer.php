<?php
declare(strict_types=1);

namespace Campsflow\Sync;

final class Transformer
{
    public function __construct(
        private readonly float $fewLeftThreshold   = 0.25,
        private readonly float $almostFullThreshold = 0.10,
    ) {
        assert($this->almostFullThreshold < $this->fewLeftThreshold);
        assert($this->fewLeftThreshold > 0.0 && $this->fewLeftThreshold <= 1.0);
    }

    /**
     * @param array<string, mixed> $apiTurnus
     */
    public function transformTurnus(array $apiTurnus): TransformedTurnus
    {
        assert(isset($apiTurnus['id'], $apiTurnus['availableSeats'], $apiTurnus['totalSeats']));

        return new TransformedTurnus(
            turnusId:            (string) $apiTurnus['id'],
            dateFrom:            (string) ($apiTurnus['dateFrom'] ?? ''),
            dateTo:              (string) ($apiTurnus['dateTo'] ?? ''),
            priceGrosze:         (int)    ($apiTurnus['price'] ?? 0),
            availabilityBucket:  $this->computeBucket(
                (int) $apiTurnus['totalSeats'],
                (int) $apiTurnus['availableSeats'],
            ),
        );
    }

    private function computeBucket(int $totalSeats, int $availableSeats): AvailabilityBucket
    {
        if ($totalSeats <= 0 || $availableSeats <= 0) {
            return AvailabilityBucket::Full;
        }

        $ratio = $availableSeats / $totalSeats;

        if ($ratio <= $this->almostFullThreshold) {
            return AvailabilityBucket::AlmostFull;
        }

        if ($ratio <= $this->fewLeftThreshold) {
            return AvailabilityBucket::FewLeft;
        }

        return AvailabilityBucket::Available;
    }
}
