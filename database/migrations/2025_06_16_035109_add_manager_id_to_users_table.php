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
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('manager_id')
                  ->nullable() // Wajib nullable, karena atasan tertinggi (misal: Direktur Utama) tidak punya atasan
                  ->after('position_id') // Tempatkan setelah kolom 'position_id'. Sesuaikan jika perlu.
                  ->constrained('users')    // Membuat foreign key constraint ke kolom 'id' di tabel 'users'
                  ->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            //
        });
    }
};
