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
            $table->string('no_accounting')->nullable()->after('no_po');
            $table->decimal('price', 15, 2)->nullable()->after('no_accounting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_request_items', function (Blueprint $table) {
            $table->dropColumn(['no_accounting', 'price']);
        });
    }
};
