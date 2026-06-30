<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meter_company_table', function (Blueprint $table): void {
            $table->id('company_id');
            $table->string('company_code')->nullable();
            $table->string('company_name')->unique();
            $table->unsignedBigInteger('created_by_user_idx')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->unsignedBigInteger('modified_by_user_idx')->nullable();
            $table->timestamp('updated_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meter_company_table');
    }
};
