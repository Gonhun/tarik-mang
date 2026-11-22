{{-- Kotak satu pihak. Param: $title, $rows (label => nilai) --}}
<div class="party-box">
    <div class="party-head">{{ $title }}</div>
    <div class="party-body">
        <table>
            @foreach ($rows as $k => $v)
                <tr><td class="k">{{ $k }}</td><td>: {{ filled($v) ? $v : '-' }}</td></tr>
            @endforeach
        </table>
    </div>
</div>
