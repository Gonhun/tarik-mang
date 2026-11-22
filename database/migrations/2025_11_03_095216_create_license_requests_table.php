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
        Schema::create('license_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no')->unique();
            $table->foreignId('user_id')->constrained('users')->onDelete('no action');
            $table->text('purpose');
            $table->enum('status', ['draft', 'submitted', 'in_progress', 'approved', 'rejected', 'cancelled'])->default('draft');
            $table->text('remarks')->nullable();
            $table->foreignId('approved_by')->constrained('users')->onDelete('cascade');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('license_requests');
    }
};
