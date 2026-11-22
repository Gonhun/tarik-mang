<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('software_licenses_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('license_id')->constrained('software_licenses')->onDelete('cascade');
            $table->text('product_key');
            $table->date('expiration_date')->nullable();
            $table->enum('status', ['Available', 'Used', 'Expired', 'Revoked'])->default('Available');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_licenses_detail.phps');
    }
};
