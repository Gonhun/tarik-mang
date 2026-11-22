<?php

namespace App\Support;

use App\Models\ApprovalWorkflow;
use App\Models\ApprovalWorkflowStep;
use App\Models\Jobsite;
use App\Models\User;
use Illuminate\Support\Facades\Log;

/**
 * Penjaga workflow approval untuk semua alur (pengajuan aset, mutasi, disposal, kehilangan, lisensi).
 *
 * Aturan: pengajuan TIDAK BOLEH tersimpan tanpa approver yang lengkap.
 * Bila workflow belum diatur, ada tahap yang approvernya tidak ditemukan, atau hasilnya kosong,
 * dilempar WorkflowNotConfigured dengan pesan yang bisa langsung ditampilkan ke user.
 */
class Workflow
{
    public const LABELS = [
        'REQUEST_ASSET' => 'pengajuan aset',
        'MUTATION' => 'mutasi aset',
        'DISPOSED' => 'disposal aset',
        'LOST' => 'kehilangan aset',
        'REQUEST_LICENSE' => 'pengajuan lisensi',
    ];

    public const ADMIN_HINT = 'Hubungi Administrator untuk mengaturnya di menu Admin › Workflow.';

    /**
     * Master jenis transaksi dari config/transaction_types.php.
     *
     * @return array<string, array{label:string, deskripsi:string, dipakai_di:array, fallback:?string, aktif:bool, wajib:bool}>
     */
    public static function types(bool $onlyActive = false): array
    {
        $types = config('transaction_types', []);
        if (!$types) {
            // Cadangan bila file config belum ter-load.
            $types = collect(self::LABELS)->map(fn ($l) => ['label' => ucfirst($l), 'deskripsi' => '', 'dipakai_di' => [], 'fallback' => null, 'aktif' => true, 'wajib' => true])->all();
        }

        return $onlyActive ? array_filter($types, fn ($t) => $t['aktif'] ?? true) : $types;
    }

    /** Label huruf kecil untuk kalimat, mis. "mutasi aset". */
    public static function label(string $type): string
    {
        $label = self::types()[$type]['label'] ?? self::LABELS[$type] ?? $type;

        return mb_strtolower($label);
    }

    /** [kode => label huruf kecil] untuk tampilan. */
    public static function labels(): array
    {
        return array_map(fn ($t) => mb_strtolower($t['label']), self::types());
    }

    /** Kode fallback dari config (mis. LOST -> DISPOSED). */
    public static function fallbacks(string $type): array
    {
        $fb = self::types()[$type]['fallback'] ?? null;

        return $fb ? [$fb] : [];
    }

    /**
     * Cari workflow untuk konteks (jabatan + site). $fallbacks dicoba berurutan bila $type belum diatur.
     *
     * @throws WorkflowNotConfigured
     */
    public static function resolve(string $type, ?int $positionId, ?int $jobsiteId, array $fallbacks = []): ApprovalWorkflow
    {
        foreach (array_merge([$type], $fallbacks) as $candidate) {
            $workflow = findWorkflowForContext($candidate, $positionId, $jobsiteId);
            if ($workflow && $workflow->steps->isNotEmpty()) {
                return $workflow;
            }
        }

        Log::warning("Workflow {$type} belum diatur", compact('positionId', 'jobsiteId', 'fallbacks'));

        throw new WorkflowNotConfigured(
            'Alur persetujuan (workflow) untuk ' . self::label($type) . ' belum diatur, sehingga pengajuan tidak bisa diproses. ' . self::ADMIN_HINT
        );
    }

    /**
     * Tentukan approver untuk setiap tahap workflow.
     * Tahap "Kepala Proyek" dilewati untuk site SMD / JKT (aturan yang sudah berlaku).
     *
     * @return array<int, array{step_order:int, role:string, user:User}>
     * @throws WorkflowNotConfigured
     */
    public static function approvers(ApprovalWorkflow $workflow, ?int $jobsiteId, string $type): array
    {
        $jobsiteCode = $jobsiteId ? Jobsite::whereKey($jobsiteId)->value('code') : null;
        $result = [];

        foreach ($workflow->steps->sortBy('step_order') as $step) {
            $role = self::roleName($step);

            if ($role === 'Kepala Proyek' && in_array($jobsiteCode, ['SMD', 'JKT'], true)) {
                continue;
            }

            $user = self::findApprover($step, $jobsiteId);
            if (!$user) {
                Log::warning("Approver workflow {$type} tidak ditemukan", ['workflow_id' => $workflow->id, 'step' => $step->step_order, 'role' => $role, 'jobsite_id' => $jobsiteId]);
                throw new WorkflowNotConfigured(
                    "Approver tahap {$step->step_order} ({$role}) untuk " . self::label($type)
                    . ' tidak ditemukan ' . (in_array($jobsiteCode, [null, 'JKT'], true) ? 'di JKT' : "di site {$jobsiteCode} maupun JKT")
                    . ', sehingga pengajuan tidak bisa diproses. ' . self::ADMIN_HINT
                );
            }

            $result[] = ['step_order' => (int) $step->step_order, 'role' => $role, 'user' => $user];
        }

        if (!$result) {
            throw new WorkflowNotConfigured(
                'Workflow ' . self::label($type) . ' tidak menghasilkan satu pun approver untuk site ' . ($jobsiteCode ?? '-') . '. ' . self::ADMIN_HINT
            );
        }

        return $result;
    }

