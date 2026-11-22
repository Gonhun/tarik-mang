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
        Schema::create('assets', function (Blueprint $table) {
            $table->id();

            $table->string('asset_number')->unique(); // Nomor Aset utama, harus unik
            $table->string('old_asset_number')->nullable()->unique(); // Nomor Aset Lama, boleh kosong, tapi jika diisi harus unik
            $table->string('pr_number')->nullable(); // Purchase Request Number
            $table->string('po_number')->nullable(); // Purchase Order Number
            $table->string('sap_number')->nullable()->unique(); // Nomor SAP, boleh kosong, tapi jika diisi harus unik

            $table->foreignId('asset_type_id')->constrained('asset_types')->onDelete('no action');
            $table->foreignId('asset_brand_id')->constrained('asset_brands')->onDelete('no action');
            $table->foreignId('asset_series_id')->constrained('asset_series')->onDelete('no action');

            $table->string('entity_name'); // Nama Entitas. Pertimbangkan enum atau tabel terpisah jika daftarnya tetap.
            $table->foreignId('jobsite_id')->constrained('jobsites')->onDelete('no action');
            $table->string('serial_number')->unique(); // Serial Number, biasanya unik secara global.
                                                   // Pertimbangkan ->nullable() atau unique per asset_series_id jika perlu:
                                                   // $table->string('serial_number')->nullable();
                                                   // $table->unique(['asset_series_id', 'serial_number']);

            $table->enum('status', ['Free', 'Used','Pre-Used','Pending Mutation','Delivery',
             'Repair', 'Disposed', 'Missing', 'Borrowed','Pending Inspection','Pre Disposed'])
                  ->default('Free'); // Status aset
                  $table->foreignId('holder_user_id')
                  ->nullable() // Aset mungkin tidak selalu dipegang individu (misal, di gudang)
                  ->constrained('users') // Merujuk ke tabel 'users'
                  ->onDelete('no action'); // Jika user pemegang dihapus, set kolom ini ke NULL

            // Kolom untuk detail lokasi aset yang lebih spesifik
            $table->string('current_location_detail')->nullable();
            // Anda bisa menggunakan ->text('current_location_detail') jika deskripsi lokasi bisa sangat panjang
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null'); // User yang membuat/mendaftarkan aset

            $table->timestamps(); // created_at dan updated_at
            $table->softDeletes(); // Untuk fitur soft delete (deleted_at)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assets');
    }
};