<?php

namespace Database\Factories;

use App\Models\ConfigurationFile;
use App\Models\Gateway;
use App\Models\Meter;
use App\Models\MeterLocation;
use App\Models\Site;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Meter>
 */
class MeterFactory extends Factory
{
    protected $model = Meter::class;

    public function definition(): array
    {
        return [
            'site_idx' => Site::factory(),
            'site_code' => 'SITE'.fake()->numberBetween(100, 999),
            'rtu_idx' => Gateway::factory(),
            'location_idx' => MeterLocation::factory(),
            'building_idx' => 0,
            'config_idx' => ConfigurationFile::factory(),
            'meter_name' => fake()->bothify('MTR-###'),
            'meter_name_addressable' => 1,
            'meter_load_profile' => 'NO',
            'meter_default_name' => fake()->bothify('##'),
            'meter_type' => fake()->randomElement(['Power', 'Energy']),
            'meter_brand' => fake()->randomElement(['Schneider', 'Siemens']),
            'meter_role' => 'Client Meter',
            'meter_remarks' => fake()->optional()->sentence(),
            'customer_name' => fake()->optional()->company(),
            'meter_multiplier' => fake()->randomFloat(2, 1, 10),
            'meter_status' => fake()->randomElement(['ACTIVE', 'INACTIVE']),
            'last_log_update' => '0000-00-00 00:00:00',
            'soft_rev' => 0,
            'created_by_user_idx' => User::factory(),
            'modified_by_user_idx' => User::factory(),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }
}
