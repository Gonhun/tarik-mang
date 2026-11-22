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
        Schema::create('asset_brands', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_type_id')
                  ->constrained('asset_types') // Merujuk ke tabel 'asset_types'
                  ->onDelete('cascade');      // Jika tipe aset dihapus, merek yang terkait juga akan dihapus
                                              // Pertimbangkan onDelete('restrict') jika Anda tidak ingin merek terhapus otomatis
            $table->string('code'); // Kolom untuk kode merek aset, dibuat unik
                                              // Pertimbangkan jika 'code' harus unik per asset_type_id atau unik secara global
                                              // Jika unik per asset_type_id: $table->string('code'); lalu $table->unique(['asset_type_id', 'code']);
            $table->string('name');        
            $table->timestamps();
            $table->unique(['asset_type_id', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_brands');
    }
};
