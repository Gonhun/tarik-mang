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
        Schema::create('asset_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_request_id')->constrained('asset_requests')->onDelete('cascade');
            $table->unsignedInteger('step_order');
            $table->string('approver_role_name');
            $table->foreignId('approver_user_id')->nullable()->constrained('users');
            $table->enum('status', ['Pending', 'Approved', 'Rejected', 'Bypassed'])->default('Pending');
            $table->foreignId('action_by_user_id')->nullable()->constrained('users');
            $table->timestamp('action_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->unique(['asset_request_id', 'step_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_request_approvals');
    }
};
