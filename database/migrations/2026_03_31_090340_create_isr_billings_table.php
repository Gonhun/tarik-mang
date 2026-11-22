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
        Schema::create('isr_billings', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_id', 50)->index();
            $table->string('invoice_number', 50);
            $table->date('publish_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('amount', 15, 2)->default(0);
            $table->string('invoice_type', 255)->nullable();
            $table->string('invoice_status', 255)->nullable();
            $table->string('invoice_desc', 255)->nullable();
            $table->dateTime('invoice_last_update')->nullable();
            $table->string('billing_name', 255)->nullable();
            $table->string('billing_number', 255)->nullable();
            $table->string('client_name', 255)->nullable();
            $table->string('station_name', 500)->nullable();
            $table->string('station_address', 500)->nullable();
            $table->string('tx', 50)->nullable();
            $table->string('rx', 50)->nullable();
            $table->string('jobsite_code',50)->nullable();
            $table->string('link_invoice', 500)->nullable();
            $table->string('link_isr', 500)->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('isr_billings');
    }
};
