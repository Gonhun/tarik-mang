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
        Schema::create('software_licenses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('category_id')->constrained('software_category')->onDelete('cascade');
            $table->integer('seats');
            $table->text('product_key');
            $table->foreignId('manufacture_id')->constrained('software_manufacturer')->onDelete('cascade');
            $table->string('order_number')->nullable();
            $table->decimal('purchase_cost', 10, 2)->default(0);
            $table->date('purchase_date');
            $table->date('expiration_date')->nullable();
            $table->string('po_number')->nullable();
            $table->text('note')->nullable();
            $table->foreignId('create_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('software_licenses');
    }
};
