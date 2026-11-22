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
        Schema::table('assets', function (Blueprint $table) {
            $table->unsignedBigInteger('asset_transaction_id')
            ->nullable()
            ->after('holder_user_id');

        // Kalau mau ada foreign key ke asset_transactions
        $table->foreign('asset_transaction_id')
                ->references('id')
                ->on('asset_transactions')
                ->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            Schema::table('assets', function (Blueprint $table) {
                $table->dropForeign(['asset_transaction_id']);
                $table->dropColumn('asset_transaction_id');
            });
        });
    }
};
