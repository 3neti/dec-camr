<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_access_group', function (Blueprint $table): void {
            $table->id('user_access_id');
            $table->text('user_idx');
            $table->text('user_name')->nullable();
            $table->date('user_expiration')->nullable();
            $table->unsignedBigInteger('site_idx');
            $table->timestamps();
            $table->unsignedBigInteger('created_by_user_idx')->nullable();
            $table->unsignedBigInteger('updated_by_user_idx')->nullable();
            $table->string('access_list_src')->nullable();

            $table->index('site_idx');
            $table->index(['user_idx', 'site_idx']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_access_group');
    }
};
