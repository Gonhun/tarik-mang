<?php

namespace App\Services;

use App\Mail\MailAssetRequest;
use App\Models\AssetRequest;
use App\Models\User;
use App\Support\Workflow;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

/**
 * Pembuatan pengajuan aset, dipakai form web (Livewire) dan API mobile.
 * Pemohon (requester) = penerima aset; submitter = yang mengisi form (bisa sama).
 */
class AssetRequestService
{
    /** Boleh mengajukan untuk karyawan lain di departemen yang sama. */
    public const ON_BEHALF_PERMISSION = 'Ajukan Aset untuk Orang Lain';

    public static function canSubmitForOthers(?User $user): bool
    {
        return $user && $user->department_id && $user->can(self::ON_BEHALF_PERMISSION);
    }

    /** Karyawan aktif satu departemen dengan pengaju (selain dirinya) yang boleh dipilih sebagai penerima. */
    public static function eligibleBeneficiaries(User $submitter)
    {
        return User::query()
            ->where('department_id', $submitter->department_id)
            ->where('id', '!=', $submitter->id)
            ->where('is_active', 1);
    }

    /**
     * Penerima yang sah untuk pengaju ini, atau null bila tidak boleh (tanpa permission / beda departemen / nonaktif).
     * $forUserId kosong atau sama dengan pengaju = mengajukan untuk diri sendiri.
     */
    public static function resolveRequester(User $submitter, $forUserId): ?User
    {
        if (!$forUserId || (int) $forUserId === (int) $submitter->id) {
            return $submitter;
        }
        if (!self::canSubmitForOthers($submitter)) {
            return null;
        }

        return self::eligibleBeneficiaries($submitter)->find($forUserId);
    }

    /**
     * Item standar jabatan pemohon yang belum ia pegang (diisi otomatis di form).
     *
     * @return array<int, array>
     */
    public function standardItems(User $requester): array
    {
        $requester->loadMissing('position.assetStandards', 'assetsHeld.assetSeries');
        if (!$requester->position) {
            return [];
        }

        $heldSeriesIds = $requester->assetsHeld->whereNotNull('asset_series_id')->pluck('asset_series_id')->unique()->toArray();
        $items = [];
        foreach ($requester->position->assetStandards as $standard) {
            if ($standard->asset_series_id && !in_array($standard->asset_series_id, $heldSeriesIds)) {
                $items[] = [
                    'asset_type_id' => $standard->asset_type_id,
                    'asset_brand_id' => $standard->asset_brand_id,
                    'asset_series_id' => $standard->asset_series_id,
                    'quantity' => $standard->quantity,
                    'item_description' => $standard->specifications_notes,
                    'is_standard' => true,
                ];
            }
        }

        return $items;
    }

    public function generateRequestNumber(User $requester): string
    {
        $jobsite = $requester->jobsite->code;
        $prefix = 'REQ/' . $jobsite . '/' . date('ym') . '/';
        $lastRequest = AssetRequest::where('request_number', 'LIKE', $prefix . '%')->orderByDesc('id')->first();
        $nextSequence = $lastRequest ? ((int) substr($lastRequest->request_number, -4)) + 1 : 1;

        return $prefix . str_pad($nextSequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Simpan pengajuan + item (dipecah per unit) + tahapan approval, lalu kirim email.
     * Workflow belum siap -> WorkflowNotConfigured (seluruh pengajuan & file upload dibatalkan).
     *
     * @param array $data purpose, needed_date, items[] (asset_type_id, asset_brand_id, asset_series_id, quantity, item_description, is_standard, ...)
     */
    public function submit(User $requester, User $submitter, array $data, ?UploadedFile $baDamage = null, ?UploadedFile $baBudgetOutside = null): AssetRequest
    {
        $files = [
            'ba_damage' => $baDamage ? ['path' => $baDamage->store('request_asset/ba_damage', 'public'), 'name' => $baDamage->getClientOriginalName()] : null,
            'ba_budget_outside' => $baBudgetOutside ? ['path' => $baBudgetOutside->store('request_asset/ba_budget_outside', 'public'), 'name' => $baBudgetOutside->getClientOriginalName()] : null,
        ];

        try {
            $assetRequest = DB::transaction(function () use ($requester, $submitter, $data, $files) {
                $request = AssetRequest::create([
                    'request_number' => $this->generateRequestNumber($requester),
                    'requester_user_id' => $requester->id,
                    'submitted_by_user_id' => $submitter->id,
                    'jobsite_id' => $requester->jobsite_id,
                    'request_date' => now(),
                    'needed_date' => $data['needed_date'],
                    'purpose' => $data['purpose'],
                    'status' => 'Pending IT Item Validation',
                    'ba_damage_path' => $files['ba_damage']['path'] ?? null,
                    'ba_damage_name' => $files['ba_damage']['name'] ?? null,
                    'ba_budget_outside_path' => $files['ba_budget_outside']['path'] ?? null,
                    'ba_budget_outside_name' => $files['ba_budget_outside']['name'] ?? null,
                ]);

                // Item dipecah per unit: quantity N -> N baris dengan quantity 1.
                foreach ($data['items'] as $itemData) {
                    $qty = (int) $itemData['quantity'];
                    unset($itemData['quantity']);
                    for ($i = 0; $i < $qty; $i++) {
                        $request->items()->create(array_merge($itemData, ['quantity' => 1]));
                    }
                }

                // Workflow mengikuti jabatan & site pemohon (penerima). Tahap yang approvernya pemohon disetujui otomatis.
                $plan = Workflow::plan('REQUEST_ASSET', $requester->position_id, $requester->jobsite_id);
                $request->approvals()->createMany(Workflow::approvalRows($plan, $requester->id));

                return $request;
            });
        } catch (\Throwable $e) {
            foreach ($files as $f) {
                if ($f) Storage::disk('public')->delete($f['path']);
            }
            throw $e;
        }

        $this->sendSubmitEmails($assetRequest, $requester, $submitter);

        return $assetRequest;
    }

    protected function sendSubmitEmails(AssetRequest $assetRequest, User $requester, User $submitter): void
    {
        // Email ke pemohon/penerima, dan ke pengisi form bila diajukan atas nama orang lain.
        $recipients = collect([$requester->karyawans->Email ?? null]);
        if ((int) $requester->id !== (int) $submitter->id) {
            $recipients->push($submitter->karyawans->Email ?? null);
        }
        $recipients = $recipients->filter()->unique()->values();
        if ($recipients->isNotEmpty()) {
            try {
                Mail::mailer('smtp2')->to($recipients->all())->send(new MailAssetRequest([
                    'type' => 'SUBMIT',
                    'author' => $requester->name,
                    'draft' => $assetRequest,
                ]));
            } catch (\Throwable $mailError) {
                Log::error('Gagal kirim email SUBMIT pengajuan aset: ' . $mailError->getMessage());
            }
        }

        // Approver pertama yang masih harus bertindak (tahap otomatis dilewati).
        $firstApprover = $assetRequest->approvals()->where('status', 'Pending')->get()->sortBy('step_order')->first();
        $approverEmail = $firstApprover?->approver?->karyawans->Email ?? null;
        if ($approverEmail) {
            try {
                Mail::mailer('smtp2')->to($approverEmail)->send(new MailAssetRequest([
                    'type' => 'OUTSTANDING_APPROVER',
                    'approver' => $firstApprover->approver->name,
                    'draft' => $firstApprover,
                ]));
            } catch (\Throwable $mailError) {
                Log::error('Gagal kirim email approver pengajuan aset: ' . $mailError->getMessage());
            }
        }
    }
}
