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
            $table->foreignId('software_manufacture_id')
                  ->nullable()
                  ->after('software_category_id')
                  ->constrained('software_manufacturer')
                  ->onDelete('no action');            

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('license_request_items', function (Blueprint $table) {
            $table->dropForeign(['software_manufacture_id']);
            $table->dropColumn('software_manufacture_id');
        });
    }
};
