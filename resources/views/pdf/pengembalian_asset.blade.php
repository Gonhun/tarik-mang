@php
    $t = $bast->returnTransaction;
    $date = $t->transaction_date ?? $t->created_at;
    $place = $t->fromJobsite->name ?? $t->toJobsite->name ?? $bast->jobsite->name ?? null;
    $inspected = $t->to_user_id !== null;
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Pengembalian {{ $t->transaction_number }}</title>
    @include('pdf.partials.ba-style')
</head>
<body>
@include('pdf.partials.ba-footer', ['docLabel' => 'BA Pengembalian', 'docNumber' => $t->transaction_number])

@include('pdf.partials.ba-header', [
    'docTitle' => 'Berita Acara Pengembalian Aset',
    'docNumber' => $t->transaction_number,
    'meta' => [
        'Tanggal' => e(\App\Support\BaFormat::date($date)),
        'Lokasi' => e($place ?? '-'),
        'Jumlah aset' => $t->items->count() . ' unit',
        'Status' => \App\Support\BaFormat::status($t->status),
    ],
])

<div class="section">
    <p class="lead">{!! \App\Support\BaFormat::opening($date, $place) !!} telah dilakukan pengembalian aset antara:</p>
    <table class="parties">
        <tr>
            <td class="party">
                @include('pdf.partials.ba-party', ['title' => 'Pihak Pertama — Mengembalikan', 'rows' => [
                    'Nama' => $t->fromUser->name ?? null,
                    'NIK' => $t->fromUser->nik ?? null,
                    'Departemen' => $t->fromUser->department->name ?? null,
                ]])
            </td>
            <td class="gap"></td>
            <td class="party">
                @include('pdf.partials.ba-party', ['title' => 'Pihak Kedua — Menerima (IT)', 'rows' => [
                    'Nama' => $t->toUser->name ?? ($inspected ? null : 'Menunggu inspeksi IT'),
                    'NIK' => $t->toUser->nik ?? null,
                    'Departemen' => $t->toUser->department->name ?? null,
                ]])
            </td>
        </tr>
    </table>
</div>

<div class="section">
    <div class="section-title">Rincian aset dan hasil pemeriksaan</div>
    @include('pdf.partials.ba-assets', ['items' => $t->items])
</div>

@if ($t->notes)
    <div class="section">
        <div class="section-title">Catatan</div>
        <div class="note">{!! nl2br(e($t->notes)) !!}</div>
    </div>
@endif

<div class="section statement">
    <div class="section-title">Pernyataan</div>
    <ol>
        <li>Pihak Pertama telah mengembalikan aset di atas kepada Pihak Kedua (IT) dalam kondisi sebagaimana tercantum dan telah diperiksa bersama.</li>
        <li>Sejak tanggal berita acara ini, tanggung jawab atas aset beralih dari Pihak Pertama kepada Pihak Kedua (IT).</li>
        <li>Selama pemeriksaan belum selesai, kerusakan atau kekurangan yang ditemukan tetap dapat ditelusuri kepada Pihak Pertama.</li>
    </ol>
</div>

<div class="section">
    <div class="section-title">Pengesahan</div>
    @include('pdf.partials.ba-signatures', ['signers' => [
        ['role' => 'Yang mengembalikan (Pihak Pertama)', 'name' => $t->fromUser->name ?? null, 'state' => 'approved', 'date' => \App\Support\BaFormat::date($date, 'D MMM YYYY')],
        ['role' => 'Yang menerima (Pihak Kedua / IT)', 'name' => $t->toUser->name ?? null,
            'state' => $t->status === 'Rejected' ? 'rejected' : ($inspected ? 'approved' : 'pending'),
            'date' => $inspected ? 'Sudah diperiksa' : 'Menunggu inspeksi'],
    ]])
</div>
</body>
</html>
