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
        Schema::create('asset_request_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_request_id')->constrained('asset_requests')->onDelete('cascade');
            $table->foreignId('asset_type_id')->constrained('asset_types');
            $table->foreignId('asset_brand_id')->constrained('asset_brands');
            $table->foreignId('asset_series_id')->constrained('asset_series');
            $table->unsignedInteger('quantity')->default(1);
            $table->text('item_description')->nullable();

            // Kolom untuk keputusan IT Site per item
            $table->enum('fulfillment_type', ['NEW_PURCHASE', 'STOCK_LOCAL', 'STOCK_MUTATION'])->nullable();
            $table->foreignId('assigned_asset_id')->nullable()->constrained('assets')->onDelete('set null');
            $table->enum('it_approval_status', ['Pending', 'Approved', 'Rejected'])->default('Pending');
            $table->foreignId('it_approver_id')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('it_approval_at')->nullable();
            $table->text('it_approval_notes')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_request_items');
    }
};
