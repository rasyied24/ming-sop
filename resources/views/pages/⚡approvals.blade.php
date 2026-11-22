<?php

use App\Enums\SopAction;
use App\Enums\SopStatus;
use App\Models\Sop;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Pengajuan & Persetujuan')] class extends Component {
    #[Computed]
    public function pending()
    {
        return Sop::query()
            ->where('status', SopStatus::Review->value)
            ->with('creator')
            ->withMax(
                ['logs as submitted_at' => fn ($q) => $q->where('action', SopAction::SubmittedForReview->value)],
                'created_at'
            )
            ->orderBy('submitted_at')
            ->get();
    }
};
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Pengajuan &amp; Persetujuan</h1>
        <p class="mt-1 text-sm text-slate-500">SOP yang diajukan PIC dan menunggu keputusan Anda, yang paling lama menunggu di atas.</p>
    </div>

    <div class="space-y-4">
        @forelse ($this->pending as $sop)
            <div wire:key="pending-{{ $sop->id }}"
                 class="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                <div>
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span class="font-mono text-slate-500">{{ $sop->document_number }}</span>
                        <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600">{{ $sop->category }}</span>
                        <span class="rounded-full bg-brand-50 px-2 py-0.5 text-brand-700">Versi {{ $sop->version }}</span>
                    </div>
                    <h2 class="mt-2 font-semibold">{{ $sop->title }}</h2>
                    <p class="mt-1 text-sm text-slate-500">
                        Diajukan oleh {{ $sop->creator->name }}
                        &middot; {{ \Illuminate\Support\Carbon::parse($sop->submitted_at)->diffForHumans() }}
                    </p>
                </div>

                <a href="{{ route('sops.show', $sop) }}" wire:navigate
                   class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">
                    Tinjau
                </a>
            </div>
        @empty
            <div class="rounded-xl border border-dashed border-slate-300 bg-white p-10 text-center text-sm text-slate-500">
                Tidak ada SOP yang menunggu persetujuan.
            </div>
        @endforelse
    </div>
</div>