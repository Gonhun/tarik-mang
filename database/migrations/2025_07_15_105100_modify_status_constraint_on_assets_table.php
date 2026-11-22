<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;   // <-- Import DB facade
use Illuminate\Support\Facades\Log;  // <-- Import Log facade

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    protected string $oldConstraintName = 'CK__assets__status__46A85E41';

    /**
     * Nama constraint baru yang akan dibuat.
     */
    protected string $newConstraintName = 'CK_assets_status_v2'; // Beri nama versi baru

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop constraint yang lama terlebih dahulu
        try {
            // Cek apakah constraint ada sebelum mencoba drop
            $constraintExists = DB::selectOne("
                SELECT 1 FROM sys.check_constraints 
                WHERE name = ?
            ", [$this->oldConstraintName]);

            if ($constraintExists) {
                DB::statement("ALTER TABLE assets DROP CONSTRAINT {$this->oldConstraintName}");
            } else {
                Log::info("Constraint {$this->oldConstraintName} tidak ditemukan, skip drop.");
            }
        } catch (\Exception $e) {
            Log::warning("Gagal drop constraint lama {$this->oldConstraintName} (mungkin sudah tidak ada): " . $e->getMessage());
        }

        // Daftar nilai BARU yang diizinkan, termasuk status untuk pengembalian dan mutasi
        $allowed_statuses = [
            'Free', 'Used', 'Repair', 'Disposed', 'Missing', 'Borrowed',
            'Pending Inspection', // <-- STATUS BARU untuk pengembalian
            'Pending Mutation',   // <-- STATUS BARU untuk mutasi
            'In Transit',  
            'Pre-Used',    
               // <-- STATUS BARU untuk mutasi
        ];

        // Ubah array PHP menjadi string yang diformat untuk SQL Server
        $formatted_statuses = implode(', ', array_map(fn($val) => "N'{$val}'", $allowed_statuses));

        // Tambahkan constraint baru dengan daftar nilai yang sudah diperbarui
        DB::statement("ALTER TABLE assets ADD CONSTRAINT {$this->newConstraintName} CHECK (status IN ({$formatted_statuses}))");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Hapus constraint baru
        DB::statement("ALTER TABLE assets DROP CONSTRAINT {$this->newConstraintName}");

        // Tambahkan kembali constraint lama (tanpa status baru)
        $old_allowed_statuses = "N'Free', N'Used', N'Repair', N'Disposed', N'Missing', N'Borrowed'";
        DB::statement("ALTER TABLE assets ADD CONSTRAINT {$this->oldConstraintName} CHECK (status IN ({$old_allowed_statuses}))");
    }
};
