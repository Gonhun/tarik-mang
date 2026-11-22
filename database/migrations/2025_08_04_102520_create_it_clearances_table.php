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
        Schema::create('it_clearances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users'); // Karyawan yang di-clearance
            $table->date('resign_date');
            $table->date('check_date');
            $table->text('additional_notes')->nullable();
            
            $table->foreignId('checked_by_user_id')->nullable()->constrained('users'); // Staff IT
            $table->foreignId('known_by_user_id')->nullable()->constrained('users');   // Kasi IT
            $table->foreignId('approved_by_user_id')->nullable()->constrained('users'); // Kabag IC
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('it_clearances');
    }
};
