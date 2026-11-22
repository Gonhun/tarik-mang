<?php

namespace App\Providers;

use App\Models\AssetRequest;
use App\Models\AssetTransaction;
use App\Models\LicenseRequest;
use App\Models\LicenseRequestApproval;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Role yang boleh melihat semua dokumen.
     */
    private const ADMIN_ROLES = ['Administrator IT', 'Administrator'];

    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        set_time_limit(0);

        $this->defineDocumentGates();
    }

    /**
     * Aturan siapa yang boleh membuka dokumen berdasarkan ID di URL.
     * Tanpa ini, user yang login bisa membuka dokumen siapa pun dengan mengganti ID.
     */
    private function defineDocumentGates(): void
    {
        Gate::define('view-asset-request', function (User $user, AssetRequest $request) {
            if ($user->hasRole(self::ADMIN_ROLES) || $user->can('Manajement Request') || $user->isItStaff()) {
                return true;
            }

            return (int) $request->requester_user_id === $user->id
                || (int) $request->current_approver_id === $user->id
                || $request->approvals()
                    ->where(fn ($q) => $q->where('approver_user_id', $user->id)->orWhere('action_by_user_id', $user->id))
                    ->exists();
        });

        Gate::define('view-license-request', function (User $user, LicenseRequest $request) {
            if ($user->hasRole(self::ADMIN_ROLES) || $user->can('Lisensi')) {
                return true;
            }

            return (int) $request->user_id === $user->id
                || LicenseRequestApproval::where('user_id', $user->id)
                    ->whereIn('license_request_item_id', $request->items()->select('id'))
                    ->exists();
        });

        Gate::define('view-asset-transaction', function (User $user, AssetTransaction $transaction) {
            if ($user->hasRole(self::ADMIN_ROLES) || $user->can('Mutasi') || $user->can('Master Asset')) {
                return true;
            }

            return in_array($user->id, [
                    (int) $transaction->from_user_id,
                    (int) $transaction->to_user_id,
                    (int) $transaction->created_by_user_id,
                ], true)
                || $transaction->approvals()
                    ->where(fn ($q) => $q->where('approver_user_id', $user->id)->orWhere('action_by_user_id', $user->id))
                    ->exists();
        });

        // Halaman master tiket (kategori/subkategori): hanya tim IT.
        Gate::define('it-staff', fn (User $user) => $user->isItStaff() || $user->hasRole(self::ADMIN_ROLES));

        // Cetak label aset: pengelola aset atau pemegang aset.
        Gate::define('print-asset-label', fn (User $user, \App\Models\Asset $asset) => $user->can('Master Asset')
            || $user->hasRole(self::ADMIN_ROLES)
            || (int) $asset->holder_user_id === $user->id);

        // Lihat tiket: pembuat, PIC yang ditugaskan, atau tim IT.
        Gate::define('view-ticket', function (User $user, Ticket $ticket) {
            return $user->isItStaff()
                || $user->hasRole(self::ADMIN_ROLES)
                || (int) $ticket->user_id === $user->id
                || (int) $ticket->assigned_to === $user->id;
        });

        // Menugaskan PIC & mengubah status tiket: hanya tim IT (sama seperti tombol di tampilan).
        Gate::define('manage-ticket', function (User $user, Ticket $ticket) {
            return $user->isItStaff() || $user->hasRole(self::ADMIN_ROLES);
        });
    }
}
