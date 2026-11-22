<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB; // <-- Import DB facade
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    protected string $indexName = 'asset_series_material_code_unique_filtered';
    public function up(): void
    {
        Schema::create('asset_series', function (Blueprint $table) {
            $table->id(); // Primary key

            // Foreign key ke tabel lain
            $table->foreignId('asset_type_id')->constrained('asset_types')->onDelete('no action');
            $table->foreignId('asset_brand_id')->constrained('asset_brands')->onDelete('cascade');

            $table->string('name'); // Nama atau model seri aset
            $table->text('specifications')->nullable(); // Spesifikasi dibuat nullable
            
            // Kolom BARU untuk Kode Material (sebagai string)
            $table->string('material_code')->nullable(); // Dibuat nullable

            $table->timestamps();
            $table->softDeletes(); // Opsional, tapi praktik yang baik

            // Composite unique key: nama seri harus unik dalam lingkup merek yang sama.
            $table->unique(['asset_brand_id', 'name'], 'asset_series_brand_name_unique');
        });

        // Buat filtered unique index untuk `material_code` secara terpisah
        // Ini memastikan keunikan HANYA untuk nilai yang TIDAK NULL, mengizinkan banyak NULL
        DB::statement("CREATE UNIQUE NONCLUSTERED INDEX {$this->indexName} ON asset_series(material_code) WHERE material_code IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_series');
    }
};
