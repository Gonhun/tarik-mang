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
            $table->string('gr_number')->nullable()->after('po_number');
            $table->decimal('price', 15, 2)->nullable()->after('gr_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_request_items', function (Blueprint $table) {
            $table->dropColumn(['gr_number', 'price']);
        });
    }
};
