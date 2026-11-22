{{-- Kop surat + judul + ringkasan. Param: $docTitle, $docNumber, $meta (label => nilai) --}}
@php $logoPath = public_path('assets/img/LogoBSS.png'); @endphp
<table class="kop">
    <tr>
        <td style="width: 120px;">
            @if (file_exists($logoPath))
                <img src="{{ $logoPath }}" alt="Logo" class="logo">
            @endif
        </td>
        <td class="company">
            <div class="company-name">PT. Bina Sarana Sukses</div>
            <small>Landmark Pluit D17, Jl. Pluit Selatan Raya No.2, Jakarta Utara 14450</small>
            <small>Telp (021) 22677447 &nbsp;·&nbsp; itops@binasaranasukses.com &nbsp;·&nbsp; binasaranasukses.co.id</small>
        </td>
    </tr>
</table>
<div class="kop-line"></div>
<div class="kop-line-thin"></div>

<div class="doc-title">
    <h1>{{ $docTitle }}</h1>
    <div class="number">Nomor: <b>{{ $docNumber }}</b></div>
</div>

<table class="meta">
    <tr>
        @foreach ($meta as $label => $value)
            <td><span class="k">{{ $label }}</span><span class="v">{!! $value !!}</span></td>
        @endforeach
    </tr>
</table>
