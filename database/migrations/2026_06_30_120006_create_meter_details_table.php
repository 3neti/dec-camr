<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('meter_details', function (Blueprint $table): void {
            $table->integer('meter_id')->key()->autoIncrement();
            $table->integer('site_idx');
            $table->integer('rtu_idx');
            $table->integer('location_idx');
            $table->integer('building_idx')->nullable()->default(0);
            $table->integer('config_idx');
            $table->string('site_code', 100);
            $table->string('meter_name', 255);
            $table->integer('meter_name_addressable')->default(1);
            $table->string('meter_load_profile', 50)->default('NO');
            $table->string('meter_default_name', 255);
            $table->string('meter_type', 255)->nullable();
            $table->string('meter_brand', 255)->nullable();
            $table->string('meter_role', 100)->default('Client Meter');
            $table->string('meter_remarks', 255)->nullable();
            $table->string('customer_name', 255)->nullable();
            $table->double('meter_multiplier')->default(1);
            $table->string('meter_status', 50);
            $table->string('last_log_update', 30)->default('0000-00-00 00:00:00');
            $table->string('soft_rev', 50)->nullable()->default('0');
            $table->timestamp('created_at')->nullable()->default(null);
            $table->integer('created_by_user_idx');
            $table->timestamp('updated_at')->nullable()->default(null);
            $table->integer('modified_by_user_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_details');
    }
};
