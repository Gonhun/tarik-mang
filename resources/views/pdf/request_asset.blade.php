<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Berita Acara Request Aset - {{ $bast->request_number }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            font-size: 10pt;
            color: #222;
            margin: 0px;
            background: #fff;
        }
        @page { size: A4; margin: 18mm 18mm 25mm 18mm; }

        .container {
            width: 100%;
            margin: 0 auto;
            background: #fff;
        }

        /* Header */
        .header-table {
            width: 100%;
            border-bottom: 2px solid #124063;
            margin-bottom: 16px;
            padding-bottom: 8px;
        }
        .header-table td {
            vertical-align: middle;
            border: none;
        }
        .logo {
            width: 75px; 
            height: auto; 
            display: block;
        }
        .company-details {
            text-align: right;
            padding-left: 14px;
        }
        .company-name {
            font-size: 15pt;
            font-weight: bold;
            color: #124063;
            margin-bottom: 5px;
        }
        .company-details p {
            margin: 0;
            font-size: 8pt;
            color: #555;
        }
        .document-title {
            text-align: center;
            margin-bottom: 18px;
        }
        .document-title h2 {
            font-size: 14pt;
            letter-spacing: 1.5px;
            margin: 0 0 5px 0;
            text-transform: uppercase;
            color: #124063;
        }
        .document-title p {
            margin: 0;
            font-size: 10pt;
            color: #333;
            letter-spacing: 0.5px;
        }
        /* Info Section */
        .bast-info-table {
            width: 100%;
            margin-bottom: 14px;
            font-size: 10pt;
        }
        .bast-info-table td {
            padding: 4px 2px;
        }
        .bast-info-table td.label {
            width: 145px;
            font-weight: bold;
            color: #124063;
        }
        /* Parties */
        .parties-section {
            width: 100%;
            border-collapse: collapse;
            margin: 14px 0;
        }
        .parties-section th,
        .parties-section td {
            border: 1px solid #d1d1d1;
            padding: 7px 10px;
            font-size: 10pt;
            background: #f9f9fb;
        }
        .parties-section th {
            background: #eaf0f7;
            font-weight: 600;
            color: #124063;
            text-align: left;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        /* Assets Table */
        .assets-table {
            width: 100%;
            border-collapse: collapse;
            margin: 18px 0;
            font-size: 9.5pt;
        }
        .assets-table th, .assets-table td {
            border: 1px solid #d1d1d1;
            padding: 6px 7px;
        }
        .assets-table th {
            background: #f3f7fa;
            color: #124063;
            text-align: center;
            font-size: 10pt;
            font-weight: bold;
        }
        .assets-table td {
            vertical-align: top;
        }
        /* Notes & Status */
        .notes-section {
            margin-top: 16px;
            margin-bottom: 18px;
            font-size: 10pt;
            text-align: left;
        }
        .notes-section strong {
            color: #124063;
            font-weight: 700;
            margin-bottom: 5px;
            display: block;
        }

        /* Signatures */
        .signature-section {
            margin-top: 30px;
            width: 100%;
            border-collapse: collapse;
        }
        .signature-section td {
            text-align: center;
            font-size: 10pt;
            vertical-align: top;
            padding: 0 10px 0 10px;
        }
        .signature-title {
            font-weight: 500;
            letter-spacing: .5px;
            margin-bottom: 10px;
            color: #124063;
        }
        .signature-space {
            height: 55px;
            margin: 12px auto 4px auto;
            border-bottom: 1px solid #bfc8d0;
            width: 75%;
            position: relative;
        }
        .signer-name {
            font-weight: 600;
            margin-top: 12px;
            display: inline-block;
            color: #2a3143;
            letter-spacing: .4px;
        }
        .stamp {
            position: absolute;
            left: 50%;
            top: -14px;
            transform: translateX(-50%) rotate(-15deg);
            opacity: 0.85;
        }
        .stamp img {
            width: 58px;
        }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 7.7pt;
            color: #888;
            position: fixed;
            bottom: -22px;
            left: 0; right: 0;
            height: 34px;
            letter-spacing: .3px;
        }
    </style>
