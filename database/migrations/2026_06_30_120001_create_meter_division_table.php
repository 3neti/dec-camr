<?php

declare(strict_types=1);

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
        Schema::create('meter_division_table', function (Blueprint $table): void {
            $table->id('division_id');
            $table->string('division_code');
            $table->string('division_name');
            $table->integer('created_by_user_idx');
            $table->timestamp('created_at')->nullable();
            $table->integer('modified_by_user_idx')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_division_table');
    }
};
