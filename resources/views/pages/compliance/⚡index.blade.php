<?php

use App\Enums\UserRole;
use App\Services\ComplianceReport;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Kepatuhan')] class extends Component {
    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole(UserRole::Auditor), 403);
    }

    /**
     * @return array{perSop: \Illuminate\Support\Collection, overall: array<string, int>}
     */
    #[Computed]
    public function data(): array
    {
        $service = app(ComplianceReport::class);

        // Yang paling rendah di atas, karena itu yang perlu ditindaklanjuti lebih dulu.
        $perSop = $service->perSop()->sortBy('percentage')->values();

        return ['perSop' => $perSop, 'overall' => $service->overall($perSop)];
    }
};
?>

@php
    $overall = $this->data['overall'];
@endphp

<div class="space-y-6">
    <div>
        <h1 class="text-2xl font-bold">Pemantauan Kepatuhan</h1>
        <p class="mt-1 text-sm text-slate-500">
            Persentase karyawan yang sudah membaca dan memahami versi SOP yang berlaku sekarang.
        </p>
    </div>

    <div class="grid gap-4 sm:grid-cols-3">
        <x-stat-card label="Kepatuhan Keseluruhan" :value="$overall['expected'] > 0 ? $overall['percentage'].'%' : '-'"
                     icon="shield-check" tone="emerald"
                     :hint="$overall['acknowledged'].' dari '.$overall['expected'].' konfirmasi'" />

        <x-stat-card label="Karyawan" :value="$overall['employees']" icon="check-circle" tone="brand"
                     hint="Wajib mengonfirmasi tiap SOP terbit" />

        <x-stat-card label="SOP Terbit" :value="$overall['sops']" icon="document-text" tone="brand"
                     hint="Hanya SOP berstatus Published" />
    </div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-700">
                <tr>
                    <th class="px-4 py-3">No. Dokumen</th>
                    <th class="px-4 py-3">Judul SOP</th>
                    <th class="px-4 py-3">Versi</th>
                    <th class="px-4 py-3">Sudah Membaca</th>
                    <th class="w-64 px-4 py-3">Kepatuhan</th>
                    <th class="px-4 py-3 text-right">Tindakan</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($this->data['perSop'] as $sop)
                    <tr wire:key="sop-{{ $sop->id }}" class="hover:bg-slate-50/60">
                        <td class="whitespace-nowrap px-4 py-3 font-mono text-xs text-slate-600">{{ $sop->document_number }}</td>
                        <td class="max-w-sm px-4 py-3 font-medium text-slate-800">
                            {{ $sop->title }}
                            <span class="block text-xs font-normal text-slate-400">{{ $sop->category }}</span>
                        </td>
                        <td class="px-4 py-3 text-slate-600">v{{ $sop->version }}</td>
                        <td class="whitespace-nowrap px-4 py-3 text-slate-600">{{ $sop->acknowledged_count }} dari {{ $sop->employee_count }}</td>
                        <td class="px-4 py-3"><x-progress-bar :value="$sop->percentage" /></td>
                        <td class="px-4 py-3 text-right">
                            <a href="{{ route('compliance.show', $sop) }}" wire:navigate
                               class="inline-flex items-center gap-1 rounded-md border border-slate-300 bg-white px-2.5 py-1 text-xs font-medium text-slate-700 hover:bg-slate-50">
                                <x-icon name="eye" size="size-4" /> Rincian
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="px-4 py-12 text-center text-slate-500">Belum ada SOP terbit.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>