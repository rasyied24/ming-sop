<?php

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\SopReport;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Laporan Masuk')] class extends Component {
    use WithPagination;

    #[Url]
    public string $status = '';

    #[Url]
    public string $type = '';

    #[Url]
    public string $search = '';

    public function mount(): void
    {
        $this->authorize('viewAny', SopReport::class);
    }

    public function setStatus(string $status): void
    {
        $this->status = ReportStatus::tryFrom($status)?->value ?? '';
        $this->resetPage();
    }

    public function updatedType(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function counts(): array
    {
        return DB::table('sop_reports')
            ->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();
    }

    #[Computed]
    public function reports()
    {
        // Nilai jenis dari URL yang tidak valid diabaikan, bukan dijadikan filter kosong.
        $type = ReportType::tryFrom($this->type);

        return SopReport::query()
            ->with(['user', 'sop'])
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($type, fn ($query) => $query->where('type', $type->value))
            ->when($this->search !== '', function ($query) {
                // Escape wildcard LIKE supaya "%" atau "_" dianggap teks biasa.
                $term = '%'.addcslashes($this->search, '%_\\').'%';

                $query->where(function ($q) use ($term) {
                    $q->where('subject', 'ilike', $term)
                        ->orWhereHas('user', fn ($u) => $u->where('name', 'ilike', $term));
                });
            })
            // Yang belum ditangani di atas, lalu yang terbaru.
            ->orderByRaw("case status when 'pending' then 0 when 'in_progress' then 1 else 2 end")
            ->latest()
            ->paginate(10);
    }
};
?>

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Laporan Masuk</h1>
        <p class="mt-1 text-sm text-slate-500">Laporan masalah dan dugaan pelanggaran SOP dari karyawan.</p>
    </div>

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

        @foreach (ReportStatus::cases() as $tab)
            <button type="button" wire:click="setStatus('{{ $tab->value }}')" wire:key="tab-{{ $tab->value }}"
                    @class([
                        '-mb-px border-b-2 px-1 pb-3 text-sm font-medium',
                        'border-brand-600 text-brand-600' => $status === $tab->value,
                        'border-transparent text-slate-600 hover:text-slate-900' => $status !== $tab->value,
                    ])>
                {{ $tab->label() }} ({{ $this->counts[$tab->value] ?? 0 }})
            </button>
        @endforeach
    </div>

    {{-- Pencarian dan filter jenis --}}
    <div class="flex flex-col gap-3 lg:flex-row">
        <div class="relative flex-1">
            <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                <x-icon name="magnifying-glass" size="size-4" />
            </span>
            <input type="search" wire:model.live.debounce.300ms="search"
                   placeholder="Cari berdasarkan subjek atau nama pelapor..."
                   class="block w-full rounded-md border border-slate-300 bg-white py-2 pl-9 pr-3 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
        </div>

        <select wire:model.live="type"
                class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 lg:w-64">
            <option value="">Semua jenis laporan</option>
            @foreach (ReportType::cases() as $option)
                <option value="{{ $option->value }}">{{ $option->label() }}</option>
            @endforeach
        </select>
    </div>

    <div wire:loading.delay class="text-sm text-slate-500">Memuat...</div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-700">
                <tr>
                    <th class="px-4 py-3">Subjek</th>
                    <th class="px-4 py-3">Jenis</th>
                    <th class="px-4 py-3">Pelapor</th>
                    <th class="px-4 py-3">SOP Terkait</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Dikirim</th>
                    <th class="px-4 py-3 text-right">Tindakan</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($this->reports as $report)
                    <tr wire:key="report-{{ $report->id }}" class="hover:bg-slate-50/60">
                        <td class="max-w-sm px-4 py-3 font-medium text-slate-800">{{ $report->subject }}</td>
                        <td class="px-4 py-3"><x-report-type-badge :type="$report->type" /></td>
                        <td class="px-4 py-3 text-slate-600">
                            {{ $report->user->name }}
                            @if ($report->user->department)
                                <span class="block text-xs text-slate-400">{{ $report->user->department }}</span>
                            @endif
                        </td>
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ $report->sop?->document_number ?? '-' }}</td>
                        <td class="px-4 py-3"><x-report-status-badge :status="$report->status" /></td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $report->created_at->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('reports.show', $report) }}" wire:navigate
                               class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <x-icon name="eye" size="size-4" /> Tinjau
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-12 text-center text-slate-500">
                            Tidak ada laporan yang cocok dengan filter ini.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <x-pagination :paginator="$this->reports" />
    </div>
</div>