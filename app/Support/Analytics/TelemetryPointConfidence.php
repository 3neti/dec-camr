<?php

declare(strict_types=1);

namespace App\Support\Analytics;

enum TelemetryPointConfidence: string
{
    case Measured = 'Measured';
    case Calculated = 'Calculated';
    case Estimated = 'Estimated';
    case Incomplete = 'Incomplete';
    case Unknown = 'Unknown';
}
