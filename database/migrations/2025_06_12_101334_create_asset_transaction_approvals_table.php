<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_transaction_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_transaction_id')->constrained('asset_transactions')->onDelete('cascade');

            $table->unsignedInteger('step_order')->comment('Urutan persetujuan (1, 2, 3, ...)');
            $table->string('approver_role_name')->comment('Nama peran approver, cth: Kasi IT, Cost Control');

            $table->foreignId('approver_user_id')->nullable()->comment('User yang ditunjuk untuk approve')
                  ->constrained('users')->onDelete('no action');

            $table->enum('status', ['Pending', 'Approved', 'Rejected'])->default('Pending');

            $table->foreignId('action_by_user_id')->nullable()->comment('User yang melakukan aksi approve/reject')
                  ->constrained('users')->onDelete('no action');
            $table->timestamp('action_at')->nullable()->comment('Waktu aksi approve/reject');
            $table->text('notes')->nullable()->comment('Catatan dari approver');

            $table->timestamps();

            // Pastikan satu transaksi hanya punya satu step dengan urutan yang sama
            $table->unique(['asset_transaction_id', 'step_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_transaction_approvals');
    }
};