<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            // Nomor unik ticket
            $table->string('ticket_no')->unique();
            // User yg membuat tiket
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('jobsite_id')->constrained('jobsites');
            // Kategori tiket
            $table->foreignId('ticket_sub_category_id')->constrained('ticket_sub_categories');
            // Assignment
            $table->foreignId('assigned_to')->nullable()->constrained('users'); // Dikerjakan oleh
            $table->foreignId('assigned_by')->nullable()->constrained('users'); // Ditugaskan oleh
            $table->timestamp('assigned_at')->nullable();                       // Waktu ditugaskan
            // Close
            $table->foreignId('closed_by')->nullable()->constrained('users');  // Yang menutup tiket
            $table->timestamp('closed_at')->nullable();                        // Waktu ditutup
            // Due date (opsional)
            $table->dateTime('due_date')->nullable();
            // Informasi ticket
            $table->string('title');
            $table->longText('description')->nullable();
            $table->string('attachment')->nullable();
            // Status & Prioritas
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('low');
            $table->enum('status', [
                'open',
                'in_progress',
                'pending',
                'resolved',
                'closed',
            ])->default('open');
            // Catatan penyelesaian
            $table->text('resolution_note')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
