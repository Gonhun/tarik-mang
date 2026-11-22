<style>
    @page { size: A4; margin: 14mm 14mm 18mm 14mm; }
    * { box-sizing: border-box; }
    body {
        font-family: 'Helvetica', Arial, sans-serif;
        font-size: 9pt;
        line-height: 1.45;
        color: #2b2f33;
        margin: 0;
    }
    p { margin: 0 0 6px 0; }
    table { border-collapse: collapse; width: 100%; }

    /* Kop */
    .kop td { vertical-align: middle; }
    .kop .logo { height: 46px; }
    .kop .company { text-align: right; }
    .kop .company-name { font-size: 12.5pt; font-weight: bold; color: #1f3a5f; }
    .kop .company small { display: block; font-size: 7.3pt; color: #6b7280; line-height: 1.35; }
    .kop-line { height: 3px; background: #1f3a5f; margin: 8px 0 1px 0; }
    .kop-line-thin { height: 1px; background: #1f3a5f; margin-bottom: 14px; }

    /* Judul */
    .doc-title { text-align: center; margin-bottom: 12px; }
    .doc-title h1 {
        font-size: 13pt; margin: 0; letter-spacing: 1.5px;
        text-transform: uppercase; color: #1f3a5f;
    }
    .doc-title .number { margin-top: 3px; font-size: 9pt; color: #4b5563; }

    /* Ringkasan */
    .meta { margin-bottom: 14px; border: 1px solid #d9dee5; }
    .meta td { padding: 6px 8px; width: 25%; vertical-align: top; border-right: 1px solid #e5e8ec; background: #f6f8fa; }
    .meta td:last-child { border-right: none; }
    .meta .k { display: block; font-size: 6.8pt; text-transform: uppercase; letter-spacing: .6px; color: #6b7280; }
    .meta .v { display: block; font-weight: bold; color: #1f2937; font-size: 8.8pt; }

    /* Bagian */
    .section { margin-bottom: 12px; }
    .section-title {
        font-size: 8pt; font-weight: bold; letter-spacing: .8px; text-transform: uppercase;
        color: #1f3a5f; border-bottom: 1px solid #c9d2dd; padding-bottom: 3px; margin-bottom: 7px;
    }
    .lead { text-align: justify; }

    /* Para pihak */
    .parties td.party { width: 50%; vertical-align: top; padding: 0; }
    .parties td.gap { width: 10px; }
    .party-box { border: 1px solid #d9dee5; }
    .party-head { background: #eef2f7; padding: 5px 8px; font-weight: bold; font-size: 8.3pt; color: #1f3a5f; }
    .party-body { padding: 6px 8px; }
    .party-body table td { padding: 1.5px 0; vertical-align: top; font-size: 8.8pt; }
    .party-body td.k { width: 72px; color: #6b7280; }

    /* Tabel aset */
    .assets th {
        background: #1f3a5f; color: #fff; font-size: 7.8pt; font-weight: bold;
        text-transform: uppercase; letter-spacing: .4px; padding: 5px 6px; text-align: left;
    }
    .assets td { padding: 5px 6px; border-bottom: 1px solid #e2e6eb; vertical-align: top; font-size: 8.6pt; }
    .assets tr.alt td { background: #f8fafc; }
    .assets .c { text-align: center; }
    .assets .muted { color: #6b7280; font-size: 7.8pt; }
    .assets .mono { font-family: 'Courier', monospace; font-size: 8.4pt; }

    /* Catatan */
    .note { border-left: 3px solid #c9d2dd; background: #f8fafc; padding: 6px 9px; font-size: 8.6pt; }
    .statement { text-align: justify; }
    .statement li { margin-bottom: 4px; }
    .statement ol { margin: 0; padding-left: 16px; }

    /* Status */
    .pill { font-weight: bold; padding: 1px 6px; border-radius: 3px; font-size: 8pt; }
    .pill-success { background: #dcfce7; color: #166534; }
    .pill-warning { background: #fef3c7; color: #92400e; }
    .pill-danger  { background: #fee2e2; color: #991b1b; }
    .pill-muted   { background: #eef0f3; color: #4b5563; }

    /* Tanda tangan */
    .signs { margin-top: 6px; page-break-inside: avoid; }
    .signs td.cell { vertical-align: top; padding: 0 5px 10px 5px; text-align: center; }
    .sign-box { border: 1px solid #d9dee5; padding: 6px 6px 7px 6px; }
    .sign-role { font-size: 7.8pt; color: #4b5563; height: 22px; }
    .sign-area { height: 62px; text-align: center; }
    .sign-area img { height: 58px; margin-top: 2px; }
    .sign-area .na { padding-top: 22px; color: #9ca3af; font-size: 7.6pt; font-style: italic; }
    .sign-name { border-top: 1px solid #9aa4b1; padding-top: 4px; font-weight: bold; font-size: 8.6pt; margin: 0 6px; }
    .sign-date { font-size: 7.2pt; color: #6b7280; }

    /* Footer tiap halaman */
    .footer {
        position: fixed; left: 0; right: 0; bottom: -11mm; height: 8mm;
        font-size: 7pt; color: #8a94a3; border-top: 1px solid #e2e6eb; padding-top: 3px;
    }
    .footer .pg:after { content: counter(page); }
</style>
