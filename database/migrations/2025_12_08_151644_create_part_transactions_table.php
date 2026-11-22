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
        Schema::create('part_transactions', function (Blueprint $table) {
            $table->id();
            // Foreign Key ke Master Part
            $table->foreignId('master_part_id')->constrained('master_parts'); 
            // Foreign Key ke Jobsite/Lokasi
            $table->foreignId('jobsite_id')->constrained('jobsites');
            
            $table->enum('type', ['IN', 'OUT']); // Tipe Transaksi: MASUK atau KELUAR
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users'); // Mencatat siapa yang melakukan transaksi
            $table->timestamp('transaction_date')->useCurrent();
            $table->timestamps();
            // Indeks unik untuk Part ID dan Lokasi ID (untuk efisiensi query)
            $table->index(['master_part_id', 'jobsite_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('part_transactions');
    }
};
