<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('chat_messages', function (Blueprint $table) {
            $table->id();
            $table->string('session_id')->index(); // Untuk mengelompokkan chat dalam satu sesi
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null'); // Jika pengirim adalah user terdaftar
            $table->enum('sender_type', ['user', 'bot', 'admin'])->default('user');
            $table->enum('message_type', ['text', 'image'])->default('text');
            $table->text('content')->nullable(); // Untuk pesan teks
            $table->string('file_path')->nullable(); // Path ke file gambar/lampiran
            $table->string('file_name')->nullable(); // Nama asli file
            $table->string('mime_type')->nullable(); // Tipe MIME file
            $table->json('metadata')->nullable(); // Untuk data tambahan (misalnya, dari Dialogflow)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('chat_messages');
    }
};