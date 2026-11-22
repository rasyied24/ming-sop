<?php

use App\Models\SopReport;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Laporan Saya')] class extends Component {
    use WithPagination;

    #[Computed]
    public function reports()
    {
        return SopReport::query()
            ->where('user_id', auth()->id())
            ->with('sop')
            ->latest()
            ->paginate(10);
    }
};
?>

<div class="space-y-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold">Laporan Saya</h1>
            <p class="mt-1 text-sm text-slate-500">Laporan masalah atau dugaan pelanggaran SOP yang pernah Anda kirim.</p>
        </div>

        <a href="{{ route('reports.create') }}" wire:navigate
           class="inline-flex items-center gap-2 rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500">
            <x-icon name="plus" size="size-4" />
            Buat Laporan
        </a>
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-700">
                <tr>
                    <th class="px-4 py-3">Subjek</th>
                    <th class="px-4 py-3">Jenis</th>
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
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ $report->sop?->document_number ?? '-' }}</td>
                        <td class="px-4 py-3"><x-report-status-badge :status="$report->status" /></td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $report->created_at->translatedFormat('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('reports.show', $report) }}" wire:navigate
                               class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <x-icon name="eye" size="size-4" /> Lihat
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-slate-500">
                            Anda belum mengirim laporan apa pun.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <x-pagination :paginator="$this->reports" />
    </div>
</div>