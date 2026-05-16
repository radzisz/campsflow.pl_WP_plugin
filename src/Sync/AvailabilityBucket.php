<?php
declare(strict_types=1);

namespace Campsflow\Sync;

enum AvailabilityBucket: string
{
    case Available  = 'available';
    case FewLeft    = 'few_left';
    case AlmostFull = 'almost_full';
    case Full       = 'full';

    public function label(): string
    {
        return match($this) {
            self::Available  => '',
            self::FewLeft    => __('Mało miejsc', 'campsflow'),
            self::AlmostFull => __('Na wyczerpaniu', 'campsflow'),
            self::Full       => __('Brak miejsc', 'campsflow'),
        };
    }

    public function cssClass(): string
    {
        return match($this) {
            self::Available  => '',
            self::FewLeft    => 'cf-badge--warning',
            self::AlmostFull => 'cf-badge--danger',
            self::Full       => 'cf-badge--full',
        };
    }
}
