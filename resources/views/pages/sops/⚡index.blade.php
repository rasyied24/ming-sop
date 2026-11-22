<?php

use App\Models\Sop;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Daftar SOP')] class extends Component {
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $category = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCategory(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function categories(): array
    {
        return Sop::published()
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->all();
    }

    #[Computed]
    public function sops()
    {
        $userId = auth()->id();

        return Sop::published()
            ->when($this->search !== '', function ($query) {
                // Escape karakter wildcard LIKE supaya input "%" atau "_" dianggap teks biasa.
                $term = '%'.addcslashes($this->search, '%_\\').'%';

                $query->where(function ($q) use ($term) {
                    $q->where('title', 'ilike', $term)
                        ->orWhere('document_number', 'ilike', $term);
                });
            })
            ->when($this->category !== '', fn ($query) => $query->where('category', $this->category))
            ->withExists([
                // Sudah konfirmasi versi yang berlaku sekarang?
                'acknowledgments as acknowledged' => fn ($q) => $q
                    ->where('user_id', $userId)
                    ->whereColumn('sop_acknowledgments.sop_version', 'sops.version'),
                // Pernah konfirmasi versi apa pun?
                'acknowledgments as has_any_ack' => fn ($q) => $q
                    ->where('user_id', $userId),
            ])
            ->orderBy('document_number')
            ->paginate(9);
    }
};
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-semibold">Daftar SOP</h1>
        <p class="mt-1 text-sm text-slate-500">SOP aktif yang berlaku di perusahaan.</p>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row">
        <input type="search" wire:model.live.debounce.300ms="search"
               placeholder="Cari judul atau nomor dokumen..."
               class="block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">

        <select wire:model.live="category"
                class="block rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 sm:w-56">
            <option value="">Semua kategori</option>
            @foreach ($this->categories as $item)
                <option value="{{ $item }}">{{ $item }}</option>
            @endforeach
        </select>
    </div>

    <div wire:loading.delay class="text-sm text-slate-500">Memuat...</div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
        @forelse ($this->sops as $sop)
            <a href="{{ route('sops.show', $sop) }}" wire:navigate wire:key="sop-{{ $sop->id }}"
               class="block rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200 transition hover:ring-brand-300">
                <div class="flex items-start justify-between gap-2">
                    <span class="font-mono text-xs text-slate-500">{{ $sop->document_number }}</span>
                    <span class="rounded-full bg-slate-100 px-2 py-0.5 text-xs text-slate-600">{{ $sop->category }}</span>
                </div>

                <h2 class="mt-2 font-semibold text-slate-800">{{ $sop->title }}</h2>

                <div class="mt-4 flex items-center justify-between text-xs">
                    <span class="text-slate-500">Versi {{ $sop->version }}</span>

                    @can('acknowledge', $sop)
                        @if ($sop->acknowledged)
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-emerald-700">Sudah dikonfirmasi</span>
                        @elseif ($sop->has_any_ack)
                            <span class="rounded-full bg-amber-50 px-2 py-0.5 text-amber-700">Versi baru, konfirmasi ulang</span>
                        @else
                            <span class="rounded-full bg-red-50 px-2 py-0.5 text-red-700">Belum dikonfirmasi</span>
                        @endif
                    @endcan
                </div>
            </a>
        @empty
            <div class="col-span-full rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">
                Tidak ada SOP yang cocok dengan pencarian Anda.
            </div>
        @endforelse
    </div>

    @if ($this->sops->hasPages())
        <div class="flex items-center justify-between text-sm text-slate-600">
            <span>
                Halaman {{ $this->sops->currentPage() }} dari {{ $this->sops->lastPage() }}
                &middot; {{ $this->sops->total() }} SOP
            </span>

            <div class="flex gap-2">
                <button type="button" wire:click="previousPage" @disabled($this->sops->onFirstPage())
                        class="rounded-md border border-slate-300 bg-white px-3 py-1.5 hover:bg-slate-100 disabled:opacity-50">
                    Sebelumnya
                </button>
                <button type="button" wire:click="nextPage" @disabled(! $this->sops->hasMorePages())
                        class="rounded-md border border-slate-300 bg-white px-3 py-1.5 hover:bg-slate-100 disabled:opacity-50">
                    Berikutnya
                </button>
            </div>
        </div>
    @endif
</div>