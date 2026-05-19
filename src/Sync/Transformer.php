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
        assert(isset($apiTurnus['id'], $apiTurnus['seatsAvailable'], $apiTurnus['seatsAll']));

        $transport = isset($apiTurnus['transport']) && is_array($apiTurnus['transport'])
            ? (string) wp_json_encode($apiTurnus['transport'], JSON_UNESCAPED_UNICODE)
            : (string) wp_json_encode(['type' => 'own', 'description' => '']);

        $meetingStart = isset($apiTurnus['meetingPoints_start']) && is_array($apiTurnus['meetingPoints_start'])
            ? (string) wp_json_encode($apiTurnus['meetingPoints_start'], JSON_UNESCAPED_UNICODE)
            : '[]';

        $meetingReturn = isset($apiTurnus['meetingPoints_return']) && is_array($apiTurnus['meetingPoints_return'])
            ? (string) wp_json_encode($apiTurnus['meetingPoints_return'], JSON_UNESCAPED_UNICODE)
            : '[]';

        return new TransformedTurnus(
            turnusId:             (string) $apiTurnus['id'],
            name:                 (string) ($apiTurnus['name'] ?? ''),
            dateFrom:             (string) ($apiTurnus['dateFrom'] ?? ''),
            dateTo:               (string) ($apiTurnus['dateTo'] ?? ''),
            numberOfDays:         (int)    ($apiTurnus['numberOfDays'] ?? 0),
            priceGrosze:          (int)    ($apiTurnus['priceFrom'] ?? 0),
            transport:            $transport,
            meetingPointsStart:   $meetingStart,
            meetingPointsReturn:  $meetingReturn,
            seatsAvailable:       (int) $apiTurnus['seatsAvailable'],
            seatsAll:             (int) $apiTurnus['seatsAll'],
            reservationUrl:       (string) ($apiTurnus['reservationUrl'] ?? ''),
            availabilityBucket:   $this->computeBucket(
                (int) $apiTurnus['seatsAll'],
                (int) $apiTurnus['seatsAvailable'],
            ),
        );
    }

    private function computeBucket(int $seatsAll, int $seatsAvailable): AvailabilityBucket
    {
        if ($seatsAll <= 0 || $seatsAvailable <= 0) {
            return AvailabilityBucket::Full;
        }

        $ratio = $seatsAvailable / $seatsAll;

        if ($ratio <= $this->almostFullThreshold) {
            return AvailabilityBucket::AlmostFull;
        }

        if ($ratio <= $this->fewLeftThreshold) {
            return AvailabilityBucket::FewLeft;
        }

        return AvailabilityBucket::Available;
    }
}
