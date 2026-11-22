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
        Schema::create('master_forecasts', function (Blueprint $table) {
            $table->id();
            $table->string('material_code', 50);
            $table->string('material_name', 200);
            $table->decimal('max_lt', 18, 2)->nullable();
            $table->decimal('avg_lt', 18, 2)->nullable();
            $table->decimal('periode', 18, 2)->nullable();
            $table->decimal('soh', 18, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_forecasts');
    }
};
