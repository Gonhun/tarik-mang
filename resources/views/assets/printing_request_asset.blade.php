<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Transaksi - {{ $transaction->transaction_number }}</title>
    <style>
        :root {
            --primary: #222e3c; /* biru gelap formal */
            --secondary: #f8f9fa; /* abu muda */
            --accent: #444; /* abu tua */
            --border: #b0b4b9; /* abu border */
            --header-bg: #f4f6f8; /* abu header */
            --approved: #2e7d32; /* hijau gelap */
            --rejected: #b71c1c; /* merah gelap */
            --table-header: #e9ecef; /* abu table header */
            --table-even: #f8f9fa; /* abu table even */
        }
        body {
            font-family: 'Segoe UI', 'Arial', sans-serif;
            font-size: 13px;
            line-height: 1.7;
            background: var(--secondary);
            color: #222;
        }
        .container {
            width: 19cm;
            margin: 0 auto;
            background: #fff;
            box-shadow: 0 2px 8px rgba(34, 46, 60, 0.06);
            border-radius: 8px;
            padding-bottom: 30px;
            border: 1px solid var(--border);
        }
        .header {
            text-align: center;
            border-bottom: 2px solid var(--primary);
            padding-bottom: 12px;
            margin-bottom: 24px;
            padding-top: 24px;
            background: var(--header-bg);
            border-top-left-radius: 8px;
            border-top-right-radius: 8px;
        }
        .header .logo {
            display: block;
            margin: 0 auto 12px auto;
            height: 56px;
        }
        .header h3 {
            margin: 0;
            text-transform: uppercase;
            color: var(--primary);
            letter-spacing: 1px;
            font-size: 1.4em;
            font-weight: bold;
        }
        .header p {
            margin: 6px 0 0 0;
            font-size: 1em;
            color: var(--accent);
            font-weight: 500;
            letter-spacing: 0.2px;
        }
        .details-table {
            width: 100%;
            margin-bottom: 22px;
        }
        .details-table td {
            padding: 4px 7px;
        }
        .details-table tr td:first-child {
            color: var(--primary);
            font-weight: 600;
            width: 160px;
            background: #f4f6f8;
            border-radius: 4px 0 0 4px;
        }
        .details-table tr td:last-child {
            color: #222;
            background: #fff;
            border-radius: 0 4px 4px 0;
        }
        .items-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-bottom: 32px;
            box-shadow: 0 1px 4px #222e3c10;
            border-radius: 6px;
            overflow: hidden;
        }
        .items-table th, .items-table td {
            border: 1px solid var(--border);
            padding: 8px 7px;
            text-align: left;
        }
        .items-table th {
            background-color: var(--table-header);
            color: var(--primary);
            font-weight: 600;
            font-size: 1em;
            letter-spacing: 0.2px;
        }
        .items-table tbody tr:nth-child(even) {
            background: var(--table-even);
        }
        .items-table tbody tr:nth-child(odd) {
            background: #fff;
        }
        .signature-section {
            width: 100%;
            margin-top: 40px;
            display: table;
        }
        .signature-box {
            display: table-cell;
            width: 33.33%;
            text-align: center;
            vertical-align: top;
            padding: 0 10px;
        }
        .signature-box .name {
            margin-top: 60px;
            text-decoration: underline;
        }
        .no-print {
            text-align: center;
            margin-bottom: 18px;
        }
        .no-print button, .no-print a {
            background: var(--primary);
            color: #fff;
            border: none;
            padding: 7px 18px;
            border-radius: 4px;
            margin: 0 5px;
            text-decoration: none;
            font-size: 1em;
            cursor: pointer;
            font-weight: 600;
            box-shadow: 0 1px 4px #222e3c10;
            transition: background 0.2s;
        }
        .no-print button:hover, .no-print a:hover {
            background: #1a2330;
        }
        .signature-box p {
            color: var(--primary);
            font-weight: 500;
            font-size: 1em;
        }
        .signature-box .approver-role {
            color: var(--accent);
            font-size: 0.98em;
            font-weight: 600;
            letter-spacing: 0.2px;
        }
        .signature-box .approver-name {
            color: var(--primary);
            font-weight: bold;
            text-decoration: underline;
            font-size: 1em;
        }
        .signature-box img[alt="Approved"] {
            filter: grayscale(0.2) brightness(0.95);
        }
        .signature-box img[alt="Rejected"] {
            filter: grayscale(0.2) brightness(0.95);
        }
        @media print {
            .no-print {
                display: none;
            }
            .container {
                box-shadow: none;
                border-radius: 0;
                background: #fff;
                border: none;
            }
            .header {
                background: none;
            }
        }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print">
        <button onclick="window.print()">Cetak Dokumen</button>
        <a href="{{ url()->previous() }}">Kembali</a>
    </div>

    <div class="container">
        <div class="header">
            <img src="{{ asset('assets/img/LogoBSS.png') }}" alt="Logo" class="logo">
            <h3>Approval Transaksi Aset</h3>
            <p>Nomor: {{ $transaction->transaction_number }}</p>
        </div>

        <table class="details-table">
            <tr>
                <td>Tanggal Transaksi</td>
                <td>: {{ \Carbon\Carbon::parse($transaction->transaction_date)->isoFormat('D MMMM YYYY') }}</td>
            </tr>
            <tr>
                <td>Tipe Transaksi</td>
                <td>: {{ $transaction->transaction_type }}</td>
            </tr>
            <tr>
                <td>Dari Lokasi</td>
                <td>: {{ $transaction->fromJobsite->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Ke Lokasi</td>
                <td>: {{ $transaction->toJobsite->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td>Dibuat Oleh</td>
                <td>: {{ $transaction->creator->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td style="vertical-align: top;">Catatan</td>
                <td style="vertical-align: top;">: {{ $transaction->notes ?? '-' }}</td>
            </tr>
        </table>

        <h4 style="color:var(--primary); margin-bottom:10px; font-weight:bold; font-size:1.08em; letter-spacing:0.2px;">Daftar Aset:</h4>
        <table class="items-table">
            <thead>
                <tr>
                    <th>No.</th>
                    <th>No. Aset</th>
                    <th>Tipe Aset</th>
                    <th>Merek & Seri</th>
                    <th>Serial Number</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($transaction->items as $index => $item)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $item->asset->asset_number ?? 'N/A' }}</td>
                        <td>{{ $item->asset->assetType->name ?? 'N/A' }}</td>
                        <td>{{ $item->asset->assetBrand->name ?? '' }} {{ $item->asset->assetSeries->name ?? '' }}</td>
                        <td>{{ $item->asset->serial_number ?? 'N/A' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="signature-section">
            @foreach ($transaction->approvals as $approval)
               @if ($approval->approver_role_name == 'Kasi IT')
                <div class="signature-box">
                    <p>Disetujui Oleh,</p>
                    @if ($approval->status == 'Approved')
                        <img src="{{ asset('assets/img/approved-stamp.png') }}" alt="Approved" style="height:56px;">
                    @elseif ($approval->status == 'Rejected')
                        <img src="{{ asset('assets/img/reject-stamp.png') }}" alt="Rejected" style="height:56px;">
                    @elseif ($approval->status == 'Pending')
                        <img src="{{ asset('assets/img/pending-stamp.png') }}" alt="Pending" style="height:56px;">
                    @else
                        {{-- <img src="{{ asset('assets/img/pending-stamp.png') }}" alt="Pending" style="height:56px;"> --}}
                    @endif
                    <p >( <span class="approver-name">{{ $approval->approver->name ?? 'N/A' }}</span> )</p>
                    <p class="approver-role">{{$approval->approver_role_name}}</p>
                </div>
                @elseif ($approval->approver_role_name == 'Cost Control')
                <div class="signature-box">
                    <p>Diketahui Oleh,</p>
                    @if ($approval->status == 'Approved')
                        <img src="{{ asset('assets/img/approved-stamp.png') }}" alt="Approved" style="height:56px;">
                    @elseif ($approval->status == 'Rejected')
                        <img src="{{ asset('assets/img/reject-stamp.png') }}" alt="Rejected" style="height:56px;">
                    @elseif ($approval->status == 'Pending')
                        <img src="{{ asset('assets/img/pending-stamp.png') }}" alt="Pending" style="height:56px;">
                    @else
                        {{-- <img src="{{ asset('assets/img/pending-stamp.png') }}" alt="Pending" style="height:56px;"> --}}
                    @endif
                        <p >( <span class="approver-name">{{ $approval->approver->name ?? 'N/A' }}</span> )</p>
                    <p class="approver-role">{{$approval->approver_role_name}}</p>
                </div>
                @else
                <div class="signature-box">
                    <p>Diproses Oleh,</p>
                    @if ($approval->status == 'Approved')
                        <img src="{{ asset('assets/img/approved-stamp.png') }}" alt="Approved" style="height:56px;">
                    @elseif ($approval->status == 'Rejected')
                        <img src="{{ asset('assets/img/reject-stamp.png') }}" alt="Rejected" style="height:56px;">
                    @elseif ($approval->status == 'Pending')
                        <img src="{{ asset('assets/img/pending-stamp.png') }}" alt="Pending" style="height:56px;">
                    @else
                        {{-- <img src="{{ asset('assets/img/pending-stamp.png') }}" alt="Pending" style="height:56px;"> --}}
                    @endif
                    <p >( <span class="approver-name">{{ $approval->approver->name ?? 'N/A' }}</span> )</p>
                    <p class="approver-role">{{$approval->approver_role_name}}</p>
                </div>
               @endif
            @endforeach
        </div>
    </div>

</body>
</html>