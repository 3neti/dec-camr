<?php

namespace Database\Factories;

use App\Models\MeterLocation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MeterLocation>
 */
class MeterLocationFactory extends Factory
{
    protected $model = MeterLocation::class;

    public function definition(): array
    {
        return [
            'site_idx' => Site::factory(),
            'building_id' => 0,
            'location_code' => fake()->bothify('ER-###'),
            'location_description' => fake()->city(),
            'created_by_user_idx' => User::factory(),
            'modified_by_user_idx' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
