<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page { margin: 14mm 12mm 16mm 12mm; }
        body { font-family: 'Helvetica', Arial, sans-serif; font-size: 8.5pt; color: #2b2f33; }
        .kop td { vertical-align: middle; }
        .kop img { height: 34px; }
        h1 { font-size: 13pt; color: #1f3a5f; margin: 10px 0 2px; }
        .meta { color: #6b7280; font-size: 7.5pt; margin-bottom: 10px; }
        table.data { width: 100%; border-collapse: collapse; }
        table.data th { background: #1f3a5f; color: #fff; text-align: left; padding: 5px; font-size: 7.6pt; text-transform: uppercase; }
        table.data td { padding: 4px 5px; border-bottom: 1px solid #e2e6eb; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f8fafc; }
        .footer { position: fixed; bottom: -10mm; left: 0; right: 0; font-size: 7pt; color: #8a94a3; border-top: 1px solid #e2e6eb; padding-top: 3px; }
        .footer .pg:after { content: counter(page); }
    </style>
</head>
<body>
<div class="footer">{{ $title }} · dibuat {{ now()->locale('id')->isoFormat('D MMM YYYY HH:mm') }} oleh {{ $user->name }} · AMS IT OPS <span style="float:right">Hal. <span class="pg"></span></span></div>
<table class="kop" width="100%"><tr>
    <td>@if (file_exists(public_path('assets/img/LogoBSS.png')))<img src="{{ public_path('assets/img/LogoBSS.png') }}">@endif</td>
    <td style="text-align:right; color:#1f3a5f; font-weight:bold">PT. Bina Sarana Sukses<br><span style="font-weight:normal; color:#6b7280; font-size:7.5pt">AMS IT Operations</span></td>
</tr></table>
<h1>{{ $title }}</h1>
<div class="meta">{{ count($rows) }} baris</div>
<table class="data">
    <thead><tr><th style="width:24px">#</th>@foreach ($columns as $label)<th>{{ $label }}</th>@endforeach</tr></thead>
    <tbody>
    @forelse ($rows as $i => $row)
        <tr><td>{{ $i + 1 }}</td>@foreach (array_keys($columns) as $k)<td>{{ $row[$k] ?? '' }}</td>@endforeach</tr>
    @empty
        <tr><td colspan="{{ count($columns) + 1 }}" style="text-align:center; color:#6b7280">Tidak ada data.</td></tr>
    @endforelse
    </tbody>
</table>
</body>
</html>
