@php
    // Ukuran label (mm) & ukuran QR (px saat dirender SVG; diskalakan CSS).
    $dims = ['small' => [50, 25, 19], 'medium' => [70, 35, 27], 'large' => [100, 50, 38]];
    [$w, $h, $qr] = $dims[$size];
@endphp
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Label aset ({{ $assets->count() }})</title>
    <style>
        :root { --w: {{ $w }}mm; --h: {{ $h }}mm; --qr: {{ $qr }}mm; }
        * { box-sizing: border-box; }
        html, body { margin: 0; padding: 0; background: #fff; color: #111; font-family: Arial, Helvetica, sans-serif; }
        .toolbar { position: sticky; top: 0; z-index: 2; display: flex; gap: 12px; align-items: center; padding: 10px 16px; background: #f4f5fb; border-bottom: 1px solid #ddd; font-size: 14px; }
        .toolbar button { padding: 6px 14px; border: 0; border-radius: 6px; background: #696cff; color: #fff; font-size: 14px; cursor: pointer; }
        .sheet { display: flex; flex-wrap: wrap; gap: {{ $layout === 'sheet' ? '3mm' : '0' }}; padding: {{ $layout === 'sheet' ? '8mm' : '0' }}; }
        .label { width: var(--w); height: var(--h); padding: 1.5mm 2mm; display: flex; align-items: center; gap: 2mm; overflow: hidden; border: {{ $layout === 'sheet' ? '0.3mm dashed #999' : '0' }}; page-break-inside: avoid; break-inside: avoid; }
        .qr { flex: 0 0 var(--qr); width: var(--qr); height: var(--qr); }
        .qr svg { width: 100%; height: 100%; display: block; }
        .info { min-width: 0; flex: 1; line-height: 1.15; }
        .no { font-weight: 700; font-size: {{ $size === 'small' ? '2.9mm' : ($size === 'medium' ? '3.6mm' : '4.6mm') }}; word-break: break-all; }
        .meta { font-size: {{ $size === 'small' ? '2mm' : ($size === 'medium' ? '2.5mm' : '3mm') }}; color: #333; margin-top: .6mm; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .owner { font-size: {{ $size === 'small' ? '1.7mm' : '2.1mm' }}; color: #555; margin-top: .8mm; }
        @media print {
            .toolbar { display: none; }
            @page { size: {{ $layout === 'sheet' ? 'A4' : $w . 'mm ' . $h . 'mm' }}; margin: {{ $layout === 'sheet' ? '0' : '0' }}; }
            @if ($layout === 'roll') .label { page-break-after: always; break-after: page; } .label:last-child { page-break-after: auto; break-after: auto; } .sheet { display: block; } @endif
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <strong>{{ $assets->count() }} label</strong>
        <span>{{ $w }} × {{ $h }} mm · {{ $layout === 'sheet' ? 'kertas A4' : 'printer label (1 label per halaman)' }}</span>
        <button type="button" onclick="window.print()">Cetak</button>
    </div>
    <div class="sheet">
        @foreach ($assets as $a)
            <div class="label">
                <div class="qr">{!! QrCode::size(200)->margin(0)->generate($a->asset_number) !!}</div>
                <div class="info">
                    <div class="no">{{ $a->asset_number }}</div>
                    {{-- Isi label: nomor aset, barang, SN (tanpa site) --}}
                    <div class="meta">{{ $a->assetSeries->name ?? ($a->assetType->name ?? '-') }}</div>
                    @if ($size !== 'small' && ($a->assetType || $a->assetBrand))
                        <div class="meta">{{ $a->assetType->name ?? '' }}{{ $a->assetBrand ? ' · ' . $a->assetBrand->name : '' }}</div>
                    @endif
                    <div class="meta">SN: {{ $a->serial_number ?: '-' }}</div>
                    @if ($size === 'large' && $a->entity)
                        <div class="owner">Milik {{ $a->entity->name }}</div>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    <script>window.addEventListener('load', () => setTimeout(() => window.print(), 300));</script>
</body>
</html>
