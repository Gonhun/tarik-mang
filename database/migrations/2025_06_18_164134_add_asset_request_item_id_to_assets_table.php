<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    protected string $indexName = 'assets_asset_request_item_id_unique_filtered';
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('asset_request_item_id')
                  ->nullable()
                  ->after('id')
                  ->constrained('asset_request_items')
                  ->onDelete('set null'); // Jika item pengajuan dihapus, link-nya di aset jadi NULL
        });
        DB::statement("CREATE UNIQUE NONCLUSTERED INDEX {$this->indexName} ON assets(asset_request_item_id) WHERE asset_request_item_id IS NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            //
        });
    }
};
