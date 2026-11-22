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
        Schema::table('license_request_approvals', function (Blueprint $table) {
            $table->unsignedInteger('step_order')->default(1)->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_request_approvals', function (Blueprint $table) {
            $table->dropColumn('step_order');
        });
    }
};
