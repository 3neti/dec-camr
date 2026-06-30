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
        Schema::create('meter_rtu', function (Blueprint $table): void {
            $table->integer('rtu_id')->key()->autoIncrement();
            $table->integer('site_idx');
            $table->integer('location_idx')->nullable()->default(null);
            $table->string('site_code', 100)->nullable()->default(null);
            $table->string('gateway_sn', 255);
            $table->string('gateway_mac', 255);
            $table->string('gateway_ip', 255);
            $table->string('connection_type', 50);
            $table->string('ip_netmask', 255)->nullable()->default(null);
            $table->string('ip_gateway', 255)->nullable()->default(null);
            $table->string('rtu_server_ip', 50)->nullable()->default(null);
            $table->string('gateway_description', 255)->nullable();
            $table->integer('update_rtu')->nullable()->default(0);
            $table->integer('update_rtu_location')->nullable()->default(0);
            $table->integer('update_rtu_ssh')->nullable()->default(0);
            $table->integer('update_rtu_force_lp')->nullable()->default(0);
            $table->string('idf_number', 255)->nullable()->default(null);
            $table->string('switch_name', 255)->nullable()->default(null);
            $table->string('idf_port', 255)->nullable()->default(null);
            $table->timestamp('created_at')->nullable()->default(null);
            $table->integer('created_by_user_idx');
            $table->timestamp('updated_at')->nullable()->default(null);
            $table->integer('modified_by_user_idx')->nullable();
            $table->string('last_log_update', 20)->nullable()->default('0000-00-00 00:00:00');
            $table->string('soft_rev', 100)->nullable()->default('0');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_rtu');
    }
};
