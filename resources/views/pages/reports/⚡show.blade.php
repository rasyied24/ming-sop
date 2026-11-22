<?php

use App\Enums\ReportStatus;
use App\Models\SopReport;
use App\Services\ReportWorkflow;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Detail Laporan')] class extends Component {
    public SopReport $report;

    public function mount(SopReport $report): void
    {
        $this->authorize('view', $report);

        $this->report = $report->load(['user', 'sop']);
    }

    public function changeStatus(ReportWorkflow $workflow, string $target): void
    {
        // tryFrom memastikan hanya nilai status yang valid yang diterima dari browser.
        $status = ReportStatus::tryFrom($target);

        abort_if($status === null, 422);

        $this->report = $workflow
            ->changeStatus(auth()->user(), $this->report, $status)
            ->load(['user', 'sop']);

        session()->flash('notice', "Status laporan diubah menjadi {$status->label()}.");
    }
};
?>

@php
    $isAuditor = auth()->user()->hasRole(\App\Enums\UserRole::Auditor);
    $backRoute = $isAuditor ? 'reports.inbox' : 'reports.index';
@endphp

<div class="mx-auto max-w-3xl space-y-6">
    <a href="{{ route($backRoute) }}" wire:navigate class="text-sm text-brand-600 hover:underline">&larr; Kembali</a>

    @if (session('notice'))
        <div class="rounded-md bg-emerald-50 px-4 py-3 text-sm text-emerald-800">{{ session('notice') }}</div>
    @endif

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="text-slate-500">Laporan #{{ $report->id }}</span>
            <x-report-type-badge :type="$report->type" />
            <x-report-status-badge :status="$report->status" />
        </div>

        <h1 class="mt-3 text-2xl font-semibold">{{ $report->subject }}</h1>

        <dl class="mt-4 grid gap-4 text-sm sm:grid-cols-2">
            <div>
                <dt class="text-slate-500">Pelapor</dt>
                <dd class="font-medium">
                    {{ $report->user->name }}
                    @if ($report->user->department)
                        <span class="font-normal text-slate-500">&middot; {{ $report->user->department }}</span>
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-slate-500">SOP terkait</dt>
                <dd class="font-medium">
                    @if ($report->sop)
                        @can('view', $report->sop)
                            <a href="{{ route('sops.show', ['sop' => $report->sop, 'from' => 'daftar']) }}" wire:navigate
                               class="text-brand-600 hover:underline">
                                {{ $report->sop->document_number }} &middot; {{ $report->sop->title }}
                            </a>
                        @else
                            {{ $report->sop->document_number }} &middot; {{ $report->sop->title }}
                        @endcan
                    @else
                        -
                    @endif
                </dd>
            </div>

            <div>
                <dt class="text-slate-500">Dikirim</dt>
                <dd class="font-medium">{{ $report->created_at->translatedFormat('d F Y, H:i') }}</dd>
            </div>

            <div>
                <dt class="text-slate-500">Terakhir diperbarui</dt>
                <dd class="font-medium">{{ $report->updated_at->translatedFormat('d F Y, H:i') }}</dd>
            </div>
        </dl>
    </div>

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <h2 class="font-semibold">Uraian Masalah</h2>
        {{-- Teks di-escape oleh Blade. whitespace-pre-line hanya mempertahankan baris baru. --}}
        <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-slate-700">{{ $report->message }}</p>
    </div>

    @can('updateStatus', $report)
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Tindak Lanjut</h2>
            <p class="mt-1 text-sm text-slate-500">Status saat ini: <strong>{{ $report->status->label() }}</strong>.</p>

            @error('status')
                <p class="mt-3 rounded-md bg-red-50 px-4 py-3 text-sm text-red-700">{{ $message }}</p>
            @enderror

            <div class="mt-4 flex flex-wrap gap-3">
                @foreach ($report->status->allowedNext() as $next)
                    @php
                        $label = match (true) {
                            $next === \App\Enums\ReportStatus::Resolved => 'Tandai Selesai',
                            $report->status === \App\Enums\ReportStatus::Resolved => 'Buka Kembali',
                            default => 'Mulai Tangani',
                        };
                    @endphp

                    <button type="button" wire:click="changeStatus('{{ $next->value }}')" wire:key="next-{{ $next->value }}"
                            wire:loading.attr="disabled"
                            @class([
                                'rounded-md px-4 py-2 text-sm font-semibold disabled:opacity-60',
                                'bg-emerald-600 text-white hover:bg-emerald-500' => $next === \App\Enums\ReportStatus::Resolved,
                                'bg-brand-600 text-white hover:bg-brand-500' => $next !== \App\Enums\ReportStatus::Resolved,
                            ])>
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>
    @endcan
</div>