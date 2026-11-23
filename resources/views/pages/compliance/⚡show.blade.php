<?php

use App\Enums\SopStatus;
use App\Enums\UserRole;
use App\Models\Sop;
use App\Services\ComplianceReport;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Rincian Kepatuhan')] class extends Component {
    use WithPagination;

    public Sop $sop;

    #[Url]
    public string $status = '';

    #[Url]
    public string $department = '';

    public function mount(Sop $sop): void
    {
        abort_unless(auth()->user()->hasRole(UserRole::Auditor), 403);

        // Kepatuhan hanya bermakna untuk SOP yang sedang berlaku.
        abort_unless($sop->status === SopStatus::Published, 404);

        $this->sop = $sop;
    }

    public function setStatus(string $status): void
    {
        // Whitelist: nilai dari browser tidak pernah dipercaya begitu saja.
        $this->status = in_array($status, ['sudah', 'versi_lama', 'belum'], true) ? $status : '';
        $this->resetPage();
    }

    public function updatedDepartment(): void
    {
        $this->resetPage();
    }

    /**
     * @return array<string, int>
     */
    #[Computed]
    public function summary(): array
    {
        return app(ComplianceReport::class)->summaryFor($this->sop);
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function departments(): array
    {
        return app(ComplianceReport::class)->departments();
    }

    #[Computed]
    public function employees()
    {
        $service = app(ComplianceReport::class);

        return $service->filterByStatus($service->employeesQuery($this->sop), $this->sop, $this->status)
            ->when($this->department !== '', fn ($query) => $query->where('department', $this->department))
            ->orderBy('name')
            ->paginate(10);
    }
};
?>

<div class="space-y-6">
    <a href="{{ route('compliance') }}" wire:navigate class="text-sm text-brand-600 hover:underline">&larr; Kembali ke Kepatuhan</a>

    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="flex flex-wrap items-center gap-2 text-xs">
            <span class="font-mono text-slate-500">{{ $sop->document_number }}</span>
            <span class="rounded-full bg-slate-100 px-2 py-0.5 text-slate-600">{{ $sop->category }}</span>
            <span class="rounded-full bg-brand-50 px-2 py-0.5 text-brand-700">Versi {{ $sop->version }}</span>
        </div>

        <h1 class="mt-3 text-2xl font-semibold">{{ $sop->title }}</h1>

        <div class="mt-4">
            <x-progress-bar :value="$this->summary['percentage']" />
            <p class="mt-1 text-xs text-slate-500">
                {{ $this->summary['acknowledged'] }} dari {{ $this->summary['employees'] }} karyawan sudah mengonfirmasi versi {{ $sop->version }}.
            </p>
        </div>

        <a href="{{ route('sops.show', ['sop' => $sop, 'from' => 'daftar']) }}" wire:navigate
           class="mt-4 inline-block text-sm text-brand-600 hover:underline">
            Buka isi SOP &rarr;
        </a>
    </div>

    {{-- Ringkasan status yang juga berfungsi sebagai filter --}}
    <div class="grid gap-3 sm:grid-cols-4">
        <button type="button" wire:click="setStatus('')"
                @class([
                    'rounded-lg border p-4 text-left text-sm',
                    'border-brand-500 bg-brand-50 ring-1 ring-brand-500' => $status === '',
                    'border-slate-300 bg-white hover:bg-slate-50' => $status !== '',
                ])>
            <span class="block text-xs text-slate-500">Semua karyawan</span>
            <span class="text-2xl font-bold">{{ $this->summary['employees'] }}</span>
        </button>

        <button type="button" wire:click="setStatus('sudah')"
                @class([
                    'rounded-lg border p-4 text-left text-sm',
                    'border-emerald-500 bg-emerald-50 ring-1 ring-emerald-500' => $status === 'sudah',
                    'border-slate-300 bg-white hover:bg-slate-50' => $status !== 'sudah',
                ])>
            <span class="block text-xs text-slate-500">Sudah membaca</span>
            <span class="text-2xl font-bold text-emerald-700">{{ $this->summary['acknowledged'] }}</span>
        </button>

        <button type="button" wire:click="setStatus('versi_lama')"
                @class([
                    'rounded-lg border p-4 text-left text-sm',
                    'border-amber-500 bg-amber-50 ring-1 ring-amber-500' => $status === 'versi_lama',
                    'border-slate-300 bg-white hover:bg-slate-50' => $status !== 'versi_lama',
                ])>
            <span class="block text-xs text-slate-500">Baru membaca versi lama</span>
            <span class="text-2xl font-bold text-amber-700">{{ $this->summary['outdated'] }}</span>
        </button>

        <button type="button" wire:click="setStatus('belum')"
                @class([
                    'rounded-lg border p-4 text-left text-sm',
                    'border-red-500 bg-red-50 ring-1 ring-red-500' => $status === 'belum',
                    'border-slate-300 bg-white hover:bg-slate-50' => $status !== 'belum',
                ])>
            <span class="block text-xs text-slate-500">Belum membaca</span>
            <span class="text-2xl font-bold text-red-700">{{ $this->summary['missing'] }}</span>
        </button>
    </div>

    <div>
        <select wire:model.live="department"
                class="rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 sm:w-64">
            <option value="">Semua departemen</option>
            @foreach ($this->departments as $item)
                <option value="{{ $item }}">{{ $item }}</option>
            @endforeach
        </select>
    </div>

    <div wire:loading.delay class="text-sm text-slate-500">Memuat...</div>

    <div class="overflow-x-auto rounded-xl bg-white shadow-sm ring-1 ring-slate-200">
        <table class="min-w-full divide-y divide-slate-200 text-sm">
            <thead class="bg-slate-50 text-left text-xs font-semibold text-slate-700">
                <tr>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Departemen</th>
                    <th class="px-4 py-3">Status</th>
                    <th class="px-4 py-3">Keterangan</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse ($this->employees as $employee)
                    @php($state = \App\Services\ComplianceReport::statusOf($employee))

                    <tr wire:key="employee-{{ $employee->id }}" class="hover:bg-slate-50/60">
                        <td class="px-4 py-3 font-medium text-slate-800">{{ $employee->name }}</td>
                        <td class="px-4 py-3 text-slate-600">{{ $employee->department ?? '-' }}</td>
                        <td class="px-4 py-3">
                            @if ($state === 'sudah')
                                <span class="inline-block rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-medium text-emerald-700">Sudah membaca</span>
                            @elseif ($state === 'versi_lama')
                                <span class="inline-block rounded-full bg-amber-50 px-2.5 py-0.5 text-xs font-medium text-amber-700">Versi lama</span>
                            @else
                                <span class="inline-block rounded-full bg-red-50 px-2.5 py-0.5 text-xs font-medium text-red-700">Belum membaca</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-slate-600">
                            @if ($state === 'sudah')
                                Konfirmasi {{ \Illuminate\Support\Carbon::parse($employee->current_ack_at)->translatedFormat('d M Y, H:i') }}
                            @elseif ($state === 'versi_lama')
                                Terakhir membaca versi lama pada {{ \Illuminate\Support\Carbon::parse($employee->any_ack_at)->translatedFormat('d M Y') }}
                            @else
                                -
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="px-4 py-12 text-center text-slate-500">Tidak ada karyawan yang cocok dengan filter ini.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <x-pagination :paginator="$this->employees" />
    </div>
</div>