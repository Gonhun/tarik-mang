@props(['placeholder' => 'Cari...'])

{{--
    Bungkus <select> biasa agar punya kolom pencarian. <select> tetap dipegang Livewire
    (wire:model / wire:change / @error tetap di <select>), widget-nya ada di form-widgets.js.

    <x-search-select placeholder="Cari tipe...">
        <select wire:model="..." class="form-select">...</select>
    </x-search-select>
--}}
<div x-data="searchSelect(@js($placeholder))"
     x-effect="syncFromWire()"
     @keydown.escape.stop="open && close(true)"
     @click.outside="open && close()"
     {{ $attributes->merge(['class' => 'search-select position-relative']) }}>
    <div class="d-none">{{ $slot }}</div>

    <div wire:ignore>
        <button type="button" x-ref="button" class="form-select text-start text-truncate"
                :class="{ 'is-invalid': invalid, 'text-muted': isPlaceholder }"
                :disabled="disabled"
                aria-haspopup="listbox" :aria-expanded="open.toString()"
                @click="toggle()"
                @keydown.arrow-down.prevent="show()"
                x-text="label || ' '"></button>

        <div x-show="open" x-cloak class="dropdown-menu show w-100 p-2 shadow" style="z-index: 1080;">
            <input type="search" x-ref="search" x-model="q" class="form-control form-control-sm mb-2"
                   :placeholder="placeholder" autocomplete="off" aria-label="Cari pilihan"
                   @input="active = 0"
                   @keydown.arrow-down.prevent="move(1)"
                   @keydown.arrow-up.prevent="move(-1)"
                   @keydown.enter.prevent="choose(filtered[active])"
                   @keydown.tab="close()">
            <ul x-ref="list" class="list-unstyled mb-0 overflow-auto" style="max-height: 240px;" role="listbox">
                <template x-for="(opt, i) in filtered" :key="opt.value">
                    <li role="option" :aria-selected="(opt.value === value).toString()"
                        :data-active="(i === active).toString()"
                        class="dropdown-item rounded text-wrap"
                        :class="{ 'active': i === active, 'fw-semibold': opt.value === value, 'disabled': opt.disabled }"
                        style="cursor: pointer;"
                        @mouseenter="active = i"
                        @click="choose(opt)"
                        x-text="opt.text"></li>
                </template>
                <li x-show="filtered.length === 0" class="dropdown-item-text small text-muted">Tidak ada yang cocok</li>
            </ul>
        </div>
    </div>
</div>
