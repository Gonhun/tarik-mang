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
        Schema::create('asset_transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_transaction_id')->constrained('asset_transactions')->onDelete('cascade');
            $table->foreignId('asset_id')->constrained('assets')->onDelete('no action');
            $table->string('condition_notes')->nullable()->comment('Kondisi aset saat transaksi'); // Lebih generik
            $table->json('metadata')->nullable()->comment('Data spesifik item per jenis transaksi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_transaction_items');
    }
};
