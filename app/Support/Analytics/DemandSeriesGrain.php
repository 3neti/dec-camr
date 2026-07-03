<?php

declare(strict_types=1);

namespace App\Support\Analytics;

enum DemandSeriesGrain: string
{
    case Hourly = 'hourly';
    case FifteenMinute = 'fifteen-minute';

    public function minutes(): int
    {
        return match ($this) {
            self::Hourly => 60,
            self::FifteenMinute => 15,
        };
    }

    public function reportWindowEndOffsetMinutes(): int
    {
        return match ($this) {
            self::Hourly => 55,
            self::FifteenMinute => 14,
        };
    }
}