</head>
<body>
<div class="container">
    {{-- HEADER --}}
    <table class="header-table">
        <tr>
            <td style="width: 92px;">
                @php
                    $logoPath = public_path('assets/img/LogoBSS.png');
                    $approve = public_path('assets/img/approved-stamp.png');
                    $pending = public_path('assets/img/pending-stamp.png');
                    $rejeceted = public_path('assets/img/reject-stamp.png');
                @endphp
                @if(file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo" class="logo">
                @else
                    <div style="width:75px; height:51px; border:1px solid #bfc8d0; text-align:center; line-height:51px; font-size:9pt; color:#888;">
                        LOGO
                    </div>
                @endif
            </td>
            <td class="company-details">
                <div class="company-name">PT. Bina Sarana Sukses</div>
                <p>Landmark Pluit D17, Jl. Pluit Selatan Raya No.2, Jakarta Utara 14450</p>
                <p>Telp: (021) 22677447 &nbsp; | &nbsp; Email: itops@binasaranasukses.com</p>
                <p>Website: <span style="color:#124063">https://binasaranasukses.co.id/</span></p>
            </td>
        </tr>
    </table>

    {{-- TITLE --}}
    <div class="document-title">
        <h2>Berita Acara Request Aset</h2>
        <p>Nomor Dokumen: <b>{{ $bast->request_number }}</b></p>
    </div>

    {{-- INFO --}}
    <table class="bast-info-table">
        <tr>
            <td class="label">Tanggal Permintaan</td>
            <td>: {{ \Carbon\Carbon::parse($bast->request_date)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</td>
        </tr>
        <tr>
            <td class="label">Tanggal Kebutuhan</td>
            <td>: {{ \Carbon\Carbon::parse($bast->needed_date)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</td>
        </tr>
    </table>

    {{-- OPENING PARAGRAPH --}}
    <p style="margin-bottom: 16px; font-size:10pt; text-align:justify;">
        Pada hari ini, <b>{{ \Carbon\Carbon::parse($bast->request_date)->locale('id')->isoFormat('dddd') }}</b>,
        tanggal <b>{{ \Carbon\Carbon::parse($bast->request_date)->locale('id')->translatedFormat('j') }}</b>
        bulan <b>{{ \Carbon\Carbon::parse($bast->request_date)->locale('id')->translatedFormat('F') }}</b>
        tahun <b>{{ \Carbon\Carbon::parse($bast->request_date)->locale('id')->translatedFormat('Y') }}</b>,
        bertempat di <b>{{ $bast->jobsite->name ?? 'Lokasi Tidak Disebutkan' }}</b>,
        telah dilakukan Berita Acara Pengajuan Permintaan Aset dengan detail sebagai berikut:
    </p>

    {{-- PARTIES --}}
    <table class="parties-section">
        <tr>
            <th colspan="2">Data Requestor</th>
        </tr>
        <tr>
            <td style="width:28%">
                <b>Nama</b>
            </td>
            <td>
                {{ $bast->requester->name ?? '____________________' }}
            </td>
        </tr>
        <tr>
            <td>
                <b>NIK</b>
            </td>
            <td>
                {{ $bast->requester->nik ?? '____________________' }}
            </td>
        </tr>
        <tr>
            <td>
                <b>Departement</b>
            </td>
            <td>
                {{ $bast->requester->department->code ?? '____________________' }}
            </td>
        </tr>
        <tr>
            <td>
                <b>Jabatan</b>
            </td>
            <td>
                {{ $bast->requester->position->name ?? '____________________' }}
            </td>
        </tr>
        <tr>
            <td>
                <b>Jobsite</b>
            </td>
            <td>
                {{ $bast->requester->jobsite->code ?? '____________________' }}
            </td>
        </tr>
    </table>

    <p style="margin-top:25px; margin-bottom:10px; font-weight:500;">Aset yang diajukan sebagai berikut:</p>
    @if($bast->ba_budget_outside_name)
        <div style="margin-bottom:15px; margin-top:8px; color:#b52a2a; font-size:10pt; background:#fde8e8; padding:7px 10px; border-left:4px solid #e74c3c;">
            <b>Note:</b> Terdapat dokumen di luar budget terlampir:
            <span style="display:inline-block; margin-left:4px;">
                {{ $bast->ba_budget_outside_name }}
            </span>
            @if($bast->ba_budget_outside_path)
                <br>
                <span style="font-size:9pt;">
                    <i>(File terlampir: <span style="color:#277b66">{{ asset('storage/' . $bast->ba_budget_outside_path)}}</span>)</i>
                </span>
            @endif
        </div>
    @endif

    {{-- ASSETS TABLE --}}
    <table class="assets-table">
        <thead>
        <tr>
            <th style="width:5%;">No.</th>
            <th style="width:20%;">Jenis</th>
            <th style="width:20%;">Tipe/Merek & Model</th>
            <th style="width:30%;">Spesifikasi</th>
            <th style="width:20%;">Status Fulfillment</th>
            <th style="width:5%;">Qty</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($bast->items as $index => $item)
            <tr>
                <td align="center">{{ $index + 1 }}</td>
                <td>{{ $item->assetType->name ?? '-' }}</td>
                <td>
                    <span><b>{{ $item->assetBrand->name ?? '-' }}</b></span>
                    <br>
                    <span>Seri/Model: {{ $item->assetSeries->name ?? '-' }}</span>
                </td>
                <td>
                    {{ $item->assetSeries->specifications && trim($item->assetSeries->specifications) !== '' ? $item->assetSeries->specifications : '-' }}
                </td>
                <td>
                    {{ $item->fulfillment_type ?? '-' }}
                    @if($item->assignedAsset && $item->assignedAsset->asset_number)
                        <br><span style="font-size:9pt;color:#277b66;padding:1px 0;">No.Aset: <b>{{ $item->assignedAsset->asset_number }}</b></span>
                    @endif
                </td>
                <td>
                    {{ $item->quantity }}
                </td>
            </tr>
        @empty
            <tr><td colspan="5" align="center">Tidak ada data aset diajukan.</td></tr>
        @endforelse
        </tbody>
    </table>
    <div class="notes-section">
        <strong>Tujuan Pengajuan:</strong>{{ $bast->purpose }}

        
    </div>
    <div class="notes-section">
        <strong>Status Pengajuan:
            <span style="text-decoration:underline; color:#105e3a;">
                {{ ucwords(strtolower($bast->status)) }}
                @if($lastPending && $lastPending->approver && $lastPending->approver->name)
                    - {{ ucwords(strtolower($lastPending->approver->name)) }}
                @endif
            </span>
        </strong>
        @if($bast->status === 'Rejected')
        <strong>Reject Note:
            <span style="color:#FF0000FF;">
                @if($lastPending->notes)
                    {{ $lastPending->notes }}
                @endif
            </span>
        </strong>
        @endif
        
    </div>

    {{-- SIGNATURES --}}
    <table class="signature-section" style="width: 100%; text-align: center;">
        <tr>
            <td style="text-align: center;">
                <div class="signature-title">Yang Mengajukan</div>
                <div class="signature-space" style="display: flex; justify-content: center; align-items: center;">
                    <div class="stamp" style="display: inline-block;"><img src="{{ $approve }}" alt="Approved"></div>
                </div>
                <span class="signer-name" style="display: block; margin-top: 3px;">{{ $bast->requester->name ?? '(_____________________)' }}</span>
            </td>
            @foreach ($bast->approvals as $index => $item )
                @if($item->status === 'Rejected')
                    <td style="text-align: center;">
                        <div class="signature-title">{{ $item->approver_role_name }}</div>
                        <div class="signature-space" style="display: flex; justify-content: center; align-items: center;">
                            @if($item->status === 'Pending')
                                <div class="stamp"><img src="{{ $pending }}" alt="Pending"></div>
                            @elseif($item->status === 'Rejected')
                                <div class="stamp"><img src="{{ $rejeceted }}" alt="Rejected"></div>
                            @else
                                <div class="stamp"><img src="{{ $approve }}" alt="Approved"></div>
                            @endif
                        </div>
                        <span class="signer-name" style="display: block; margin-top: 3px;">{{ $item->approver->actionBy->name ?? '(_____________________)' }}</span>
                    </td>
                    
                    @break

                @else
                    
                    <td style="text-align: center;">
                        <div class="signature-title">{{ $item->approver_role_name }}</div>
                        <div class="signature-space" style="display: flex; justify-content: center; align-items: center;">
                            @if($item->status === 'Pending')
                                <div class="stamp"><img src="{{ $pending }}" alt="Pending"></div>
                            @elseif($item->status === 'Rejected')
                                <div class="stamp"><img src="{{ $rejeceted }}" alt="Rejected"></div>
                            @else
                                <div class="stamp"><img src="{{ $approve }}" alt="Approved"></div>
                            @endif
                        </div>
                        <span class="signer-name" style="display: block; margin-top: 3px;">{{ $item->actionBy->name ?? $item->approver->name ?? '(_____________________)' }}</span>
                    </td>
                @endif
            @endforeach
        </tr>
    </table>
</div>

{{-- FOOTER --}}
<div class="footer">
    <script type="text/php">
        if (isset($pdf)) {
            $text_page_number = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $font = null; $size = 7.5; $color = [0.5,0.5,0.5];
            $y_position = $pdf->get_height() - 20;
            $pdf->page_text(270, $y_position, $text_page_number, $font, $size, $color);

            $nowFormattedPHP = \Carbon\Carbon::now()->isoFormat('D MMM YYYY HH:mm');
            $custom_text = "Dokumen Request Aset - Dicetak pada: " . $nowFormattedPHP;
            $pdf->page_text(30, $y_position, $custom_text, $font, $size, $color);
        }
    </script>
</div>
</body>
</html>
