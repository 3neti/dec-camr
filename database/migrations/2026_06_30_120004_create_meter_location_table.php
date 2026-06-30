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
        Schema::create('meter_location_table', function (Blueprint $table): void {
            $table->integer('location_id')->key()->autoIncrement();
            $table->integer('site_idx');
            $table->integer('building_id')->nullable()->default(0);
            $table->string('location_code', 255);
            $table->string('location_description', 255);
            $table->timestamp('created_at')->nullable()->default(null);
            $table->integer('created_by_user_idx')->nullable()->default(null);
            $table->timestamp('updated_at')->nullable()->default(null);
            $table->integer('modified_by_user_idx')->nullable()->default(null);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_location_table');
    }
};
