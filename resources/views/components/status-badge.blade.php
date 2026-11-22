@props(['status'])

@php
    $classes = match ($status->value) {
        'published' => 'bg-emerald-50 text-emerald-700',
        'review' => 'bg-amber-50 text-amber-700',
        'draft' => 'bg-slate-100 text-slate-600',
        'archived' => 'bg-red-50 text-red-700',
    };
@endphp

<span {{ $attributes->class(['inline-block rounded-full px-2.5 py-0.5 text-xs font-medium', $classes]) }}>
    {{ $status->label() }}
</span>