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
        Schema::create('master_parts', function (Blueprint $table) {
            $table->id();
            $table->string('part_number')->unique();
            $table->string('material_number')->unique();
            $table->string('name');
            $table->string('unit')->nullable();
            $table->decimal('default_price', 10, 2)->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_parts');
    }
};
