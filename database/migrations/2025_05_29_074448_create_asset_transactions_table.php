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
        Schema::create('asset_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique();
            $table->date('transaction_date');
            $table->enum('transaction_type', [ 'HANDOVER', 'RETURN', 'DAMAGE_REPORT','MUTATION','DISPOSE']); // ->change() untuk memodifikasi kolom yang ada
            // Tambahkan kolom untuk lokasi mutasi
            $table->foreignId('from_jobsite_id')->nullable()->after('to_user_id')->constrained('jobsites')->onDelete('no action');
            $table->foreignId('to_jobsite_id')->nullable()->after('from_jobsite_id')->constrained('jobsites')->onDelete('no action');
            $table->foreignId('from_user_id')->nullable()->comment('User sumber (pemberi/pengembali)')->constrained('users')->onDelete('no action');
            $table->foreignId('to_user_id')->nullable()->comment('User tujuan (penerima)')->constrained('users')->onDelete('no action');
            $table->text('notes')->nullable();
            $table->enum('status', ['Draft', 'Pending Approval','In Transit','Pending Release','Rejected','Pending KASI IT','Pending Cost Control','Pending Staff IT', 'Pending Inspection', 'Completed', 'Cancelled','Pre Disposed','Disposed','Delivery','Pending Inspection'])->default('Draft');
            $table->string('document_path')->nullable();
            $table->string('document_name')->nullable();
            $table->foreignId('created_by_user_id')->nullable()->constrained('users')->onDelete('no action');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_transactions');
    }
};
