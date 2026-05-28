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
        Monkey\Functions\when('wp_json_encode')->alias('json_encode');
        Monkey\Functions\when('sanitize_text_field')->returnArg();
    }

    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    #[Test]
    public function it_returns_full_bucket_when_no_seats_available(): void
    {
        $transformer = new Transformer(fewLeftThreshold: 0.25, almostFullThreshold: 0.10);
        $result = $transformer->transformTurnus($this->makeTurnus(seatsAll: 40, seatsAvailable: 0));

        self::assertSame(AvailabilityBucket::Full, $result->availabilityBucket);
    }

    #[Test]
    public function it_returns_full_bucket_when_seats_all_is_zero(): void
    {
        $transformer = new Transformer(fewLeftThreshold: 0.25, almostFullThreshold: 0.10);
        $result = $transformer->transformTurnus($this->makeTurnus(seatsAll: 0, seatsAvailable: 0));

        self::assertSame(AvailabilityBucket::Full, $result->availabilityBucket);
    }

    #[Test]
    public function it_returns_almost_full_bucket_when_at_threshold(): void
    {
        $transformer = new Transformer(fewLeftThreshold: 0.25, almostFullThreshold: 0.10);
        // 4/40 = 10% available — exactly at threshold → almost_full
        $result = $transformer->transformTurnus($this->makeTurnus(seatsAll: 40, seatsAvailable: 4));

        self::assertSame(AvailabilityBucket::AlmostFull, $result->availabilityBucket);
    }

    #[Test]
    public function it_returns_few_left_bucket_between_thresholds(): void
    {
        $transformer = new Transformer(fewLeftThreshold: 0.25, almostFullThreshold: 0.10);
        // 8/40 = 20% available — between 10% and 25% → few_left
        $result = $transformer->transformTurnus($this->makeTurnus(seatsAll: 40, seatsAvailable: 8));

        self::assertSame(AvailabilityBucket::FewLeft, $result->availabilityBucket);
    }

    #[Test]
    public function it_returns_available_bucket_when_plenty_of_seats(): void
    {
        $transformer = new Transformer(fewLeftThreshold: 0.25, almostFullThreshold: 0.10);
        // 30/40 = 75% available → available
        $result = $transformer->transformTurnus($this->makeTurnus(seatsAll: 40, seatsAvailable: 30));

        self::assertSame(AvailabilityBucket::Available, $result->availabilityBucket);
    }

    #[Test]
    public function it_maps_all_fields_from_api(): void
    {
        $transformer = new Transformer();
        $result = $transformer->transformTurnus($this->makeTurnus(seatsAll: 40, seatsAvailable: 20));

        self::assertSame('session-001-uuid', $result->turnusId);
        self::assertSame('Turnus I', $result->name);
        self::assertSame('2026-07-01', $result->dateFrom);
        self::assertSame('2026-07-07', $result->dateTo);
        self::assertSame(7, $result->numberOfDays);
        self::assertSame(189000, $result->priceGrosze);
        self::assertSame(20, $result->seatsAvailable);
        self::assertSame(40, $result->seatsAll);
        self::assertSame('https://campsflow.pl/embed/oaza-test/register?session=session-001-uuid', $result->reservationUrl);
    }

    /**
     * @return array<string, mixed>
     */
    private function makeTurnus(int $seatsAll, int $seatsAvailable): array
    {
        return [
            'id'             => 'session-001-uuid',
            'name'           => 'Turnus I',
            'dateFrom'       => '2026-07-01',
            'dateTo'         => '2026-07-07',
            'numberOfDays'   => 7,
            'priceFrom'      => 189000,
            'transport'      => ['type' => 'bus', 'description' => 'Autokar'],
            'meetingPoints_start'  => [],
            'meetingPoints_return' => [],
            'seatsAvailable' => $seatsAvailable,
            'seatsAll'       => $seatsAll,
            'reservationUrl' => 'https://campsflow.pl/embed/oaza-test/register?session=session-001-uuid',
        ];
    }
}
