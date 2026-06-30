<?php

namespace Tests\Feature\LegacyCharacterizationReference;

use Illuminate\Support\Facades\Schema;

class LegacyBrowserSeedTest extends LegacyCharacterizationTestCase
{
    public function test_browser_characterization_database_is_seeded(): void
    {
        $this->assertDatabaseHas('user_tb', ['user_name' => 'admin']);
        $this->assertDatabaseHas('meter_site', ['site_code' => 'SITEA']);
        $this->assertDatabaseHas('meter_site', ['site_code' => 'SITEB']);
        $this->assertTrue(Schema::hasTable('sessions'));
    }
}
