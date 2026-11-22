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
        Schema::table('asset_brands', function (Blueprint $table) {
            Schema::table('asset_brands', function (Blueprint $table) {
                // Tambahkan kolom untuk menyimpan path ke file foto
                // Ditempatkan setelah kolom 'name' atau sesuaikan posisinya
                $table->string('photo_path')->nullable()->after('name');
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_brands', function (Blueprint $table) {
            //
        });
    }
};
