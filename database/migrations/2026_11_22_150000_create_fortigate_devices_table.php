<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Daftar perangkat Fortigate (bisa lebih dari satu, per jobsite).
 * Hanya menambah 1 tabel baru; tidak mengubah tabel lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fortigate_devices', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->foreignId('jobsite_id')->nullable()->constrained('jobsites');
            $table->string('base_url');                 // mis. https://192.168.4.1:8443
            $table->text('api_token');                  // terenkripsi (cast 'encrypted')
            $table->string('vdom', 50)->nullable();
            $table->boolean('verify_ssl')->default(false);
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            // Hasil cek terakhir
            $table->string('last_status', 20)->nullable(); // online | offline | auth_error
            $table->text('last_error')->nullable();
            $table->text('last_info')->nullable();         // JSON: hostname, model, serial, versi, cpu, mem, sesi
            $table->timestamp('last_checked_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users');
            $table->timestamps();

            $table->index(['jobsite_id', 'is_active']);
        });

        // Pindahkan Fortigate tunggal dari .env (jika ada) sebagai perangkat pertama.
        $url = config('services.fortigate.url');
        $token = config('services.fortigate.token');
        if ($url && $token) {
            DB::table('fortigate_devices')->insert([
                'name' => 'Fortigate HO',
                'jobsite_id' => DB::table('jobsites')->where('code', 'JKT')->value('id'),
                'base_url' => preg_replace('#/api/v2/?$#', '', rtrim($url, '/')),
                'api_token' => encrypt($token, false),
                'verify_ssl' => (bool) config('services.fortigate.verify_ssl'),
                'is_active' => true,
                'notes' => 'Dipindahkan dari konfigurasi .env (FORTIGATE_API_URL).',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('fortigate_devices');
    }
};
