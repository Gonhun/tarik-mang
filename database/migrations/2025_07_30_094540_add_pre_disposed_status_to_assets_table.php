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
    protected string $oldConstraintName = 'CK_assets_status_v2'; // Ganti 'v2' jika versi Anda berbeda

    /**
     * Nama constraint baru yang akan dibuat.
     */
    protected string $newConstraintName = 'CK_assets_status_v3'; // Beri nama versi baru

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop constraint yang lama terlebih dahulu
        try {
            DB::statement("ALTER TABLE assets DROP CONSTRAINT {$this->oldConstraintName}");
        } catch (\Exception $e) {
            Log::warning("Gagal drop constraint lama {$this->oldConstraintName} (mungkin sudah tidak ada): " . $e->getMessage());
        }

        // Daftar nilai BARU yang diizinkan, termasuk status untuk pengembalian, mutasi, dan pre-disposal
        $allowed_statuses = [
            'Free', 'Used', 'Repair', 'Disposed', 'Missing', 'Borrowed',
            'Pending Inspection',
            'Pending Mutation',
            'In Transit',
            'Pre-Disposed' // <-- STATUS BARU DITAMBAHKAN
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
        $old_allowed_statuses = "N'Free', N'Used', N'Repair', N'Disposed', N'Missing', N'Borrowed', N'Pending Inspection', N'Pending Mutation', N'In Transit'";
        DB::statement("ALTER TABLE assets ADD CONSTRAINT {$this->oldConstraintName} CHECK (status IN ({$old_allowed_statuses}))");
    }
};