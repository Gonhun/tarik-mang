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
        Schema::table('tickets', function (Blueprint $table) {
            $table->foreignId('resolved_by')
                ->nullable()
                ->after('assigned_at')
                ->constrained('users');

            // resolved_at → kapan tiket dinyatakan selesai
            $table->timestamp('resolved_at')
                ->nullable()
                ->after('resolved_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tickets', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->dropColumn(['resolved_by', 'resolved_at']);
        });
    }
};
