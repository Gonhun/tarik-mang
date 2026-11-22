@php
    $t = $bast->transaction;
    $date = $t->transaction_date ?? $t->created_at;
    $place = $t->fromJobsite->name ?? $t->toJobsite->name ?? $bast->jobsite->name ?? null;
    $received = $bast->status !== 'Pre-Used';
    $isLoan = str_starts_with((string) $t->notes, \App\Support\AssetLoan::TAG);
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAST {{ $t->transaction_number }}</title>
    @include('pdf.partials.ba-style')
</head>
<body>
@include('pdf.partials.ba-footer', ['docLabel' => 'BAST', 'docNumber' => $t->transaction_number])

@include('pdf.partials.ba-header', [
    'docTitle' => $isLoan ? 'Berita Acara Peminjaman Aset' : 'Berita Acara Serah Terima Aset',
    'docNumber' => $t->transaction_number,
    'meta' => [
        'Tanggal' => e(\App\Support\BaFormat::date($date)),
        'Lokasi' => e($place ?? '-'),
        'Jumlah aset' => $t->items->count() . ' unit',
        'Status penerimaan' => $received ? \App\Support\BaFormat::status('Completed') : \App\Support\BaFormat::status('Pending'),
    ],
])

<div class="section">
    <p class="lead">{!! \App\Support\BaFormat::opening($date, $place) !!} telah dilakukan serah terima aset antara:</p>
    <table class="parties">
        <tr>
            <td class="party">
                @include('pdf.partials.ba-party', ['title' => 'Pihak Pertama — Menyerahkan', 'rows' => [
                    'Nama' => $t->fromUser->name ?? null,
                    'NIK' => $t->fromUser->nik ?? null,
                    'Departemen' => $t->fromUser->department->name ?? null,
                ]])
            </td>
            <td class="gap"></td>
            <td class="party">
                @include('pdf.partials.ba-party', ['title' => 'Pihak Kedua — Menerima', 'rows' => [
                    'Nama' => $t->toUser->name ?? null,
                    'NIK' => $t->toUser->nik ?? null,
                    'Departemen' => $t->toUser->department->name ?? null,
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
        <div class="section-title">Catatan</div>
        <div class="note">{!! nl2br(e($t->notes)) !!}</div>
    </div>
@endif

<div class="section statement">
    <div class="section-title">Pernyataan</div>
    <ol>
        <li>Pihak Kedua telah menerima aset di atas dari Pihak Pertama dalam kondisi sebagaimana tercantum dan telah diperiksa bersama.</li>
        <li>Sejak tanggal berita acara ini, tanggung jawab atas penggunaan, pemeliharaan, dan keamanan aset beralih kepada Pihak Kedua.</li>
        <li>Aset <b>dilarang dipindahtangankan</b> kecuali melalui tim IT atau prosedur pengembalian resmi, dan tetap menjadi tanggung jawab Pihak Kedua sampai ada Berita Acara Pengembalian.</li>
    </ol>
</div>

<div class="section">
    <div class="section-title">Pengesahan</div>
    @include('pdf.partials.ba-signatures', ['signers' => [
        ['role' => 'Yang menyerahkan (Pihak Pertama)', 'name' => $t->fromUser->name ?? null, 'state' => 'approved', 'date' => \App\Support\BaFormat::date($date, 'D MMM YYYY')],
        ['role' => 'Yang menerima (Pihak Kedua)', 'name' => $t->toUser->name ?? null, 'state' => $received ? 'approved' : 'pending',
            'date' => $received ? 'Diterima' : 'Menunggu konfirmasi penerima'],
    ]])
</div>
</body>
</html>
