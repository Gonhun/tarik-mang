{{--
    Kolom pengesahan. Param: $signers = [[role, name, state, date, note], ...]
    state: approved | pending | rejected | none
--}}
@php
    $stamps = [
        'approved' => public_path('assets/img/approved-stamp.png'),
        'pending' => public_path('assets/img/pending-stamp.png'),
        'rejected' => public_path('assets/img/reject-stamp.png'),
    ];
    $perRow = count($signers) <= 2 ? 2 : 3;
    $width = round(100 / $perRow, 2) . '%';
@endphp
<table class="signs">
    @foreach (array_chunk($signers, $perRow) as $row)
        <tr>
            @foreach ($row as $s)
                <td class="cell" style="width: {{ $width }};">
                    <div class="sign-box">
                        <div class="sign-role">{{ $s['role'] }}</div>
                        <div class="sign-area">
                            @if (isset($stamps[$s['state']]) && file_exists($stamps[$s['state']]))
                                <img src="{{ $stamps[$s['state']] }}" alt="{{ $s['state'] }}">
                            @elseif ($s['state'] === 'none')
                                <div class="na">{{ $s['note'] ?? 'Tidak diperlukan' }}</div>
                            @endif
                        </div>
                        <div class="sign-name">{{ $s['name'] ?: '(............................)' }}</div>
                        <div class="sign-date">{{ $s['date'] ?? ' ' }}</div>
                    </div>
                </td>
            @endforeach
            @for ($i = count($row); $i < $perRow; $i++)
                <td class="cell" style="width: {{ $width }};"></td>
            @endfor
        </tr>
    @endforeach
</table>
