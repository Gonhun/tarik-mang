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
        Schema::table('asset_requests', function (Blueprint $table) {
            $table->dropColumn('ba_damage');
            $table->dropColumn('ba_budget_outside');
            $table->string('ba_damage_path')->nullable();
            $table->string('ba_budget_outside_path')->nullable();
            $table->string('ba_damage_name')->nullable();
            $table->string('ba_budget_outside_name')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_requests', function (Blueprint $table) {
            $table->dropColumn('ba_damage_path');
            $table->dropColumn('ba_budget_outside_path');
        });
    }
};
