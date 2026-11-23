@props(['label', 'value', 'icon', 'tone' => 'brand', 'hint' => null])

@php
    // Nama class ditulis utuh supaya Tailwind menemukannya saat build.
    $tones = [
        'brand' => 'bg-brand-50 text-brand-600',
        'amber' => 'bg-amber-50 text-amber-600',
        'emerald' => 'bg-emerald-50 text-emerald-600',
        'rose' => 'bg-rose-50 text-rose-600',
    ];
@endphp

<div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
    <div class="flex items-center justify-between">
        <p class="text-sm text-slate-500">{{ $label }}</p>
        <span class="flex size-9 items-center justify-center rounded-lg {{ $tones[$tone] ?? $tones['brand'] }}">
            <x-icon :name="$icon" />
        </span>
    </div>

    <p class="mt-3 text-3xl font-bold text-slate-800">{{ $value }}</p>

    @if ($hint)
        <p class="mt-1 text-xs text-slate-500">{{ $hint }}</p>
    @endif
</div>