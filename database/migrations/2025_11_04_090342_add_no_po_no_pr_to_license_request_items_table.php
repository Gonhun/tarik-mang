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
            $table->string('no_pr')->nullable()->after('id'); // atau ubah posisi sesuai kebutuhan
            $table->string('no_po')->nullable()->after('no_pr');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_request_items', function (Blueprint $table) {
            $table->dropColumn(['no_pr', 'no_po']);
        });
    }
};
