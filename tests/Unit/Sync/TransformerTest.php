<?php
declare(strict_types=1);

namespace Campsflow\Tests\Unit\Sync;

use Brain\Monkey;
use Campsflow\Sync\AvailabilityBucket;
use Campsflow\Sync\Transformer;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;

class TransformerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_full_bucket_when_no_seats_available(): void
    {
        $transformer = new Transformer(fewLeftThreshold: 0.30, almostFullThreshold: 0.10);
        $result = $transformer->transformTurnus($this->makeTurnus(totalSeats: 40, availableSeats: 0));

        self::assertSame(AvailabilityBucket::Full, $result->availabilityBucket);
    }

    #[Test]
    public function it_returns_full_bucket_when_total_seats_is_zero(): void
    {
        $transformer = new Transformer(fewLeftThreshold: 0.30, almostFullThreshold: 0.10);
        $result = $transformer->transformTurnus($this->makeTurnus(totalSeats: 0, availableSeats: 0));

        self::assertSame(AvailabilityBucket::Full, $result->availabilityBucket);
    }

    #[Test]
    public function it_returns_almost_full_bucket_when_at_threshold(): void
    {
        $transformer = new Transformer(fewLeftThreshold: 0.30, almostFullThreshold: 0.10);
        // 4/40 = 10% available — exactly at threshold → almost_full
        $result = $transformer->transformTurnus($this->makeTurnus(totalSeats: 40, availableSeats: 4));

        self::assertSame(AvailabilityBucket::AlmostFull, $result->availabilityBucket);
    }

    #[Test]
    public function it_returns_few_left_bucket_between_thresholds(): void
    {
        $transformer = new Transformer(fewLeftThreshold: 0.30, almostFullThreshold: 0.10);
        // 9/40 = 22.5% available — between 10% and 30% → few_left
        $result = $transformer->transformTurnus($this->makeTurnus(totalSeats: 40, availableSeats: 9));

        self::assertSame(AvailabilityBucket::FewLeft, $result->availabilityBucket);
    }

    #[Test]
    public function it_returns_available_bucket_when_plenty_of_seats(): void
    {
        $transformer = new Transformer(fewLeftThreshold: 0.30, almostFullThreshold: 0.10);
        // 30/40 = 75% available → available
        $result = $transformer->transformTurnus($this->makeTurnus(totalSeats: 40, availableSeats: 30));

        self::assertSame(AvailabilityBucket::Available, $result->availabilityBucket);
    }

    #[Test]
    public function it_maps_price_and_dates_from_api(): void
    {
        $transformer = new Transformer();
        $result = $transformer->transformTurnus($this->makeTurnus(totalSeats: 40, availableSeats: 20));

        self::assertSame('session-001-uuid', $result->turnusId);
        self::assertSame('2026-07-01', $result->dateFrom);
        self::assertSame('2026-07-14', $result->dateTo);
        self::assertSame(189000, $result->priceGrosze);
    }

    /**
     * @return array<string, mixed>
     */
    private function makeTurnus(int $totalSeats, int $availableSeats): array
    {
        return [
            'id'             => 'session-001-uuid',
            'dateFrom'       => '2026-07-01',
            'dateTo'         => '2026-07-14',
            'price'          => 189000,
            'status'         => 'published',
            'totalSeats'     => $totalSeats,
            'availableSeats' => $availableSeats,
        ];
    }
}
