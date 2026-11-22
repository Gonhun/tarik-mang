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
        Schema::create('position_asset_standards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('position_id')->constrained('positions')->onDelete('cascade'); // Jabatan yang memiliki standar ini

            $table->foreignId('asset_type_id')->constrained('asset_types')->onDelete('no action'); // Tipe aset standar
            $table->foreignId('asset_brand_id')->nullable()->constrained('asset_brands')->onDelete('no action'); // Merek standar (opsional)
            $table->foreignId('asset_series_id')->nullable()->constrained('asset_series')->onDelete('no action'); // Seri standar (opsional)

            $table->unsignedInteger('quantity')->default(1); // Jumlah standar untuk item ini (misal 2 monitor)
            $table->text('specifications_notes')->nullable(); // Catatan spesifikasi tambahan untuk standar ini (mis: "Min. RAM 16GB")

            $table->timestamps();

            // Opsional: Unique constraint untuk mencegah duplikasi standar item yang sama persis untuk satu jabatan
            $table->unique(['position_id', 'asset_type_id', 'asset_brand_id', 'asset_series_id'], 'pos_std_asset_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('position_asset_standards');
    }
};
