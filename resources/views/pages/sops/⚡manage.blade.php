<?php

use App\Enums\SopStatus;
use App\Models\Sop;
use App\Services\SopWorkflow;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Direktori SOP')] class extends Component {
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    #[Url]
    public string $sort = 'terbaru';

    public function setStatus(string $status): void
    {
        // tryFrom memastikan hanya nilai status yang valid yang diterima dari browser.
        $this->status = SopStatus::tryFrom($status)?->value ?? '';
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    public function updatedSort(): void
    {
        $this->resetPage();
    }

    public function archive(SopWorkflow $workflow, int $sopId): void
    {
        $sop = Sop::findOrFail($sopId);

        // Hak akses dicek di dalam service, bukan hanya lewat tombol yang disembunyikan.
        $workflow->archive(auth()->user(), $sop);

        // Flash hanya hidup sampai request berikutnya, jadi banner tidak menempel selamanya.
        session()->flash('notice', "SOP {$sop->document_number} telah diarsipkan.");
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        // Query builder (bukan Eloquent) supaya nilai status tidak diubah menjadi objek enum.
        return DB::table('sops')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function categories(): array
    {
        return Sop::query()
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }

    #[Computed]
    public function sops()
    {
        return Sop::query()
            ->with('creator')
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->category !== '', fn ($query) => $query->where('category', $this->category))
            ->when($this->search !== '', function ($query) {
                // Escape wildcard LIKE supaya "%" atau "_" dianggap teks biasa.
                $term = '%'.addcslashes($this->search, '%_\\').'%';

                $query->where(function ($q) use ($term) {
                    $q->where('title', 'ilike', $term)
                        ->orWhere('document_number', 'ilike', $term);
                });
            })
            ->tap(fn ($query) => match ($this->sort) {
                'terlama' => $query->orderBy('updated_at'),
                'nomor' => $query->orderBy('document_number'),
                'judul' => $query->orderBy('title'),
                default => $query->orderByDesc('updated_at'),
            })
            ->paginate(10);
    }

    /**
     * Nomor halaman yang ditampilkan: 1 ... (sekarang-1) sekarang (sekarang+1) ... terakhir.
     *
     * @return array<int, int|string>
     */
    #[Computed]
    public function pageNumbers(): array
    {
        $current = $this->sops->currentPage();
        $last = $this->sops->lastPage();

        $pages = collect([1, $last, $current - 1, $current, $current + 1])
            ->filter(fn (int $page) => $page >= 1 && $page <= $last)
            ->unique()
            ->sort()
            ->values();

        $result = [];
        $previous = null;

        foreach ($pages as $page) {
            if ($previous !== null && $page - $previous > 1) {
                $result[] = '...';
            }

            $result[] = $page;
            $previous = $page;
        }

        return $result;
    }
};
?>

@php
    $actionButton = 'inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50';
    $tabs = [SopStatus::Published, SopStatus::Review, SopStatus::Draft, SopStatus::Archived];
