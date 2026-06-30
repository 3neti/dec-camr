<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Building;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Building>
 */
class BuildingFactory extends Factory
{
    protected $model = Building::class;

    public function definition(): array
    {
        return [
            'site_idx' => Site::factory(),
            'building_code' => fake()->bothify('BLD-###'),
            'building_description' => fake()->unique()->words(2, true),
            'cut_off' => 25,
            'created_by_user_idx' => User::factory(),
            'modified_by_user_idx' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
