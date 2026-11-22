<?php

use App\Models\Sop;
use App\Models\SopAcknowledgment;
use App\Services\SopWorkflow;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail SOP')] class extends Component {
    public Sop $sop;

    // Versi yang sedang tampil di layar saat halaman dibuka.
    public string $displayedVersion = '';

    public string $rejectionNote = '';

    public function mount(Sop $sop): void
    {
        $this->authorize('view', $sop);

        $this->sop = $sop;
        $this->displayedVersion = $sop->version;
    }

    public function acknowledge(): void
    {
        $this->authorize('acknowledge', $this->sop);

        // Cegah konfirmasi versi yang belum pernah dilihat user:
        // SOP bisa naik versi setelah halaman ini dibuka.
        if ($this->displayedVersion !== $this->sop->version) {
            $this->displayedVersion = $this->sop->version;

            $this->addError('acknowledge', 'SOP ini baru diperbarui ke versi '.$this->sop->version.'. Silakan baca ulang isinya, lalu konfirmasi kembali.');

            return;
        }

        SopAcknowledgment::firstOrCreate(
            [
                'sop_id' => $this->sop->id,
                'user_id' => auth()->id(),
                'sop_version' => $this->sop->version,
            ],
            ['acknowledged_at' => now()],
        );

        unset($this->acknowledgment);
        unset($this->olderAcknowledgment);
    }

    public function submitForReview(SopWorkflow $workflow): void
    {
        $workflow->submitForReview(auth()->user(), $this->sop);

        session()->flash('status', 'SOP berhasil diajukan ke Manager untuk ditinjau.');
        $this->redirect(route('sops.manage'), navigate: true);
    }

    public function startRevision(SopWorkflow $workflow, bool $major = false): void
    {
        $sop = $workflow->startRevision(auth()->user(), $this->sop, $major);

        session()->flash('status', "Revisi dimulai (versi {$sop->version}). SOP berstatus draf sampai disetujui Manager.");
        $this->redirect(route('sops.edit', $sop), navigate: true);
    }

    public function approve(SopWorkflow $workflow): void
    {
        $workflow->approve(auth()->user(), $this->sop);

        session()->flash('status', 'SOP disetujui dan diterbitkan.');
        $this->redirect(route('approvals.index'), navigate: true);
    }

    public function reject(SopWorkflow $workflow): void
    {
        $this->validate(
            ['rejectionNote' => ['required', 'string', 'min:10', 'max:1000']],
            [
                'required' => ':attribute wajib diisi.',
                'min' => ':attribute minimal :min karakter agar PIC paham apa yang perlu diperbaiki.',
                'max' => ':attribute maksimal :max karakter.',
            ],
            ['rejectionNote' => 'Catatan revisi'],
        );

        $workflow->reject(auth()->user(), $this->sop, $this->rejectionNote);

        session()->flash('status', 'SOP dikembalikan ke PIC beserta catatan revisi.');
        $this->redirect(route('approvals.index'), navigate: true);
    }

    public function archive(SopWorkflow $workflow): void
    {
        $workflow->archive(auth()->user(), $this->sop);

        session()->flash('status', 'SOP telah diarsipkan.');
        $this->redirect(route('sops.manage'), navigate: true);
    }

    #[Computed]
    public function acknowledgment(): ?SopAcknowledgment
    {
        return SopAcknowledgment::query()
            ->where('sop_id', $this->sop->id)
            ->where('user_id', auth()->id())
            ->where('sop_version', $this->sop->version)
            ->first();
    }

    #[Computed]
    public function olderAcknowledgment(): ?SopAcknowledgment
    {
        return SopAcknowledgment::query()
            ->where('sop_id', $this->sop->id)
            ->where('user_id', auth()->id())
            ->where('sop_version', '!=', $this->sop->version)
            ->latest('acknowledged_at')
            ->first();
    }

    #[Computed]
    public function contentHtml(): string
    {
        // html_input=strip membuang HTML mentah dari isi SOP (pencegah XSS).
        return Str::markdown($this->sop->content, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    #[Computed]
    public function logs()
    {
        return $this->sop->logs()
            ->with('user')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();
    }
};
?>

@php
    // PIC dan Manager datang dari direktori, jadi tombol kembali ke sana.
    $backRoute = auth()->user()->hasRole(\App\Enums\UserRole::PicSop, \App\Enums\UserRole::Manager)
        ? 'sops.manage'
        : 'sops.index';
@endphp

<div class="space-y-6">
    <a href="{{ route($backRoute) }}" wire:navigate class="text-sm text-brand-600 hover:underline">
        &larr; Kembali
    </a>

    @if ($sop->revision_note)
        <div class="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <p class="font-medium">Catatan revisi dari Manager:</p>
            <p class="mt-1">{{ $sop->revision_note }}</p>
        </div>
    @endif

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="font-mono text-slate-500">{{ $sop->document_number }}</span>
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600">{{ $sop->category }}</span>
            <span class="rounded-full bg-brand-50 px-2 py-0.5 text-brand-700">Versi {{ $sop->version }}</span>
            <x-status-badge :status="$sop->status" />
        </div>

        <h1 class="mt-3 text-2xl font-semibold">{{ $sop->title }}</h1>

        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-3">
            <div>
                <dt class="text-slate-500">Disusun oleh</dt>
                <dd class="font-medium">{{ $sop->creator->name }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Disetujui oleh</dt>
                <dd class="font-medium">{{ $sop->approver?->name ?? '-' }}</dd>
            </div>
            <div>
                <dt class="text-slate-500">Terakhir diperbarui</dt>
                <dd class="font-medium">{{ $sop->updated_at->translatedFormat('d F Y') }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="sop-content">
            {!! $this->contentHtml !!}
        </div>
    </div>

    {{-- PIC SOP: ubah atau ajukan draf --}}
    @can('update', $sop)
        <div class="flex flex-wrap items-center gap-3 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <a href="{{ route('sops.edit', $sop) }}" wire:navigate
               class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50">
                Ubah Draf
            </a>

            @can('submitForReview', $sop)
                <button type="button" wire:click="submitForReview" wire:loading.attr="disabled"
                        wire:confirm="Ajukan SOP ini ke Manager untuk ditinjau? Setelah diajukan, draf tidak bisa diubah sampai ditolak atau disetujui."
                        class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500 disabled:opacity-60">
                    Ajukan untuk Review
                </button>
            @endcan
        </div>
    @endcan

    {{-- PIC SOP: revisi SOP yang sudah terbit --}}
    @can('revise', $sop)
        <div id="revisi" class="scroll-mt-24 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Revisi SOP</h2>
            <p class="mt-1 text-sm text-slate-500">
                Revisi menaikkan versi dan mengembalikan SOP ke status draf. Selama menunggu persetujuan Manager,
                SOP ini tidak tampil di daftar karyawan.
            </p>

            <div class="mt-4 flex flex-wrap gap-3">
                <button type="button" wire:click="startRevision(false)"
                        wire:confirm="Mulai revisi minor ke versi {{ $sop->nextVersion() }}? SOP akan kembali ke draf."
                        class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50">
                    Revisi Minor (v{{ $sop->version }} &rarr; v{{ $sop->nextVersion() }})
                </button>

                <button type="button" wire:click="startRevision(true)"
                        wire:confirm="Mulai revisi mayor ke versi {{ $sop->nextVersion(true) }}? SOP akan kembali ke draf."
                        class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50">
                    Revisi Mayor (v{{ $sop->version }} &rarr; v{{ $sop->nextVersion(true) }})
                </button>
            </div>
        </div>
    @endcan

    {{-- Manager: setujui atau tolak --}}
    @can('approve', $sop)
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Keputusan Manager</h2>

            <div class="mt-4">
                <button type="button" wire:click="approve" wire:loading.attr="disabled"
                        wire:confirm="Setujui dan terbitkan SOP ini?"
                        class="rounded-md bg-emerald-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-emerald-500 disabled:opacity-60">
                    Setujui &amp; Terbitkan
                </button>
            </div>

            <div class="mt-6 border-t border-slate-100 pt-6">
                <label for="rejectionNote" class="block text-sm font-medium text-slate-700">Catatan revisi (wajib jika menolak)</label>
                <textarea id="rejectionNote" rows="4" wire:model="rejectionNote"
                          class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-red-500 focus:outline-none focus:ring-1 focus:ring-red-500"></textarea>
                @error('rejectionNote') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror

                <button type="button" wire:click="reject" wire:loading.attr="disabled"
                        class="mt-3 rounded-md bg-red-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-red-500 disabled:opacity-60">
                    Tolak &amp; Kembalikan ke PIC
                </button>
            </div>
        </div>
    @endcan

    {{-- Manager: arsipkan SOP terbit --}}
    @can('archive', $sop)
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Arsipkan SOP</h2>
            <p class="mt-1 text-sm text-slate-500">SOP yang diarsipkan tidak lagi tampil di daftar karyawan.</p>
            <button type="button" wire:click="archive" wire:loading.attr="disabled"
                    wire:confirm="Arsipkan SOP ini? Karyawan tidak akan melihatnya lagi."
                    class="mt-4 rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50 disabled:opacity-60">
                Arsipkan
            </button>
        </div>
    @endcan

    {{-- Karyawan: konfirmasi baca --}}
    @can('acknowledge', $sop)
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            @if ($this->acknowledgment)
                <p class="font-medium text-emerald-700">
                    &#10003; Anda telah mengonfirmasi membaca SOP ini (versi {{ $this->acknowledgment->sop_version }})
                    pada {{ $this->acknowledgment->acknowledged_at->translatedFormat('d F Y, H:i') }}.
                </p>
            @else
                @if ($this->olderAcknowledgment)
                    <p class="mb-4 rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
                        Anda pernah mengonfirmasi versi {{ $this->olderAcknowledgment->sop_version }}.
                        SOP ini sudah diperbarui ke versi {{ $sop->version }}, silakan baca dan konfirmasi ulang.
                    </p>
                @endif

                @error('acknowledge')
                    <p class="mb-4 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>
                @enderror

                <button type="button" wire:click="acknowledge" wire:loading.attr="disabled"
                        class="rounded-md bg-brand-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-brand-500 disabled:opacity-60">
                    Saya Telah Membaca &amp; Memahami SOP Ini
                </button>
            @endif
        </div>
    @endcan

    {{-- Karyawan: laporkan masalah pada SOP ini --}}
    @can('create', \App\Models\SopReport::class)
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Menemukan masalah pada SOP ini?</h2>
            <p class="mt-1 text-sm text-slate-500">
                Laporkan ketidaksesuaian di lapangan, kendala penerapan, atau dugaan pelanggaran.
            </p>
            <a href="{{ route('reports.create', ['sop' => $sop->id]) }}" wire:navigate
               class="mt-4 inline-block rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium hover:bg-slate-50">
                Laporkan Masalah
            </a>
        </div>
    @endcan

    @can('viewHistory', $sop)
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="text-lg font-semibold">Riwayat Perubahan</h2>

            <ul class="mt-4 divide-y divide-slate-100">
                @foreach ($this->logs as $log)
                    <li class="py-3 text-sm" wire:key="log-{{ $log->id }}">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-medium">{{ $log->action->label() }}</span>
                            <span class="text-xs text-slate-500">{{ $log->created_at->translatedFormat('d F Y, H:i') }}</span>
                        </div>
                        <p class="text-slate-500">oleh {{ $log->user->name }}</p>
                        @if ($log->notes)
                            <p class="mt-1 text-slate-600">{{ $log->notes }}</p>
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endcan
</div>