    /**
     * resolve() + approvers() sekaligus.
     *
     * @throws WorkflowNotConfigured
     */
    public static function plan(string $type, ?int $positionId, ?int $initiatorJobsiteId, ?int $approverJobsiteId = null, array $fallbacks = []): array
    {
        $workflow = self::resolve($type, $positionId, $initiatorJobsiteId, $fallbacks);

        return self::approvers($workflow, $approverJobsiteId ?? $initiatorJobsiteId, $type);
    }

    /**
     * Pengecekan awal untuk UI: null bila siap, atau pesan peringatan bila belum bisa diproses.
     */
    public static function problem(string $type, ?User $user, ?int $approverJobsiteId = null, array $fallbacks = []): ?string
    {
        if (!$user) {
            return null;
        }

        try {
            self::plan($type, $user->position_id, $user->jobsite_id, $approverJobsiteId, $fallbacks);
            return null;
        } catch (WorkflowNotConfigured $e) {
            return $e->getMessage();
        }
    }

    /** Catatan untuk tahap yang disetujui otomatis karena approver = pengaju. */
    public const AUTO_NOTE = 'Disetujui otomatis: pengaju adalah approver tahap ini.';

    /**
     * Ubah rencana approver menjadi baris approval (kolom asset_transaction_approvals / asset_request_approvals).
     * Tahap yang approvernya adalah pengaju langsung "Approved" atas nama pengaju.
     */
    public static function approvalRows(array $plan, ?int $initiatorId): array
    {
        return array_map(function ($p) use ($initiatorId) {
            $auto = $initiatorId && (int) $p['user']->id === (int) $initiatorId;

            return [
                'step_order' => $p['step_order'],
                'approver_role_name' => $p['role'],
                'approver_user_id' => $p['user']->id,
                'status' => $auto ? 'Approved' : 'Pending',
                'action_by_user_id' => $auto ? $initiatorId : null,
                'action_at' => $auto ? now() : null,
                'notes' => $auto ? self::AUTO_NOTE : null,
            ];
        }, $plan);
    }

    /** Approver pertama yang masih harus bertindak (bukan tahap yang otomatis disetujui). */
    public static function firstPendingUser(array $plan, ?int $initiatorId): ?User
    {
        foreach ($plan as $p) {
            if ((int) $p['user']->id !== (int) $initiatorId) {
                return $p['user'];
            }
        }

        return null;
    }

    public static function roleName(ApprovalWorkflowStep $step): string
    {
        return $step->approverPositionLevel->name ?? ($step->user->name ?? 'Tahap ' . $step->step_order);
    }

    /**
     * user spesifik > level jabatan (+department) di site terkait > level jabatan (+department) di JKT.
     */
    public static function findApprover(ApprovalWorkflowStep $step, ?int $jobsiteId): ?User
    {
        if ($step->user_id) {
            return User::find($step->user_id);
        }

        if (!$step->approver_position_level_id && !$step->department_id) {
            return null;
        }

        $base = fn () => User::query()
            ->when($step->approver_position_level_id, fn ($q) => $q->where('position_level_id', $step->approver_position_level_id))
            ->when($step->department_id, fn ($q) => $q->where('department_id', $step->department_id));

        return ($jobsiteId ? $base()->where('jobsite_id', $jobsiteId)->first() : null)
            ?? $base()->whereHas('jobsite', fn ($q) => $q->where('code', 'JKT'))->first();
    }

    /**
     * Ringkasan untuk admin: tipe workflow yang belum punya aturan default (berlaku semua jabatan & site).
     *
     * @return string[] label tipe yang belum diatur sama sekali
     */
    public static function unconfiguredTypes(): array
    {
        $configured = \App\Models\ApprovalWorkflowAssignment::query()
            ->whereIn('approval_workflow_id', ApprovalWorkflowStep::select('approval_workflow_id'))
            ->distinct()
            ->pluck('transaction_type')
            ->all();

        return array_values(array_map(
            fn ($t) => self::label($t),
            array_filter(array_keys(array_filter(self::types(true), fn ($t) => $t['wajib'] ?? true)), fn ($t) => !in_array($t, $configured, true))
        ));
    }
}
