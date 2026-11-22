<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;   // <-- Import DB facade
use Illuminate\Support\Facades\Log;  // <-- Import Log facade

return new class extends Migration
{
    /**
     * Nama constraint lama. Ganti nama ini jika nama di database Anda berbeda.
     * Anda bisa menemukannya di error sebelumnya atau di tool database.
     */
    protected string $oldConstraintName = 'CK__asset_tra__statu__3BC0BB7A'; // Ganti 'v2' jika versi Anda berbeda

    /**
     * Nama constraint baru yang akan dibuat.
     */
    protected string $newConstraintName = 'CK__asset_tra__statu__V1'; // Beri nama versi baru

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop constraint yang lama terlebih dahulu
        try {
            DB::statement("ALTER TABLE asset_transactions DROP CONSTRAINT {$this->oldConstraintName}");
        } catch (\Exception $e) {
            Log::warning("Gagal drop constraint lama {$this->oldConstraintName} (mungkin sudah tidak ada): " . $e->getMessage());
        }

       
        $allowed_statuses = [
            'Cancelled',
            'Completed',
            'Pending Inspection',
            'Pending Staff IT',
            'Pending Cost Control',
            'Pending KASI IT',
            'Rejected',
            'Pending Release',
            'In Transit',
            'Pending Approval',
            'Draft',
            'Delivery'
        ];

        // Ubah array PHP menjadi string yang diformat untuk SQL Server
        $formatted_statuses = implode(', ', array_map(fn($val) => "N'{$val}'", $allowed_statuses));

        // Tambahkan constraint baru dengan daftar nilai yang sudah diperbarui
        DB::statement("ALTER TABLE asset_transactions ADD CONSTRAINT {$this->newConstraintName} CHECK (status IN ({$formatted_statuses}))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asset_transactions', function (Blueprint $table) {
            //
        });
    }
};
