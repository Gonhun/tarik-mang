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
        Schema::create('loginwa_sessions', function (Blueprint $table) {
            $table->id();
            $table->text('token'); // Kolom TEXT untuk menyimpan token JWT yang panjang
            $table->timestamp('expires_at'); // Tanggal dan waktu kedaluwarsa token
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('loginwa_sessions');
    }
};
