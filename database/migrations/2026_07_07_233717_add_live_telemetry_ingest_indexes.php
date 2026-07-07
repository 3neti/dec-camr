<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const METER_DATA_IDENTITY_INDEX = 'meter_data_live_identity_unique';

    private const METER_RTU_LOOKUP_INDEX = 'meter_rtu_live_lookup_index';

    private const METER_DETAILS_LOOKUP_INDEX = 'meter_details_live_lookup_index';

    private const METER_SITE_LOOKUP_INDEX = 'meter_site_live_lookup_index';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement('CREATE UNIQUE INDEX '.self::METER_DATA_IDENTITY_INDEX.' ON meter_data (location, meter_id, datetime, mac_addr(191))');
        } else {
            DB::statement('CREATE UNIQUE INDEX '.self::METER_DATA_IDENTITY_INDEX.' ON meter_data (location, meter_id, datetime, mac_addr)');
        }

        DB::statement('CREATE INDEX '.self::METER_RTU_LOOKUP_INDEX.' ON meter_rtu (site_code, gateway_mac)');
        DB::statement('CREATE INDEX '.self::METER_DETAILS_LOOKUP_INDEX.' ON meter_details (site_code, meter_name)');
        DB::statement('CREATE INDEX '.self::METER_SITE_LOOKUP_INDEX.' ON meter_site (site_code)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('meter_site', self::METER_SITE_LOOKUP_INDEX);
        $this->dropIndexIfExists('meter_details', self::METER_DETAILS_LOOKUP_INDEX);
        $this->dropIndexIfExists('meter_rtu', self::METER_RTU_LOOKUP_INDEX);
        $this->dropIndexIfExists('meter_data', self::METER_DATA_IDENTITY_INDEX);
    }

    private function dropIndexIfExists(string $table, string $index): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement(sprintf('DROP INDEX %s ON %s', $index, $table));

            return;
        }

        DB::statement(sprintf('DROP INDEX %s', $index));
    }
};
