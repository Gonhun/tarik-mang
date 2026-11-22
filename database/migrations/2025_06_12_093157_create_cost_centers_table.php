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
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->id();
            $table->string('cost_center_number')->unique()->comment('Nomor atau Kode Cost Center');
            $table->foreignId('jobsite_id')->constrained('jobsites')->onDelete('no action');
            $table->foreignId('department_id')->constrained('departments')->onDelete('no action');
            $table->text('description')->nullable()->comment('Keterangan atau deskripsi tambahan');

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cost_centers');
    }
};
