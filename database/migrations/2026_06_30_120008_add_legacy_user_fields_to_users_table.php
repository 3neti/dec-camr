<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('user_real_name')->nullable()->after('name');
            $table->string('user_job_title')->nullable()->after('user_real_name');
            $table->string('user_type')->nullable()->after('user_job_title');
            $table->string('user_access')->default('Selected')->after('user_type');
            $table->integer('created_by_user_idx')->nullable()->after('user_access');
            $table->integer('modified_by_user_idx')->nullable()->after('created_by_user_idx');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('user_real_name');
            $table->dropColumn('user_job_title');
            $table->dropColumn('user_type');
            $table->dropColumn('user_access');
            $table->dropColumn('created_by_user_idx');
            $table->dropColumn('modified_by_user_idx');
        });
    }
};
