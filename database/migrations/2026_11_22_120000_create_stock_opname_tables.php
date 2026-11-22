<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock opname aset dengan scan QR.
 * Hanya menambah 2 tabel baru; tidak mengubah tabel lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_opnames', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();               // mis. SO/JKT/2611/0001
            $table->foreignId('jobsite_id')->constrained('jobsites');
            $table->string('title')->nullable();
            $table->string('status', 20)->default('open');  // open | closed
            $table->unsignedInteger('expected_count')->default(0); // jumlah aset tercatat di site saat sesi dibuat/ditutup
            $table->text('notes')->nullable();
            $table->foreignId('started_by')->constrained('users');
            $table->timestamp('started_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users');
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();

            $table->index(['jobsite_id', 'status']);
        });

        Schema::create('stock_opname_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('stock_opname_id')->constrained('stock_opnames')->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained('assets');
            $table->string('scanned_code');                 // isi QR / nomor yang diketik
            // found | other_site | not_registered
            $table->string('result', 20);
            $table->string('condition', 20)->nullable();    // baik | rusak
            $table->string('location_note')->nullable();
            $table->string('asset_status_at_scan')->nullable();
            $table->foreignId('asset_jobsite_id_at_scan')->nullable()->constrained('jobsites');
            $table->foreignId('scanned_by')->constrained('users');
            $table->timestamp('scanned_at')->nullable();
            $table->timestamps();

            $table->index(['stock_opname_id', 'result']);
            $table->index(['stock_opname_id', 'asset_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_opname_items');
        Schema::dropIfExists('stock_opnames');
    }
};
