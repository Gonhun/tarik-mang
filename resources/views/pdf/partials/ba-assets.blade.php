{{-- Tabel rincian aset. Param: $items (AssetTransactionItem dengan relasi asset) --}}
<table class="assets">
    <thead>
        <tr>
            <th class="c" style="width: 6%;">No</th>
            <th style="width: 19%;">No. Aset</th>
            <th>Deskripsi</th>
            <th style="width: 20%;">Serial Number</th>
            <th style="width: 23%;">Kondisi / Catatan</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($items as $i => $item)
            @php $a = $item->asset; @endphp
            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                <td class="c">{{ $i + 1 }}</td>
                <td>
                    <b>{{ $a->asset_number ?? '-' }}</b>
                    @if ($a?->old_asset_number)
                        <div class="muted">Lama: {{ $a->old_asset_number }}</div>
                    @endif
                </td>
                <td>
                    <b>{{ $a->assetType->name ?? '-' }}</b>
                    <div>{{ trim(($a->assetBrand->name ?? '') . ' ' . ($a->assetSeries->name ?? '')) ?: '-' }}</div>
                </td>
                <td class="mono">{{ $a->serial_number ?: '-' }}</td>
                <td>{{ $item->condition_notes ?: '-' }}</td>
            </tr>
        @empty
            <tr><td colspan="5" class="c muted">Tidak ada aset tercantum.</td></tr>
        @endforelse
    </tbody>
</table>
