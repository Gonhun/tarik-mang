<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Cetak Label Aset - Zebra ZD230</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        html, body {
            height: 100%;
            margin: 0;
            padding: 0;
            font-family: 'Arial', sans-serif;
            background: #fff;
        }
        .label-container {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            justify-content: flex-start;
            align-items: flex-start;
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
        }
        .asset-label {
            /* Zebra ZD230: 2 inch x 1 inch = 50.8mm x 25.4mm */
            width: 50.8mm;
            height: 25.4mm;
            background: #fff;
            border: 2px solid #222;
            border-radius: 2.5mm;
            box-shadow: 0 1px 2px rgba(0,0,0,0.07);
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: flex-start;
            padding: 2mm 3mm;
            box-sizing: border-box;
            overflow: hidden;
            page-break-inside: avoid;
            margin: 0;
            transition: width 0.2s, height 0.2s;
            position: relative;
        }
        .qr-code {
            flex: 0 0 auto;
            margin-right: 3mm;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 15mm;
            height: 15mm;
            background: #fff;
            border-radius: 1.5mm;
            border: 1px solid #eee;
            box-sizing: border-box;
        }
        .qr-code svg {
            width: 100% !important;
            height: 100% !important;
            display: block;
        }
        .label-info {
            flex: 1 1 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-width: 0;
        }
        .asset-number {
            font-weight: bold;
            font-size: 11px;
            color: #111;
            letter-spacing: 0.5px;
            word-break: break-all;
            line-height: 1.1;
            margin-bottom: 2px;
            text-align: left;
        }
        .asset-name {
            font-size: 8.5px;
            color: #444;
            line-height: 1.1;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        @media print {
            html, body {
                margin: 0;
                padding: 0;
                background: #fff;
            }
            .no-print {
                display: none !important;
            }
            .label-container {
                padding: 0;
                gap: 0;
            }
            .asset-label {
                box-shadow: none !important;
                border: 1px solid #000 !important;
                margin: 0 !important;
            }
        }
        .label-size-form {
            display: flex;
            gap: 10px;
            align-items: center;
            justify-content: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
        }
        .label-size-form input[type="number"] {
            width: 70px;
        }
        @media (max-width: 600px) {
            .label-container {
                flex-direction: column;
                gap: 10px;
            }
            .asset-label {
                width: 98vw !important;
                min-width: 0;
                max-width: 100vw;
                height: auto !important;
                flex-direction: column;
                align-items: flex-start;
                padding: 2vw 2vw;
            }
            .qr-code {
                margin-right: 0;
                margin-bottom: 6px;
                width: 22vw;
                height: 22vw;
                min-width: 40px;
                min-height: 40px;
            }
            .label-info {
                width: 100%;
            }
            .asset-number, .asset-name {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    {{-- Tombol ini tidak akan ikut tercetak --}}
    <div class="no-print" style="padding: 10px; text-align: center;">
        <p>Halaman ini siap untuk dicetak. Gunakan dialog print (Ctrl+P) untuk mencetak atau menyimpan sebagai PDF.</p>
        <form class="label-size-form" id="labelSizeForm" onsubmit="return false;">
            <label>Ukuran Label: 
                <input type="number" id="labelWidth" min="30" step="0.1" value="50.8"> mm x 
                <input type="number" id="labelHeight" min="15" step="0.1" value="25.4"> mm
            </label>
            <button type="button" onclick="applyLabelSize()">Terapkan</button>
        </form>
        <button onclick="window.print()">Cetak Label</button>
        <a href="{{ url()->previous() }}">Kembali</a>
    </div>

    <div class="label-container" id="labelContainer">
        @foreach ($assets as $asset)
            <div class="asset-label" style="background: #fff; border-color: #000; box-shadow: none;">
                <div class="qr-code" data-asset-number="{{ $asset->asset_number }}" style="background: #fff; border-radius: 8px; box-shadow: none; padding: 2px;">
                    {!! QrCode::size(120)->margin(0)->generate($asset->asset_number) !!}
                </div>
                <div class="label-info" style="display: flex; flex-direction: column; justify-content: center;">
                    <div class="asset-number" style="font-weight: bold; color: #000; font-size: 1.1em;  margin-bottom: 2px;">
                        {{ $asset->asset_number }}
                    </div>
                    <div class="asset-name" style="font-size: 0.98em; color: #000; font-style: italic; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                        {{ $asset->assetSeries->name ?? 'Aset' }}
                    </div>
                    @if($asset->assetBrand?->name)
                        <div class="asset-brand" style="font-size: 0.85em; color: #000; margin-top: 1px;">
                            <span style="font-weight: 500;">Brand:</span> {{ $asset->assetBrand->name }}
                        </div>
                    @endif
                    @if($asset->assetType?->name)
                        <div class="asset-type" style="font-size: 0.8em; color: #000;">
                            <span style="font-weight: 500;">Tipe:</span> {{ $asset->assetType->name }}
                        </div>
                    @endif
                </div>
                <span style="position: absolute; bottom: 2mm; right: 3mm; font-size: 0.7em; color: #000; font-style: italic; letter-spacing: 0.5px;">
                    {{ $asset->location ?? '' }}
                </span>
            </div>
        @endforeach
    </div>

    <script>
        // Fungsi untuk mengubah ukuran label dan responsif font/QR
        function applyLabelSize() {
            const width = parseFloat(document.getElementById('labelWidth').value);
            const height = parseFloat(document.getElementById('labelHeight').value);
            const labels = document.querySelectorAll('.asset-label');
            labels.forEach(label => {
                label.style.width = width + 'mm';
                label.style.height = height + 'mm';

                // Responsif font size berdasarkan tinggi label
                // QR code: 60% dari tinggi label, max 90% dari lebar, min 10mm
                const qrDiv = label.querySelector('.qr-code');
                const assetNumberDiv = label.querySelector('.asset-number');
                const assetNameDiv = label.querySelector('.asset-name');

                // Hitung px dari mm (1mm ≈ 3.78px)
                const pxHeight = height * 3.78;
                const pxWidth = width * 3.78;

                // QR code size: 60% dari tinggi label, max 90% dari lebar, min 10mm
                let qrSizeMM = Math.max(10, Math.min(height * 0.6, width * 0.35));
                let qrSize = qrSizeMM * 3.78;

                if (qrDiv && qrDiv.querySelector('svg')) {
                    qrDiv.querySelector('svg').setAttribute('width', qrSize);
                    qrDiv.querySelector('svg').setAttribute('height', qrSize);
                    qrDiv.style.height = qrSizeMM + 'mm';
                    qrDiv.style.width = qrSizeMM + 'mm';
                }

                // Asset number font: 18% dari tinggi label, min 8px, max 20px
                if (assetNumberDiv) {
                    let fontSize = Math.max(8, Math.min(20, pxHeight * 0.18));
                    assetNumberDiv.style.fontSize = fontSize + 'px';
                }

                // Asset name font: 12% dari tinggi label, min 7px, max 14px
                if (assetNameDiv) {
                    let fontSize = Math.max(7, Math.min(14, pxHeight * 0.12));
                    assetNameDiv.style.fontSize = fontSize + 'px';
                }
            });
            // Simpan ke localStorage agar konsisten saat reload (opsional)
            localStorage.setItem('labelWidthMM', width);
            localStorage.setItem('labelHeightMM', height);
        }

        // Saat halaman dimuat, terapkan ukuran label dari localStorage jika ada
        document.addEventListener('DOMContentLoaded', function() {
            const savedWidth = localStorage.getItem('labelWidthMM');
            const savedHeight = localStorage.getItem('labelHeightMM');
            if (savedWidth && savedHeight) {
                document.getElementById('labelWidth').value = savedWidth;
                document.getElementById('labelHeight').value = savedHeight;
            }
            applyLabelSize();
        });

        // Responsif: jika ukuran layar berubah, tetap responsif
        window.addEventListener('resize', function() {
            applyLabelSize();
        });
    </script>
</body>
</html>