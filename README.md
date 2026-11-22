✨ **Langkah-langkah Publish Konfigurasi & Migrasi Paket Laravel** ✨

Jalankan perintah-perintah berikut untuk mengatur dan mengaktifkan fitur-fitur penting pada aplikasi Anda:

1. **Livewire Config**
   ```bash
   php artisan livewire:publish --assets
   ```
php artisan livewire:publish --config
2. **OwenIt Auditing**
   - Publish konfigurasi:
     ```bash
     php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider" --tag="config"
     ```
   - Publish migrasi:
     ```bash
     php artisan vendor:publish --provider="OwenIt\Auditing\AuditingServiceProvider" --tag="migrations"
     ```

3. **Barryvdh DomPDF**
   - Publish konfigurasi:
     ```bash
     php artisan vendor:publish --provider="Barryvdh\DomPDF\ServiceProvider" --tag=config
     ```

4. **Spatie Permission**
   - Publish migrasi:
     ```bash
     php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="permission-migrations"
     ```
   - Publish konfigurasi:
     ```bash
     php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider" --tag="permission-config"
     ```
5. **Deploy DB dan Seed**
  - Migrate DB :
    ```bash
    php artisan migrate
    ```
  - Seed :
    ```bash
    php artisan db:seed
    ```
6. **men-deploy aplikasi Laravel**
    ```bash
    composer install --no-dev --optimize-autoloader
    ```

🚀 **Selamat!** Setelah !
