@props(['value'])

@php
    $value = max(0, min(100, (int) $value));
    $bar = $value >= 80 ? 'bg-emerald-500' : ($value >= 50 ? 'bg-amber-500' : 'bg-red-500');
@endphp

<div {{ $attributes->class(['flex items-center gap-3']) }}>
    <div class="h-2 w-full overflow-hidden rounded-full bg-slate-100"
         role="progressbar" aria-valuenow="{{ $value }}" aria-valuemin="0" aria-valuemax="100">
        <div class="h-full rounded-full {{ $bar }}" style="width: {{ $value }}%"></div>
    </div>
    <span class="w-10 shrink-0 text-right text-sm font-medium text-slate-700">{{ $value }}%</span>
</div>