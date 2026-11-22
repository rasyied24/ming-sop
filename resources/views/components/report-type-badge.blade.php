@props(['type'])

@php
    // Warna sengaja berbeda dari badge status (kuning, biru, hijau) supaya tidak tertukar.
    $classes = match ($type->value) {
        'kendala_sop' => 'bg-violet-50 text-violet-700',
        'ketidaksesuaian' => 'bg-teal-50 text-teal-700',
        'dugaan_pelanggaran' => 'bg-rose-50 text-rose-700',
    };
@endphp

<span {{ $attributes->class(['inline-block whitespace-nowrap rounded-full px-2.5 py-0.5 text-xs font-medium', $classes]) }}>
    {{ $type->label() }}
</span>