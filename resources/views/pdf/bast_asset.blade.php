<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>BAST Aset - {{ $bast->transaction->transaction_number }}</title>
    <style>
        body {
            font-family: 'Helvetica', Arial, sans-serif;
            font-size: 9pt;
            line-height: 1.5;
            color: #333;
            margin: 0;
        }
        @page { size: A4; margin: 12mm 12mm 15mm 12mm; }

        .container { width: 100%; }

        /* Header */
        .header-table {
            width: 100%;
            border-bottom: 1px solid #333;
            margin-bottom: 12px;
            padding-bottom: 6px;
        }
        .header-table td { vertical-align: middle; border: none; }
        .logo { width: 70px; height: auto; display: block; }
        .company-details { text-align: right; }
        .company-details .company-name {
            font-size: 12pt; font-weight: bold; color: #2c3e50; margin-bottom: 2px;
        }
        .company-details p { margin: 0; font-size: 7.5pt; color: #555; }

        /* Title */
        .document-title { text-align: center; margin-bottom: 12px; }
        .document-title h2 {
            font-size: 12pt; margin: 0 0 4px 0;
            text-transform: uppercase; letter-spacing: 1px;
        }
        .document-title p { margin: 0; font-size: 9pt; color: #555; }

        /* Info */
        .bast-info-table { width: 100%; margin-bottom: 8px; font-size: 9pt; }
        .bast-info-table td { padding: 2px 0; }
        .bast-info-table td.label { width: 130px; font-weight: bold; }

        /* Parties */
        .parties-section {
            width: 100%;
            border-collapse: collapse;
            margin: 10px 0;
        }
        .parties-section th, .parties-section td {
            border: 1px solid #ddd;
            padding: 5px 6px;
            font-size: 9pt;
        }
        .parties-section th {
            background-color: #f2f2f2;
            font-weight: bold;
            text-align: center;
        }

        /* Assets table */
        .assets-table {
            width: 100%;
            border-collapse: collapse;
            margin: 12px 0;
            font-size: 8.5pt;
        }
        .assets-table th, .assets-table td {
            border: 1px solid #bbb;
            padding: 4px 5px;
            text-align: left;
        }
        .assets-table th {
            background-color: #e9e9e9;
            text-align: center;
        }

        /* Notes & closing */
        .notes-section, .closing-statement {
            margin-bottom: 12px;
            font-size: 9pt;
            text-align: justify;
        }
        .notes-section strong, .closing-statement strong {
            display: block; margin-bottom: 3px;
        }

        /* Signatures */
        .signature-section {
            margin-top: 20px;
            width: 100%;
        }
        .signature-section td {
            width: 50%;
            text-align: center;
            font-size: 9pt;
            vertical-align: top;
        }
        .signature-space {
            height: 45px;
            margin: 8px auto;
            border-bottom: 1px solid #ccc;
            width: 70%;
            position: relative;
        }
        .signer-name { font-weight: bold; margin-top: 6px; display: inline-block; }

        .stamp {
            position: absolute;
            left: 50%;
            top: -10px;
            transform: translateX(-50%) rotate(-15deg);
            opacity: 0.75;
        }
        .stamp img { width: 60px; }

        /* Footer */
        .footer {
            text-align: center;
            font-size: 7pt;
            color: #888;
            position: fixed;
            bottom: -25px;
            left: 0; right: 0;
            height: 30px;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- HEADER -->
    <table class="header-table">
        <tr>
            <td style="width: 90px;">
                @php
                    $logoPath = public_path('assets/img/LogoBSS.png');
                    $approve = public_path('assets/img/approved-stamp.png');
                    $pending = public_path('assets/img/pending-stamp.png');
                @endphp
                @if(file_exists($logoPath))
                    <img src="{{ $logoPath }}" alt="Logo" class="logo">
                @else
                    <div style="width:70px; height:50px; border:1px solid #ccc;
                                text-align:center; line-height:50px; font-size:8pt; color:grey;">
                        Logo
                    </div>
                @endif
            </td>
            <td class="company-details">
                <div class="company-name">PT. Bina Sarana Sukses</div>
                <p>Landmark Pluit D17, Jl. Pluit Selatan Raya No.2, Jakarta Utara 14450</p>
                <p>Telp: (021) 22677447 | Email: itops@binasaranasukses.com</p>
                <p>Website: https://binasaranasukses.co.id/</p>
            </td>
        </tr>
    </table>

    <!-- TITLE -->
    <div class="document-title">
        <h2>Berita Acara Serah Terima Aset</h2>
        <p>Nomor: {{ $bast->transaction->transaction_number }}</p>
    </div>

    <!-- INFO -->
    <table class="bast-info-table">
        <tr>
            <td class="label">Tanggal Serah Terima</td>
            <td>: {{ \Carbon\Carbon::parse($bast->transaction->created_at)->locale('id')->isoFormat('dddd, D MMMM YYYY') }}</td>
        </tr>
    </table>

    <!-- OPENING PARAGRAPH -->
    <p>
        Pada hari ini, {{ \Carbon\Carbon::parse($bast->transaction->created_at)->locale('id')->isoFormat('dddd') }},
        tanggal {{ \Carbon\Carbon::parse($bast->transaction->created_at)->locale('id')->translatedFormat('j') }}
        bulan {{ \Carbon\Carbon::parse($bast->transaction->created_at)->locale('id')->translatedFormat('F') }}
        tahun {{ \Carbon\Carbon::parse($bast->transaction->created_at)->locale('id')->translatedFormat('Y') }},
        bertempat di {{ $bast->transaction->fromJobsite->name ?? 'Lokasi Tidak Disebutkan' }},
        telah dilakukan serah terima aset oleh:
    </p>

    <!-- PARTIES -->
    <table class="parties-section">
        <tr>
            <th>PIHAK PERTAMA (Menyerahkan)</th>
            <th>PIHAK KEDUA (Menerima)</th>
        </tr>
        <tr>
            <td>
                <p><b>Nama:</b> {{ $bast->transaction->fromUser->name ?? '____________________' }}</p>
                <p><b>NIK:</b> {{ $bast->transaction->fromUser->nik ?? '____________________' }}</p>
                <p><b>Departemen:</b> {{ $bast->transaction->fromUser->department->name ?? '____________________' }}</p>
            </td>
            <td>
                <p><b>Nama:</b> {{ $bast->transaction->toUser->name ?? '____________________' }}</p>
                <p><b>NIK:</b> {{ $bast->transaction->toUser->nik ?? '____________________' }}</p>
                <p><b>Departemen:</b> {{ $bast->transaction->toUser->department->name ?? '____________________' }}</p>
            </td>
        </tr>
    </table>

    <p>Pihak Pertama menyerahkan kepada Pihak Kedua aset dengan rincian sebagai berikut:</p>

    <!-- ASSETS TABLE -->
    <table class="assets-table">
        <thead>
        <tr>
            <th style="width:5%;">No.</th>
            <th style="width:18%;">Nomor Aset</th>
            <th>Deskripsi</th>
            <th style="width:18%;">Serial Number</th>
            <th style="width:18%;">Kondisi</th>
        </tr>
        </thead>
        <tbody>
        @forelse ($bast->transaction->items as $index => $item)
            <tr>
                <td align="center">{{ $index + 1 }}</td>
                <td>{{ $item->asset->asset_number ?? 'N/A' }}</td>
                <td>
                    <b>{{ $item->asset->assetType->name ?? 'Tipe N/A' }}</b> -
                    {{ $item->asset->assetBrand->name ?? 'Merek N/A' }}<br>
                    Seri/Model: {{ $item->asset->assetSeries->name ?? 'N/A' }}
                </td>
                <td>{{ $item->asset->serial_number ?? 'N/A' }}</td>
                <td>
                    {{ $item->asset_condition_at_handover ?? '-' }}
                    @if($item->condition_notes)
                        <br><small><em>Catatan: {{ $item->condition_notes }}</em></small>
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" align="center">Tidak ada aset tercantum.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <!-- NOTES -->
    @if($bast->transaction->notes)
        <div class="notes-section">
            <strong>Catatan Tambahan:</strong>
            <p>{!! nl2br(e($bast->transaction->notes)) !!}</p>
        </div>
    @endif

    <!-- CLOSING -->
    <div class="closing-statement">
        <p>Pihak Kedua telah menerima aset-aset tersebut di atas dari Pihak Pertama dalam kondisi sebagaimana tercantum
            dan telah diperiksa bersama. Sejak tanggal penandatanganan BAST ini, tanggung jawab penuh atas penggunaan,
            pemeliharaan, serta keamanan aset beralih kepada Pihak Kedua.</p>
        <p>Selama belum ada Berita Acara Pengembalian Aset, aset ini menjadi <b>tanggung jawab Pihak Kedua</b> dan
            <b>dilarang</b> dipindahtangankan kecuali melalui IT atau prosedur pengembalian resmi.</p>
    </div>

    <!-- SIGNATURES -->
    <table class="signature-section">
        <tr>
            <td>
                Yang Menyerahkan,<br>Pihak Pertama
                <div class="signature-space">
                    <div class="stamp"><img src="{{ $approve }}" alt="Approved"></div>
                </div>
                <span class="signer-name">{{ $bast->transaction->fromUser->name ?? '(_____________________)' }}</span>
            </td>
            <td>
                Yang Menerima,<br>Pihak Kedua
                <div class="signature-space">
                    @if($bast->status === 'Pre-Used')
                        <div class="stamp"><img src="{{ $pending }}" alt="Pending"></div>
                    @elseif($bast->status === 'Used')
                        <div class="stamp"><img src="{{ $approve }}" alt="Approved"></div>
                    @endif
                </div>
                <span class="signer-name">{{ $bast->transaction->toUser->name ?? '(_____________________)' }}</span>
            </td>
        </tr>
    </table>
</div>

<!-- FOOTER -->
<div class="footer">
    <script type="text/php">
        if (isset($pdf)) {
            $text_page_number = "Halaman {PAGE_NUM} dari {PAGE_COUNT}";
            $font = null; $size = 7; $color = [0.5,0.5,0.5];
            $y_position = $pdf->get_height() - 20;
            $pdf->page_text(270, $y_position, $text_page_number, $font, $size, $color);

            $nowFormattedPHP = \Carbon\Carbon::now()->isoFormat('D MMM YYYY HH:mm');
            $custom_text = "Dokumen BAST Aset - Dicetak pada: " . $nowFormattedPHP;
            $pdf->page_text(30, $y_position, $custom_text, $font, $size, $color);
        }
    </script>
</div>
</body>
</html>
