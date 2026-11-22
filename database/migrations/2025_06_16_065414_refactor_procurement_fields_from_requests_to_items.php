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
        Schema::table('asset_request_items', function (Blueprint $table) {
            $table->string('pr_number')->nullable()->after('quantity');
            $table->string('accounting_number')->nullable()->after('pr_number');
            $table->text('procurement_notes')->nullable()->after('accounting_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        
    }
};