@endphp

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Direktori &amp; Manajemen SOP</h1>
            <p class="mt-1 text-sm text-slate-500">Seluruh dokumen SOP beserta status dan versinya.</p>
        </div>

        @can('create', \App\Models\Sop::class)
            <a href="{{ route('sops.create') }}" wire:navigate
               class="inline-flex items-center gap-2 rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">
                <x-icon name="plus" size="size-4" />
                Buat SOP Baru
            </a>
        @endcan
    </div>

    @if (session('notice'))
        <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('notice') }}</div>
    @endif

    {{-- Tab status --}}
    <div class="flex flex-wrap gap-x-6 border-b border-slate-200">
        <button type="button" wire:click="setStatus('')"
                @class([
                    '-mb-px border-b-2 px-1 pb-3 text-sm font-medium',
                    'border-brand-600 text-brand-600' => $status === '',
                    'border-transparent text-slate-600 hover:text-slate-900' => $status !== '',
                ])>
            Semua ({{ array_sum($this->counts) }})
        </button>

        @foreach ($tabs as $tab)
            <button type="button" wire:click="setStatus('{{ $tab->value }}')" wire:key="tab-{{ $tab->value }}"
                    @class([
                        '-mb-px border-b-2 px-1 pb-3 text-sm font-medium',
                        'border-brand-600 text-brand-600' => $status === $tab->value,
                        'border-transparent text-slate-600 hover:text-slate-900' => $status !== $tab->value,
                    ])>
                {{ $tab->tabLabel() }} ({{ $this->counts[$tab->value] ?? 0 }})
            </button>
        @endforeach
    </div>

    {{-- Pencarian, filter, urutan --}}
    <div class="flex flex-col gap-3 lg:flex-row">
        <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <x-icon name="magnifying-glass" size="size-4" />
            </span>
            <input type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Cari berdasarkan judul atau nomor dokumen..."
                   class="block w-full rounded-md border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
        </div>

        <select wire:model.live="category"
                class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 lg:w-48">
            <option value="">Semua Departemen</option>
            @foreach ($this->categories as $item)
                <option value="{{ $item }}">{{ $item }}</option>
            @endforeach
        </select>

        <select wire:model.live="sort"
                class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 lg:w-48">
            <option value="terbaru">Terbaru diperbarui</option>
            <option value="terlama">Terlama diperbarui</option>
            <option value="nomor">Nomor dokumen</option>
            <option value="judul">Judul A-Z</option>
        </select>
    </div>

    <div wire:loading.delay class="text-sm text-slate-500">Memuat...</div>

    {{-- Tabel --}}
    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-700">
                <tr>
                    <th class="px-4 py-3">No. Dokumen</th>
                    <th class="px-4 py-3">Judul SOP</th>
                    <th class="px-4 py-3">Kategori / Dept</th>
                    <th class="px-4 py-3">Versi</th>
                    <th class="px-4 py-3">Terakhir Diperbarui</th>
                    <th class="px-4 py-3">Pembuat</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3 text-right">Tindakan</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($this->sops as $sop)
                    <tr wire:key="row-{{ $sop->id }}" class="hover:bg-slate-50/60">
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ $sop->document_number }}</td>
                        <td class="max-w-xs px-4 py-3 font-medium text-slate-800">{{ $sop->title }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $sop->category }}</td>
                        <td class="px-4 py-3 text-slate-600">v{{ $sop->version }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $sop->updated_at->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $sop->creator->name }}</td>
                        <td class="px-4 py-3"><x-status-badge :status="$sop->status" /></td>
                        <td class="px-4 py-3">
                            <div class="flex flex-wrap justify-end gap-2">
                                <a href="{{ route('sops.show', $sop) }}" wire:navigate class="{{ $actionButton }}">
                                    <x-icon name="eye" size="size-4" /> Lihat
                                </a>

                                @can('revise', $sop)
                                    {{-- Tanpa wire:navigate agar halaman langsung menggulung ke bagian revisi --}}
                                    <a href="{{ route('sops.show', $sop) }}#revisi" class="{{ $actionButton }}">
                                        <x-icon name="pencil-square" size="size-4" /> Revisi
                                    </a>
                                @endcan

                                @can('update', $sop)
                                    <a href="{{ route('sops.edit', $sop) }}" wire:navigate class="{{ $actionButton }}">
                                        <x-icon name="pencil-square" size="size-4" /> Edit
                                    </a>
                                @endcan

                                @can('archive', $sop)
                                    <button type="button" wire:click="archive({{ $sop->id }})"
                                            wire:confirm="Arsipkan SOP {{ $sop->document_number }}? Karyawan tidak akan melihatnya lagi."
                                            class="{{ $actionButton }}">
                                        <x-icon name="archive-box" size="size-4" /> Arsip
                                    </button>
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-12 text-center text-slate-500">
                            Tidak ada SOP yang cocok dengan filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        {{-- Pagination --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200 px-4 py-3 text-sm text-slate-600">
            <span>
                @if ($this->sops->total() > 0)
                    Menampilkan {{ $this->sops->firstItem() }}-{{ $this->sops->lastItem() }} dari {{ $this->sops->total() }} data
                @else
                    Tidak ada data
                @endif
            </span>

            @if ($this->sops->hasPages())
                <div class="flex items-center gap-1">
                    <button type="button" wire:click="previousPage" @disabled($this->sops->onFirstPage())
                            class="rounded-md px-2.5 py-1 hover:bg-slate-100 disabled:opacity-40" aria-label="Halaman sebelumnya">&lsaquo;</button>

                    @foreach ($this->pageNumbers as $item)
                        @if ($item === '...')
                            <span class="px-2">&hellip;</span>
                        @else
                            <button type="button" wire:click="gotoPage({{ $item }})" wire:key="page-{{ $item }}"
                                    @class([
                                        'min-w-8 rounded-md px-2.5 py-1',
                                        'bg-brand-100 font-semibold text-brand-700' => $item === $this->sops->currentPage(),
                                        'hover:bg-slate-100' => $item !== $this->sops->currentPage(),
                                    ])>
                                {{ $item }}
                            </button>
                        @endif
                    @endforeach

                    <button type="button" wire:click="nextPage" @disabled(! $this->sops->hasMorePages())
                            class="rounded-md px-2.5 py-1 hover:bg-slate-100 disabled:opacity-40" aria-label="Halaman berikutnya">&rsaquo;</button>
                </div>
            @endif
        </div>
    </div>
</div>