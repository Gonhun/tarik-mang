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
        Schema::create('jobsites', function (Blueprint $table) {
            $table->id(); // Primary key (bigIncrements)
            $table->string('name')->unique(); // Nama jobsite
            $table->string('code')->unique(); // Kode unik untuk jobsite (opsional)
            $table->text('location')->nullable(); // Lokasi atau alamat jobsite (opsional)
            $table->timestamps(); // Kolom created_at dan updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('jobsites');
    }
};
