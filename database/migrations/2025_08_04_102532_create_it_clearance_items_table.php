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
        Schema::create('it_clearance_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('it_clearance_id')->constrained('it_clearances')->onDelete('cascade');
            $table->string('item_description');
            $table->enum('status', ['V', 'X', 'N/A'])->default('N/A');
            $table->string('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_clearance_items');
    }
};
