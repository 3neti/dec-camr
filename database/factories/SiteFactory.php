<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Division;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Site>
 */
class SiteFactory extends Factory
{
    protected $model = Site::class;

    public function definition(): array
    {
        return [
            'division_idx' => Division::factory(),
            'company_idx' => Company::factory(),
            'building_idx' => 0,
            'site_code' => fake()->bothify('SITE-###'),
            'building_description' => fake()->unique()->words(2, true),
            'created_by_user_idx' => User::factory(),
            'modified_by_user_idx' => null,
            'created_at' => now(),
            'updated_at' => now(),
            'last_log_update' => now(),
            'deleted_at' => null,
        ];
    }
}
