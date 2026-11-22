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
        Schema::create('master_pivots', function (Blueprint $table) {
            $table->id();
            $table->string('cost_element_number')->unique()->comment('Nomor atau Kode Cost Element');
            $table->string('description', 2000)->nullable()->comment('Keterangan atau deskripsi tambahan');
            $table->foreignId('department_id')->constrained('departments')->onDelete('no action');
            $table->foreignId('jobsite_id')->constrained('jobsites')->onDelete('no action');
            $table->string('year', 4)->comment('Tahun Anggaran');
            $table->integer('plan_jan')->nullable();
            $table->integer('act_jan')->nullable();
            $table->integer('plan_feb')->nullable();
            $table->integer('act_feb')->nullable();
            $table->integer('plan_mar')->nullable();
            $table->integer('act_mar')->nullable();
            $table->integer('plan_apr')->nullable();
            $table->integer('act_apr')->nullable();
            $table->integer('plan_may')->nullable();
            $table->integer('act_may')->nullable();
            $table->integer('plan_jun')->nullable();
            $table->integer('act_jun')->nullable();
            $table->integer('plan_jul')->nullable();
            $table->integer('act_jul')->nullable();
            $table->integer('plan_aug')->nullable();
            $table->integer('act_aug')->nullable();
            $table->integer('plan_sep')->nullable();
            $table->integer('act_sep')->nullable();
            $table->integer('plan_oct')->nullable();
            $table->integer('act_oct')->nullable();
            $table->integer('plan_nov')->nullable();
            $table->integer('act_nov')->nullable();
            $table->integer('plan_dec')->nullable();
            $table->integer('act_dec')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_pivots');
    }
};
