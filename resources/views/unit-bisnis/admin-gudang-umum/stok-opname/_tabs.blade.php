@php
    $tabs = [
        [
            'key' => 'process',
            'label' => 'Proses Opname',
            'url' => route('unit-bisnis.admin-gu.stok-opname.index'),
        ],
        [
            'key' => 'drafts',
            'label' => 'Draf Opname',
            'url' => route('unit-bisnis.admin-gu.stok-opname.drafts'),
        ],
    ];
@endphp

<nav class="mb-5 flex flex-wrap gap-2 border-b-2 border-[var(--surface-border)]" aria-label="Navigasi Stok Opname">
    @foreach ($tabs as $tab)
        <a href="{{ $tab['url'] }}" @if ($activeTab === $tab['key']) aria-current="page" @endif
            class="btn min-h-10 max-[520px]:w-full {{ $activeTab === $tab['key'] ? 'btn-primary' : 'btn-outline' }}">
            {{ $tab['label'] }}
        </a>
    @endforeach
</nav>
