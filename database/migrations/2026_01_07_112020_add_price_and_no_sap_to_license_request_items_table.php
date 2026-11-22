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
        Schema::table('license_request_items', function (Blueprint $table) {
            $table->decimal('price', 18, 2)->nullable()->after('quantity');
            $table->string('no_sap', 50)->nullable()->after('price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_request_items', function (Blueprint $table) {
            $table->dropColumn(['price', 'no_sap']);
        });
    }
};
