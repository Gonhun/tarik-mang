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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();

            // Judul pekerjaan
            $table->string('title');

            // Deskripsi pekerjaan
            $table->text('description')->nullable();

            // Status task (default: pending)
            $table->enum('status', ['pending', 'progress', 'done'])->default('pending');

            // Persentase progress
            $table->integer('progress')->default(0); // 0-100

            // User yang mengerjakan
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->onDelete('set null');

            // Optional: deadline pekerjaan (kalau kamu mau)
            $table->date('due_date')->nullable();

            // Optional: prioritas
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
