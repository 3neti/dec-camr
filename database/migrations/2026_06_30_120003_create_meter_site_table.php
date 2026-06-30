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
        Schema::create('meter_site', function (Blueprint $table): void {
            $table->id('site_id');
            $table->unsignedBigInteger('division_idx');
            $table->unsignedBigInteger('company_idx');
            $table->unsignedBigInteger('building_idx')->nullable()->default(0);
            $table->string('site_code', 100)->nullable();
            $table->string('building_description')->nullable();
            $table->unsignedBigInteger('created_by_user_idx');
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('modified_by_user_idx')->nullable();
            $table->timestamp('updated_at')->nullable();
            $table->dateTime('last_log_update')->nullable();
            $table->string('deleted_at', 50)->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_site');
    }
};
