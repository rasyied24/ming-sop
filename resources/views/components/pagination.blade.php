@props(['paginator'])

@php
    $current = $paginator->currentPage();
    $last = $paginator->lastPage();

    $pages = collect([1, $last, $current - 1, $current, $current + 1])
        ->filter(fn ($page) => $page >= 1 && $page <= $last)
        ->unique()
        ->sort()
        ->values();

    $items = [];
    $previous = null;

    foreach ($pages as $page) {
        if ($previous !== null && $page - $previous > 1) {
            $items[] = '...';
        }

        $items[] = $page;
        $previous = $page;
    }
@endphp

<div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
    <span>
        @if ($paginator->total() > 0)
            Menampilkan {{ $paginator->firstItem() }}-{{ $paginator->lastItem() }} dari {{ $paginator->total() }} data
        @else
            Tidak ada data
        @endif
    </span>

    @if ($paginator->hasPages())
        <div class="flex items-center gap-1">
            <button type="button" wire:click="previousPage" @disabled($paginator->onFirstPage())
                    class="rounded-md px-2.5 py-1 hover:bg-slate-100 disabled:opacity-40" aria-label="Halaman sebelumnya">&lsaquo;</button>

            @foreach ($items as $item)
                @if ($item === '...')
                    <span class="px-2">&hellip;</span>
                @else
                    <button type="button" wire:click="gotoPage({{ $item }})" wire:key="page-{{ $item }}"
                            @class([
                                'min-w-8 rounded-md px-2.5 py-1',
                                'bg-brand-100 font-semibold text-brand-700' => $item === $current,
                                'hover:bg-slate-100' => $item !== $current,
                            ])>
                        {{ $item }}
                    </button>
                @endif
            @endforeach

            <button type="button" wire:click="nextPage" @disabled(! $paginator->hasMorePages())
                    class="rounded-md px-2.5 py-1 hover:bg-slate-100 disabled:opacity-40" aria-label="Halaman berikutnya">&rsaquo;</button>
        </div>
    @endif
</div>