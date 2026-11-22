<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_damage_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained('assets')->onDelete('cascade'); // Aset yang rusak

            $table->foreignId('reported_by_user_id')->nullable() // User yang melaporkan kerusakan
                  ->constrained('users')->onDelete('no action');
            $table->date('report_date');             // Tanggal kerusakan dilaporkan/terjadi
            $table->text('damage_description');      // Deskripsi detail kerusakan

            $table->enum('damage_status', [          // Status penanganan kerusakan
                'Reported',             // Baru Dilaporkan
                'PendingAssessment',    // Menunggu Penilaian/Inspeksi
                'AssessmentComplete',   // Penilaian Selesai
                'UnderRepair',          // Dalam Perbaikan
                'RepairComplete',       // Perbaikan Selesai (aset mungkin kembali 'Free' atau 'Used')
                'AwaitingParts',        // Menunggu Sparepart
                'BeyondEconomicRepair', // Rusak Berat (Tidak Ekonomis Diperbaiki -> bisa jadi input untuk status aset 'Disposed')
                'Closed',               // Kasus ditutup (misalnya, setelah perbaikan atau pemusnahan)
                'Cancelled'             // Laporan dibatalkan
            ])->default('Reported');

            $table->text('assessment_notes')->nullable(); // Catatan hasil penilaian/inspeksi
            $table->text('repair_notes')->nullable();     // Catatan perbaikan
            $table->decimal('repair_cost', 15, 2)->nullable(); // Biaya perbaikan
            $table->date('repaired_date')->nullable();    // Tanggal selesai perbaikan

            // Kolom untuk bukti foto kerusakan/dokumen pendukung
            $table->string('photo1_path')->nullable();
            $table->string('photo2_path')->nullable(); // Bisa tambah lebih banyak jika perlu
            $table->string('other_document_path')->nullable();
            $table->string('other_document_name')->nullable();

            $table->foreignId('created_by_user_id')->nullable() // User yang membuat record laporan ini
                  ->constrained('users')->onDelete('no action');
            $table->foreignId('updated_by_user_id')->nullable() // User yang terakhir update record ini
                  ->constrained('users')->onDelete('no action');

            $table->timestamps();
            $table->softDeletes(); // Jika Anda ingin soft delete untuk laporan kerusakan
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_damage_reports');
    }
};