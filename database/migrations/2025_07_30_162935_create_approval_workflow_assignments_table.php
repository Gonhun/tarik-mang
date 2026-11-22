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
        Schema::create('approval_workflow_assignments', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_type')->comment('cth: REQUEST_ASSET, DISPOSAL, MUTATION, REPAIR');
        
            // Konteks: bisa berdasarkan salah satu atau kombinasi
            $table->foreignId('position_id')->nullable()->constrained('positions')->onDelete('cascade');
            $table->foreignId('jobsite_id')->nullable()->constrained('jobsites')->onDelete('cascade');
            // Anda bisa tambah department_id, dll. di sini jika perlu
        
            $table->foreignId('approval_workflow_id')->constrained('approval_workflows')->onDelete('cascade');
        
            $table->integer('priority')->default(0)->comment('Aturan yang lebih spesifik (priority lebih tinggi) akan dipilih dahulu');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('approval_workflow_assignments');
    }
};
