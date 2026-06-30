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
        Schema::create('meter_data', function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('location', 30)->default('Home');
            $table->string('meter_id', 30);
            $table->dateTime('datetime');
            $table->double('vrms_a')->default(0);
            $table->double('vrms_b')->default(0);
            $table->double('vrms_c')->default(0);
            $table->double('irms_a')->default(0);
            $table->double('irms_b')->default(0);
            $table->double('irms_c')->default(0);
            $table->double('freq')->default(0);
            $table->double('pf')->default(0);
            $table->double('watt')->default(0);
            $table->double('va')->default(0);
            $table->double('var')->default(0);
            $table->double('wh_del')->default(0);
            $table->double('wh_rec')->default(0);
            $table->double('wh_net')->default(0);
            $table->double('wh_total')->default(0);
            $table->double('varh_neg')->default(0);
            $table->double('varh_pos')->default(0);
            $table->double('varh_net')->default(0);
            $table->double('varh_total')->default(0);
            $table->double('vah_total')->default(0);
            $table->double('max_rec_kw_dmd')->default(0);
            $table->dateTime('max_rec_kw_dmd_time')->nullable();
            $table->double('max_del_kw_dmd')->default(0);
            $table->dateTime('max_del_kw_dmd_time')->nullable();
            $table->double('max_pos_kvar_dmd')->default(0);
            $table->dateTime('max_pos_kvar_dmd_time')->nullable();
            $table->double('max_neg_kvar_dmd')->default(0);
            $table->dateTime('max_neg_kvar_dmd_time')->nullable();
            $table->double('v_ph_angle_a')->default(0);
            $table->double('v_ph_angle_b')->default(0);
            $table->double('v_ph_angle_c')->default(0);
            $table->double('i_ph_angle_a')->default(0);
            $table->double('i_ph_angle_b')->default(0);
            $table->double('i_ph_angle_c')->default(0);
            $table->text('mac_addr')->nullable();
            $table->text('soft_rev')->nullable();
            $table->integer('relay_status')->default(0);
            $table->timestamp('dt')->nullable();
            $table->integer('genset_status')->nullable();
            $table->timestamps();
            $table->index(['meter_id', 'datetime', 'location'], 'meter_data_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('meter_data');
    }
};
