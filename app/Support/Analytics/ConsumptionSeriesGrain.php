<?php

declare(strict_types=1);

namespace App\Support\Analytics;

enum ConsumptionSeriesGrain: string
{
    case Hourly = 'hourly';
    case Daily = 'daily';

    public function minutes(): int
    {
        return match ($this) {
            self::Hourly => 60,
            self::Daily => 1440,
        };
    }

    public function reportWindowEndOffsetMinutes(): int
    {
        return match ($this) {
            self::Hourly => 55,
            self::Daily => 1435,
        };
    }
}
