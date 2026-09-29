<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
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
        });

        if (in_array(DB::connection()->getDriverName(), ['mysql', 'mariadb'], true)) {
            DB::statement('CREATE INDEX user_access_group_user_idx_site_idx_index ON user_access_group (user_idx(191), site_idx)');
        } else {
            Schema::table('user_access_group', function (Blueprint $table): void {
                $table->index(['user_idx', 'site_idx']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('user_access_group');
    }
};
