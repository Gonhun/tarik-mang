<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Cetak Label - {{ $asset->asset_number }}</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('assets/img/favicon/favicon.ico') }}" />
    <style>
        @page {
            size: 100mm 50mm;
            margin: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Segoe UI', 'Arial', sans-serif;
            background: linear-gradient(135deg, #f8fafc 0%, #e0e7ef 100%);
        }

        .label {
            width: 100mm;
            height: 50mm;
            padding: 2mm 7mm 5mm 7mm;
            box-sizing: border-box;
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background: linear-gradient(120deg, #fff 80%, #e3e9f7 100%);
            border-radius: 8mm 2mm 8mm 2mm;
            border: 2.5px solid #1a237e;
            box-shadow: 0 4px 18px rgba(26, 35, 126, 0.13), 0 1.5px 6px rgba(0, 0, 0, 0.07);
            position: relative;
        }

        .label::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, #1a237e 0 20px, #fff 20px 40px);
            opacity: 0.18;
            border-radius: 8mm 8mm 0 0;
        }

        .label::after {
            content: "";
            position: absolute;
            left: 0;
            bottom: 0;
            width: 100%;
            height: 8px;
            background: linear-gradient(90deg, #1a237e 0 14px, #fff 14px 28px);
            opacity: 0.13;
            border-radius: 0 0 8mm 8mm;
        }

        .label-content-center {
            display: flex;
            flex-direction: row;
            align-items: center;
            justify-content: center;
            width: 100%;
            height: 100%;
            position: relative;
            z-index: 2;
        }

        .info-section {
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: left;
            flex-grow: 1;
            padding-left: 0;
            padding-right: 0;
            margin-left: 0;
            margin-right: 0;
            height: 100%;
            z-index: 2;
        }

        .asset-number {
            font-weight: bold;
            font-size: 16pt;
            color: #1a237e;
            letter-spacing: 2.5px;
            text-transform: uppercase;
            text-shadow: 0 2px 0 #fff, 0 3px 0 #e3e9f7;
            border-bottom: 2px dashed #1a237e;
            padding-bottom: 3px;
            margin-bottom: 10px;
            background: linear-gradient(90deg, #e3e9f7 60%, #fff 100%);
            border-radius: 0 0 8px 0;
            box-decoration-break: clone;
        }

        .asset-name {
            font-size: 12pt;
            color: #263238;
            margin-top: 0;
            font-style: italic;
            font-weight: 600;
            letter-spacing: 0.7px;
            text-overflow: ellipsis;
            white-space: nowrap;
            overflow: hidden;
            margin-bottom: 2px;
        }

        .asset-brand,
        .asset-type,
        .asset-location {
            color: #374151;
            font-size: 11pt;
            margin-top: 2px;
            font-weight: 500;
            letter-spacing: 0.3px;
            display: flex;
            align-items: center;
        }

        .asset-brand span,
        .asset-type span,
        .asset-location span {
            font-weight: 700;
            color: #1a237e;
            margin-right: 4px;
        }

        .asset-type {
            font-size: 10pt;
        }

        .asset-location {
            font-size: 9.5pt;
            font-style: italic;
            color: #0d1333;
            margin-top: 6px;
        }

        .qr-section {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            height: 100%;
            margin-left: 22px;
            margin-right: 0;
            position: relative;
            z-index: 2;
        }

        .qr-code {
            background: #fff;
            border-radius: 16px;
            box-shadow: 0 2px 8px rgba(26, 35, 126, 0.10);
            padding: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 2px solid #1a237e;
            width: 70px;
            height: 70px;
            transition: box-shadow 0.2s;
        }

        .qr-code:hover {
            box-shadow: 0 4px 16px rgba(26, 35, 126, 0.18);
        }

        .label-footer {
            position: absolute;
            bottom: 7mm;
            right: 10mm;
            font-size: 10pt;
            color: #1a237e;
            font-style: italic;
            letter-spacing: 0.7px;
            opacity: 0.8;
            z-index: 3;
        }

        /* Accent circle for visual interest */
        .label-accent {
            position: absolute;
            top: 10mm;
            left: -18mm;
            width: 38mm;
            height: 38mm;
            background: radial-gradient(circle at 60% 40%, #e3e9f7 60%, #fff0 100%);
            border-radius: 50%;
            opacity: 0.25;
            z-index: 1;
        }

        .label-accent2 {
            position: absolute;
            bottom: -12mm;
            right: -12mm;
            width: 28mm;
            height: 28mm;
            background: radial-gradient(circle at 40% 60%, #1a237e 40%, #fff0 100%);
            border-radius: 50%;
            opacity: 0.10;
            z-index: 1;
        }

        /* Responsive for print preview */
        @media (max-width: 600px) {
            .label {
                width: 98vw !important;
                height: auto !important;
                min-width: 0;
                min-height: 0;
                padding: 2vw 2vw;
                border-radius: 4vw;
            }

            .qr-code {
                width: 22vw;
                height: 22vw;
                min-width: 40px;
                min-height: 40px;
            }

            .asset-number {
                font-size: 6vw;
            }

            .asset-name {
                font-size: 4vw;
            }
        }
    </style>
</head>

<body onload="window.print()">
    <div
        style="width:100vw; height:100vh; display:flex; align-items:center; justify-content:center; background:none; margin:0; padding:0;">
        <div class="label">
            <div class="label-accent"></div>
            <div class="label-accent2"></div>
            <div class="label-content-center">
                <div class="info-section">
                    <div class="asset-number">
                        {{ $asset->asset_number }}
                    </div>
                    <div class="asset-name">
                        {{ $asset->assetSeries->name ?? 'Aset' }}

                    </div>
                    <div class="asset-brand">
                        <i>{{ $asset->serial_number ?? '-' }}</i>
                    </div>
                    @if($asset->assetBrand?->name)
                        <div class="asset-brand">
                            <span>Brand:</span> {{ $asset->assetBrand->name }}
                        </div>
                    @endif
                    @if($asset->assetType?->name)
                        <div class="asset-type">
                            <span>Tipe:</span> {{ $asset->assetType->name }}
                        </div>
                    @endif

                </div>
                <div class="qr-section">
                    <div class="qr-code">
                        {!! QrCode::size(70)->margin(0)->color(26, 35, 126)->backgroundColor(255, 255, 255)->generate($asset->asset_number) !!}
                    </div>
                </div>
            </div>

        </div>
        <div style="
        position: absolute;
        bottom: 0mm;
        left: 5mm;
        right: 2mm;
        width: auto;
        max-width: calc(100mm - 14mm);
        text-align: center;
        font-size: 2.2mm;
        color: #444;
        z-index: 999;
        line-height: 1.3;
        word-break: break-word;
        overflow-wrap: break-word;
        white-space: normal;
        /* Prevent page break */
        page-break-inside: avoid;
        page-break-before: avoid;
        page-break-after: avoid;">
            <span>
                Label ini adalah milik perusahaan {{ $asset->entity->name }}. Dilarang memindahkan, mengubah, atau
                merusak label tanpa izin resmi. Jika ditemukan, mohon menghubungi IT.
            </span>
        </div>
    </div>

</body>

</html>