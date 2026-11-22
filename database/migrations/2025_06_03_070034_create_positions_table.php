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
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('department_id')->nullable() 
                  ->constrained('departments')
                  ->onDelete('no action'); // Jika departemen dihapus, jabatan ini department_id nya jadi NULL (atau bisa 'restrict')
            $table->string('name'); // Nama Jabatan (mis: Staff IT, Manager Keuangan)
            $table->timestamps();
            $table->softDeletes();
            $table->unique(['department_id', 'name', 'deleted_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('positions');
    }
};
