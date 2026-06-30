<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meter_configuration_file', function (Blueprint $table): void {
            $table->integer('config_id')->autoIncrement();
            $table->text('meter_model');
            $table->text('config_file');
            $table->integer('created_by_user_idx');
            $table->timestamp('created_at')->nullable()->default(null);
            $table->integer('modified_by_user_idx')->nullable();
            $table->timestamp('updated_at')->nullable()->default(null);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_configuration_file');
    }
};
