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
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('entity_id')
                  ->nullable() // Buat nullable jika aset tidak selalu memiliki entitas, atau required jika wajib
                  ->after('asset_series_id') // Sesuaikan posisi 'after'
                  ->constrained('entities') // Merujuk ke tabel 'entities'
                  ->onDelete('no action'); // Atau 'set null' jika nullable dan entitas dihapus
            $table->dropColumn('entity_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['entity_id']);
            $table->dropColumn('entity_id');
        });
    }
};
