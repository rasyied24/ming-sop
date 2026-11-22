<?php

use App\Enums\ReportType;
use App\Enums\SopStatus;
use App\Models\Sop;
use App\Models\SopReport;
use App\Services\ReportWorkflow;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;

new #[Title('Buat Laporan')] class extends Component {
    // Diisi dari ?sop=ID saat karyawan menekan "Laporkan Masalah" di halaman detail SOP.
    // Bertipe string supaya nilai aneh di URL tidak menyebabkan error tipe data.
    #[Url(as: 'sop')]
    public string $sopId = '';

    // Sengaja tanpa nilai awal: pelapor harus memilih sendiri jenisnya.
    public string $type = '';

    public string $subject = '';

    public string $message = '';

    public function mount(): void
    {
        $this->authorize('create', SopReport::class);

        // Abaikan ?sop= kalau bukan SOP terbit yang valid.
        if ($this->sopId !== '' && ! $this->publishedSops->contains('id', (int) $this->sopId)) {
            $this->sopId = '';
        }
    }

    #[Computed]
    public function publishedSops()
    {
        return Sop::published()
            ->orderBy('document_number')
            ->get(['id', 'document_number', 'title']);
    }

    protected function rules(): array
    {
        return [
            'type' => ['required', Rule::enum(ReportType::class)],
            'sopId' => ['nullable', Rule::exists('sops', 'id')->where('status', SopStatus::Published->value)],
            'subject' => ['required', 'string', 'max:150'],
            'message' => ['required', 'string', 'min:20', 'max:2000'],
        ];
    }

    protected function messages(): array
    {
        return [
            'type.required' => 'Pilih jenis laporan.',
            'type.enum' => 'Jenis laporan tidak valid.',
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'min' => ':attribute minimal :min karakter agar Auditor memahami masalahnya.',
            'exists' => ':attribute tidak valid.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'sopId' => 'SOP terkait',
            'subject' => 'Subjek',
            'message' => 'Uraian masalah',
        ];
    }

    public function submit(ReportWorkflow $workflow): void
    {
        $this->validate();

        // Batasi 10 laporan per jam per user supaya form tidak dipakai membanjiri Auditor.
        $key = 'submit-report:'.auth()->id();

        if (RateLimiter::tooManyAttempts($key, 10)) {
            $seconds = RateLimiter::availableIn($key);

            throw ValidationException::withMessages([
                'subject' => "Terlalu banyak laporan dalam waktu singkat. Coba lagi dalam {$seconds} detik.",
            ]);
        }

        RateLimiter::hit($key, 3600);

        $workflow->submit(auth()->user(), [
            'sop_id' => $this->sopId !== '' ? (int) $this->sopId : null,
            'type' => ReportType::from($this->type),
            'subject' => $this->subject,
            'message' => $this->message,
        ]);

        session()->flash('status', 'Laporan berhasil dikirim. Tim Auditor/HRD akan menindaklanjutinya.');
        $this->redirect(route('reports.index'), navigate: true);
    }
};
?>

<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('reports.index') }}" wire:navigate class="text-sm text-brand-600 hover:underline">&larr; Kembali ke Laporan Saya</a>
        <h1 class="mt-2 text-2xl font-semibold">Buat Laporan</h1>
        <p class="mt-1 text-sm text-slate-500">
            Laporkan ketidaksesuaian di lapangan, kendala penerapan SOP, atau dugaan pelanggaran.
            Laporan ini dapat dilihat oleh tim Auditor/HRD beserta identitas Anda.
        </p>
    </div>

    <form wire:submit="submit" class="space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        {{-- Jenis laporan --}}
        <fieldset>
            <legend class="block text-sm font-medium text-slate-700">Jenis Laporan</legend>

            <div class="mt-2 grid gap-3 sm:grid-cols-3">
                @foreach (\App\Enums\ReportType::cases() as $option)
                    <label wire:key="type-{{ $option->value }}"
                           class="flex cursor-pointer flex-col rounded-lg border border-slate-300 bg-white p-4 text-sm hover:bg-slate-50 has-[:checked]:border-brand-500 has-[:checked]:bg-brand-50 has-[:checked]:ring-1 has-[:checked]:ring-brand-500">
                        <span class="flex items-center gap-2 font-medium text-slate-800">
                            <input type="radio" wire:model="type" value="{{ $option->value }}"
                                   class="size-4 border-slate-300 text-brand-600 focus:ring-brand-500">
                            {{ $option->label() }}
                        </span>
                        <span class="mt-2 text-xs leading-relaxed text-slate-500">{{ $option->description() }}</span>
                    </label>
                @endforeach
            </div>

            @error('type') <p class="mt-2 text-sm text-red-600">{{ $message }}</p> @enderror
        </fieldset>

        <div>
            <label for="sopId" class="block text-sm font-medium text-slate-700">SOP Terkait <span class="font-normal text-slate-400">(opsional)</span></label>
            <select id="sopId" wire:model="sopId"
                    class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                <option value="">Tidak terkait SOP tertentu</option>
                @foreach ($this->publishedSops as $item)
                    <option value="{{ $item->id }}">{{ $item->document_number }} &middot; {{ $item->title }}</option>
                @endforeach
            </select>
            @error('sopId') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="subject" class="block text-sm font-medium text-slate-700">Subjek</label>
            <input id="subject" type="text" wire:model="subject" maxlength="150"
                   class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
            @error('subject') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="message" class="block text-sm font-medium text-slate-700">Uraian Masalah</label>
            <textarea id="message" rows="8" wire:model="message" maxlength="2000"
                      class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
            @error('message') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('reports.index') }}" wire:navigate class="text-sm text-slate-600 hover:underline">Batal</a>

            <button type="submit" wire:loading.attr="disabled"
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500 disabled:opacity-60">
                Kirim Laporan
            </button>
        </div>
    </form>
</div>