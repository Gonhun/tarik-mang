{{-- Footer tiap halaman. Param: $docLabel, $docNumber --}}
<div class="footer">
    <table>
        <tr>
            <td>{{ $docLabel }} {{ $docNumber }} &nbsp;·&nbsp; Dicetak {{ now()->locale('id')->isoFormat('D MMM YYYY HH:mm') }} dari AMS IT OPS</td>
            <td style="text-align: right; width: 80px;">Hal. <span class="pg"></span></td>
        </tr>
    </table>
</div>
