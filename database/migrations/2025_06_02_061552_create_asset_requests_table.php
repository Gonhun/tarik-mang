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
        Schema::create('asset_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number')->unique();
            $table->foreignId('requester_user_id')->constrained('users');
            $table->foreignId('jobsite_id')->constrained('jobsites');
            $table->date('request_date');
            $table->date('needed_date')->nullable();
            $table->text('purpose');
            $table->text('remarks')->nullable();
            $table->string('status')->default('Draft'); // cth: Draft, Pending Approval, Approved, Rejected
            $table->text('final_rejection_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_requests');
    }
};
