@props(['status'])

@php
    $classes = match ($status->value) {
        'pending' => 'bg-amber-50 text-amber-700',
        'in_progress' => 'bg-brand-50 text-brand-700',
        'resolved' => 'bg-emerald-50 text-emerald-700',
    };
@endphp

<span {{ $attributes->class(['inline-block rounded-full px-2.5 py-0.5 text-xs font-medium', $classes]) }}>
    {{ $status->label() }}
</span>