<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use App\Models\User;
use App\Models\AssetRequest;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        $this->registerPolicies();

        // Definisikan Gate untuk input data pengadaan
        Gate::define('input-procurement-data', function (User $user, AssetRequest $assetRequest) {
            // Logika: User boleh input jika perannya adalah 'Admin IT'.
            // Asumsi model User punya relasi 'role' dan model Role punya kolom 'code'.
            // Ganti 'IT_ADMIN' dengan kode peran yang sesuai di database Anda.
            dd($user->department->code);
            return $user->department && $user->department->code === 'IT';
           
        });
    }
}