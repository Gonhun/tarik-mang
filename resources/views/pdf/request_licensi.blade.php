<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Licensi Request - {{ $licens->request_no }}</title>

    <style>
        @page {
            size: A4;
            margin: 15mm 15mm 20mm 15mm;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 9pt;
            color: #333;
            line-height: 1.45;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .bold { font-weight: bold; }

        /* ===== HEADER ===== */
        .header {
            border-bottom: 1px solid #000;
            margin-bottom: 12px;
        }

        .header td {
            vertical-align: middle;
        }

        .logo {
            width: 75px;
        }

        .company-name {
            font-size: 11pt;
            font-weight: bold;
        }

        .company-info {
            font-size: 7.5pt;
            color: #555;
        }

        /* ===== TITLE ===== */
        .title {
            text-align: center;
            margin: 14px 0;
        }

        .title h2 {
            font-size: 12pt;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .title p {
            margin: 3px 0 0;
            font-size: 9pt;
        }

        /* ===== INFO ===== */
        .info-table td {
            padding: 3px 0;
        }

        .info-label {
            width: 160px;
            font-weight: bold;
        }

        /* ===== SECTION ===== */
        .section-title {
            font-weight: bold;
            margin: 12px 0 6px;
        }

        /* ===== TABLE ===== */
        .data-table th,
        .data-table td {
            border: 1px solid #999;
            padding: 5px;
            font-size: 8.5pt;
        }

        .data-table th {
            background: #efefef;
            text-align: center;
        }

        .remarks {
            font-style: italic;
            background: #fafafa;
        }

        /* ===== SIGNATURE ===== */
        .signature-table td {
            width: 50%;
            text-align: center;
            vertical-align: top;
            padding-top: 25px;
        }

        .signature-line {
            margin: 40px auto 5px;
            width: 70%;
            border-bottom: 1px solid #000;
            height: 1px;
            position: relative;
        }

        .stamp {
            position: absolute;
            top: -50px;
            left: 50%;
            transform: translateX(-50%) rotate(-12deg);
            opacity: 0.8;
        }

        .stamp img {
            width: 65px;
        }

        /* ===== FOOTER ===== */
        .footer {
            position: fixed;
            bottom: -18px;
            left: 0;
            right: 0;
            font-size: 7pt;
            color: #777;
        }
    </style>
</head>

<body>

@php
    $logo = public_path('assets/img/LogoBSS.png');
    $approve = public_path('assets/img/approved-stamp.png');
    $pending = public_path('assets/img/pending-stamp.png');
@endphp

<!-- HEADER -->
<table class="header">
    <tr>
        <td style="width:90px;">
            @if(file_exists($logo))
                <img src="{{ $logo }}" class="logo">
            @endif
        </td>
        <td class="text-right">
            <div class="company-name">PT. Bina Sarana Sukses</div>
            <div class="company-info">
                Landmark Pluit D17, Jakarta Utara 14450<br>
                Telp. (021) 22677447 | itops@binasaranasukses.com<br>
                www.binasaranasukses.co.id
            </div>
        </td>
    </tr>
</table>

<!-- TITLE -->
<div class="title">
    <h2>Berita Acara Permintaan Licensi</h2>
    <p>Nomor: {{ $licens->request_no }}</p>
</div>

<!-- INFO -->
<table class="info-table">
    <tr>
        <td class="info-label">Tanggal Permintaan</td>
        <td>: {{ \Carbon\Carbon::parse($licens->created_at)->translatedFormat('l, d F Y') }}</td>
    </tr>
    <tr>
        <td class="info-label">Keperluan</td>
        <td>: {{ $licens->purpose }}</td>
    </tr>
</table>

<p>
    Pada hari {{ \Carbon\Carbon::parse($licens->created_at)->translatedFormat('l, d F Y') }} telah diajukan permintaan licensi oleh pihak berikut:
</p>

<!-- REQUESTOR -->
<table class="data-table">
    <tr>
        <th colspan="2">Data Pemohon</th>
    </tr>
    <tr>
        <td width="50%">
            <b>Nama</b><br>
            {{ $licens->user->name ?? '-' }}
        </td>
        <td width="50%">
            <b>NIK</b><br>
            {{ $licens->user->nik ?? '-' }}
        </td>
    </tr>
    <tr>
        <td>
            <b>Jabatan</b><br>
            {{ $licens->user->position->name ?? '-' }}
        </td>
        <td>
            <b>Jobsite</b><br>
            {{ $licens->user->jobsite->code ?? '-' }}
        </td>
    </tr>
</table>

<div class="section-title">Rincian Licensi</div>

<!-- ITEMS -->
<table class="data-table">
    <thead>
        <tr>
            <th width="5%">No</th>
            <th width="20%">Manufaktur</th>
            <th width="20%">Kategori</th>
            <th>Nama Software</th>
            <th width="7%">Qty</th>
            <th width="13%">Harga</th>
        </tr>
    </thead>
    <tbody>
        @forelse($licens->items as $i => $item)
            <tr>
                <td class="text-center">{{ $i+1 }}</td>
                <td>{{ $item->softwareManufacturer->name ?? '-' }}</td>
                <td>{{ $item->softwareCategory->name ?? '-' }}</td>
                <td>{{ $item->software_name }}</td>
                <td class="text-center">{{ number_format($item->quantity) }}</td>
                <td class="text-right">{{ number_format($item->price) }}</td>
            </tr>
            @if($item->remarks)
                <tr class="remarks">
                    <td colspan="6"><b>Keterangan:</b> {{ $item->remarks }}</td>
                    <td><b>Total:</b></td>
                    <td class="text-right"><b>{{ number_format($item->quantity * $item->price) }}</b></td>
                </tr>
            @endif
        @empty
            <tr>
                <td colspan="6" class="text-center">Tidak ada data</td>
            </tr>
        @endforelse
    </tbody>
</table>
 @foreach($licens->items as $item)
<!-- SIGNATURE -->
<table class="signature-section" style="width: 100%; text-align: center;">
    <tr>
       
            @foreach($item->approvals as $approval)
                <td>
                    <div  class="signature-title">{{ $approval->approver->name }}</div>
                     <div class="signature-space" style="display: flex; justify-content: center; align-items: center;">
                     <div class="signature-line">
                        <div class="stamp">
                            <img src="{{ $approval->status === 'Pending' ? $pending : $approve }}">
                        </div>
                    </div>
                     </div>
                    
                    <div class="signer-name" style="display: block; margin-top: 3px;">{{ $approval->approver->position->name }}</div>
                </td>
            @endforeach
       
    </tr>
</table>
 @endforeach
<!-- FOOTER -->
<div class="footer">
    <script type="text/php">
        if (isset($pdf)) {
            $pdf->page_text(260, 820, "Halaman {PAGE_NUM} dari {PAGE_COUNT}", null, 7, [0.5,0.5,0.5]);
            $pdf->page_text(30, 820, "Dicetak: {{ now()->translatedFormat('d F Y H:i') }}", null, 7, [0.5,0.5,0.5]);
        }
    </script>
</div>

</body>
</html>
