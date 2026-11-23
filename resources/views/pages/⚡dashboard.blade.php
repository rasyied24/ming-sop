<?php

use App\Enums\ReportStatus;
use App\Enums\SopAction;
use App\Enums\SopStatus;
use App\Enums\UserRole;
use App\Models\Sop;
use App\Models\SopReport;
use App\Services\ComplianceReport;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Dashboard')] class extends Component {
    public function mount(): void
    {
        // Pertahanan berlapis: route sudah dibatasi role:manager, ini pagar kedua.
        abort_unless(auth()->user()->hasRole(UserRole::Manager), 403);
    }

    #[Computed]
    public function activeCount(): int
    {
        return Sop::published()->count();
    }

    /**
     * @return array{perSop: \Illuminate\Support\Collection, overall: array<string, int>}
     */
    #[Computed]
    public function compliance(): array
    {
        $service = app(ComplianceReport::class);
        $perSop = $service->perSop();

        return ['perSop' => $perSop, 'overall' => $service->overall($perSop)];
    }

    #[Computed]
    public function pendingApprovals()
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

    /**
     * Jumlah laporan belum selesai per status. Total kartu dan rincian di kartu berasal dari sini,
     * jadi keduanya tidak mungkin berbeda.
     *
     * @return array<string, int>
     */
    #[Computed]
    public function openReportBreakdown(): array
    {
        return DB::table('sop_reports')
            ->whereIn('status', [ReportStatus::Pending->value, ReportStatus::InProgress->value])
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    #[Computed]
    public function openReports()
    {
        // Sengaja tidak memuat relasi pelapor: Manager tidak perlu melihat identitas.
        return SopReport::query()
            ->with('sop')
            ->whereIn('status', [ReportStatus::Pending->value, ReportStatus::InProgress->value])
            // Yang belum ditangani di atas, lalu yang terbaru.
            ->orderByRaw("case status when 'pending' then 0 else 1 end")
            ->latest()
            ->limit(5)
            ->get();
    }
};
?>

@php
    $overall = $this->compliance['overall'];
    $lowest = $this->compliance['perSop']->sortBy('percentage')->take(5);

    $breakdown = $this->openReportBreakdown;
    $pendingTotal = $breakdown[ReportStatus::Pending->value] ?? 0;
    $progressTotal = $breakdown[ReportStatus::InProgress->value] ?? 0;
    $openTotal = $pendingTotal + $progressTotal;
    $shown = $this->openReports->count();
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Dashboard</h1>
        <p class="mt-1 text-sm text-slate-500">Ringkasan eksekutif pengelolaan SOP perusahaan.</p>
    </div>

    {{-- Kartu statistik --}}
    <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <x-stat-card label="SOP Aktif" :value="$this->activeCount" icon="document-text" tone="brand"
                     hint="SOP berstatus Published" />

        <x-stat-card label="Menunggu Persetujuan" :value="$this->pendingApprovals->count()" icon="check-circle" tone="amber"
                     hint="Diajukan PIC, menunggu keputusan" />

        <x-stat-card label="Kepatuhan Baca" :value="$overall['expected'] > 0 ? $overall['percentage'].'%' : '-'"
                     icon="shield-check" tone="emerald"
                     :hint="$overall['acknowledged'].' dari '.$overall['expected'].' konfirmasi karyawan'" />

        <x-stat-card label="Laporan Belum Selesai" :value="$openTotal" icon="exclamation-triangle" tone="rose"
                     :hint="$pendingTotal.' menunggu · '.$progressTotal.' diproses'" />
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        {{-- Menunggu persetujuan --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <div class="flex items-center justify-between">
                <h2 class="font-semibold">Menunggu Persetujuan</h2>
                <a href="{{ route('approvals.index') }}" wire:navigate class="text-sm text-brand-600 hover:underline">Lihat semua</a>
            </div>

            <ul class="mt-4 divide-y divide-slate-100">
                @forelse ($this->pendingApprovals->take(5) as $sop)
                    <li class="flex items-center justify-between gap-4 py-3" wire:key="pending-{{ $sop->id }}">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">{{ $sop->title }}</p>
                            <p class="text-xs text-slate-500">
                                <span class="font-mono">{{ $sop->document_number }}</span>
                                &middot; v{{ $sop->version }}
                                &middot; {{ $sop->creator->name }}
                                &middot; {{ \Illuminate\Support\Carbon::parse($sop->submitted_at)->diffForHumans() }}
                            </p>
                        </div>
                        <a href="{{ route('sops.show', ['sop' => $sop, 'from' => 'persetujuan']) }}" wire:navigate
                           class="shrink-0 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium hover:bg-slate-50">
                            Tinjau
                        </a>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">Tidak ada SOP yang menunggu persetujuan.</li>
                @endforelse
            </ul>
        </div>

        {{-- Kepatuhan terendah --}}
        <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
            <h2 class="font-semibold">Kepatuhan Terendah per SOP</h2>
            <p class="mt-1 text-xs text-slate-500">Persentase karyawan yang sudah mengonfirmasi versi berlaku.</p>

            <ul class="mt-4 space-y-4">
                @forelse ($lowest as $sop)
                    <li wire:key="low-{{ $sop->id }}">
                        <div class="flex items-center justify-between gap-4 text-sm">
                            <span class="truncate font-medium">{{ $sop->title }}</span>
                            <span class="shrink-0 text-xs text-slate-500">{{ $sop->acknowledged_count }}/{{ $sop->employee_count }}</span>
                        </div>
                        <x-progress-bar :value="$sop->percentage" class="mt-1.5" />
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-slate-500">Belum ada SOP terbit.</li>
                @endforelse
            </ul>
        </div>
    </div>

    {{-- Laporan belum selesai --}}
    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <div class="px-6 pt-6">
            <h2 class="font-semibold">Laporan Belum Selesai</h2>
            <p class="mt-1 text-xs text-slate-500">
                Laporan berstatus Menunggu atau Diproses. Penanganan dilakukan oleh tim Auditor/HRD.
            </p>
        </div>

        <table class="mt-4 min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-700">
                <tr>
                    <th class="px-6 py-3">Subjek</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3">SOP Terkait</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Dikirim</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($this->openReports as $report)
                    <tr wire:key="report-{{ $report->id }}">
                        <td class="max-w-sm px-6 py-3 font-medium text-slate-800">{{ $report->subject }}</td>
                        <td class="px-4 py-3"><x-report-type-badge :type="$report->type" /></td>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ $report->sop?->document_number ?? '-' }}</td>
                        <td class="px-4 py-3"><x-report-status-badge :status="$report->status" /></td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $report->created_at->translatedFormat('d M Y') }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-6 py-10 text-center text-slate-500">Tidak ada laporan yang belum selesai.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        @if ($openTotal > 0)
            <p class="border-t border-slate-200 px-6 py-3 text-xs text-slate-500">
                Menampilkan {{ $shown }} dari {{ $openTotal }} laporan.
                @if ($openTotal > $shown)
                    Sisanya hanya dapat dibuka oleh Auditor/HRD.
                @endif
            </p>
        @endif
    </div>
</div>