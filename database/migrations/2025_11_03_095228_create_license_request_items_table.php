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
        Schema::create('license_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_request_id')->constrained('license_requests')->onDelete('cascade');
            $table->foreignId('software_category_id')->constrained('software_category')->onDelete('cascade');
            $table->string('software_name');
            $table->integer('quantity')->default(1);
            $table->text('remarks')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('approved_by')->nullable()->constrained('users')->onDelete('no action');
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_request_items');
    }
};
