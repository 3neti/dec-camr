<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Division;
use App\Models\User;
use Database\Seeders\Profiles\DemoProfileSeeder;
use Database\Seeders\Profiles\HeavyProfileSeeder;
use Database\Seeders\Profiles\MinimalProfileSeeder;

final class SeedProfileManager
{
    public const PROFILE_MINIMAL = 'minimal';

    public const PROFILE_DEMO = 'demo';

    public const PROFILE_HEAVY = 'heavy';

    public function seed(string $profile, User $admin, Division $division, Company $company): void
    {
        $normalizedProfile = strtolower(trim($profile));

        match ($normalizedProfile) {
            self::PROFILE_MINIMAL => (new MinimalProfileSeeder)->seed($admin, $division, $company),
            self::PROFILE_DEMO => (new DemoProfileSeeder)->seed($admin, $division, $company),
            self::PROFILE_HEAVY => (new HeavyProfileSeeder)->seed($admin, $division, $company),
            default => (new DemoProfileSeeder)->seed($admin, $division, $company),
        };
    }

    /**
     * @return array<int, string>
     */
    public function supportedProfiles(): array
    {
        return [
            self::PROFILE_MINIMAL,
            self::PROFILE_DEMO,
            self::PROFILE_HEAVY,
        ];
    }
}
