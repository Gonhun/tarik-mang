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
            $table->enum('qc_status', ['Pending', 'Passed', 'Failed'])
                  ->default('Pending')
                  ->after('po_number'); // Sesuaikan posisi 'after' jika perlu

            // Kolom untuk catatan dari tim procurement/QC
          
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_request_items', function (Blueprint $table) {
            $table->dropColumn(['qc_status', 'procurement_notes']);
        });
    }
};
