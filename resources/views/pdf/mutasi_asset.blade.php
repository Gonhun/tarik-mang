@php
    $t = $bast;
    $date = $t->transaction_date ?? $t->created_at;
    $closed = in_array($t->status, \App\Support\TransactionApprovalRepair::CLOSED_STATUSES, true);

    $signers = [[
        'role' => 'Diajukan oleh',
        'name' => $t->creator->name ?? null,
        'state' => 'approved',
        'date' => \App\Support\BaFormat::date($t->created_at, 'D MMM YYYY'),
    ]];
    foreach ($t->approvals->sortBy('step_order') as $ap) {
        $state = match ($ap->status) {
            'Approved' => 'approved',
            'Rejected' => 'rejected',
            default => $closed ? 'none' : 'pending',
        };
        $signers[] = [
            'role' => $ap->approver_role_name ?: 'Tahap ' . $ap->step_order,
            'name' => $ap->actionBy->name ?? $ap->approver->name ?? null,
            'state' => $state,
            'date' => match ($state) {
                'approved' => ($ap->notes === \App\Support\Workflow::AUTO_NOTE ? 'Otomatis · ' : '') . \App\Support\BaFormat::date($ap->action_at, 'D MMM YYYY'),
                'rejected' => 'Ditolak ' . \App\Support\BaFormat::date($ap->action_at, 'D MMM YYYY'),
                'none' => ' ',
                default => 'Menunggu persetujuan',
            },
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Mutasi {{ $t->transaction_number }}</title>
    @include('pdf.partials.ba-style')
</head>
<body>
@include('pdf.partials.ba-footer', ['docLabel' => 'BA Mutasi', 'docNumber' => $t->transaction_number])

@include('pdf.partials.ba-header', [
    'docTitle' => 'Berita Acara Mutasi Aset',
    'docNumber' => $t->transaction_number,
    'meta' => [
        'Tanggal' => e(\App\Support\BaFormat::date($date)),
        'Rute' => e(($t->fromJobsite->code ?? '-') . ' ke ' . ($t->toJobsite->code ?? '-')),
        'Jumlah aset' => $t->items->count() . ' unit',
        'Status' => \App\Support\BaFormat::status($t->status),
    ],
])

<div class="section">
    <p class="lead">{!! \App\Support\BaFormat::opening($date, $t->fromJobsite->name ?? null) !!} telah diajukan mutasi aset antar jobsite sebagai berikut:</p>
    <table class="parties">
        <tr>
            <td class="party">
                @include('pdf.partials.ba-party', ['title' => 'Jobsite asal', 'rows' => [
                    'Jobsite' => trim(($t->fromJobsite->code ?? '') . ' — ' . ($t->fromJobsite->name ?? ''), ' —'),
                    'Cost center' => $fromCostCenter->cost_center_number ?? null,
                ]])
            </td>
            <td class="gap"></td>
            <td class="party">
                @include('pdf.partials.ba-party', ['title' => 'Jobsite tujuan', 'rows' => [
                    'Jobsite' => trim(($t->toJobsite->code ?? '') . ' — ' . ($t->toJobsite->name ?? ''), ' —'),
                    'Cost center' => $destinationCostCenter->cost_center_number ?? null,
                ]])
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Rincian aset</div>
    @include('pdf.partials.ba-assets', ['items' => $t->items])
</div>

@if ($t->notes)
    <div class="section">
        <div class="section-title">Alasan / catatan</div>
        <div class="note">{!! nl2br(e($t->notes)) !!}</div>
    </div>
@endif

<div class="section statement">
    <div class="section-title">Pernyataan</div>
    <ol>
        <li>Beban cost center atas aset di atas dilepaskan dari jobsite asal dan dibebankan kepada jobsite tujuan sejak mutasi disetujui.</li>
        <li>Tanggung jawab fisik aset berpindah kepada jobsite tujuan setelah aset diterima.</li>
    </ol>
</div>

<div class="section">
    <div class="section-title">Pengesahan</div>
    @include('pdf.partials.ba-signatures', ['signers' => $signers])
</div>
</body>
</html>
