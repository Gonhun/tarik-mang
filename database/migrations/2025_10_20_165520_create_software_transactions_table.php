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
        Schema::create('software_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('software_id')->nullable()->constrained('software_licenses')->onDelete('set null');

            $table->foreignId('user_id')->nullable()->constrained('users');

            $table->foreignId('asset_id')->nullable()->constrained('assets');

            $table->enum('transaction_type', ['assign','return','maintenance','purchase','license_update'])->default('assign');

            $table->dateTime('assigned_at')->nullable();

            $table->dateTime('returned_at')->nullable();

            $table->longText('note')->nullable();

            $table->foreignId('created_by')->constrained('users');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_transactions');
    }
};
