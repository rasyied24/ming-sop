<?php

use App\Models\Sop;
use App\Services\SopWorkflow;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Form SOP')] class extends Component {
    public ?Sop $sop = null;

    /** @var array{title: string, document_number: string, category: string, content: string} */
    public array $form = [
        'title' => '',
        'document_number' => '',
        'category' => '',
        'content' => '',
    ];

    public function mount(?Sop $sop = null): void
    {
        if ($sop) {
            $this->authorize('update', $sop);

            $this->sop = $sop;
            $this->form = [
                'title' => $sop->title,
                'document_number' => $sop->document_number,
                'category' => $sop->category,
                'content' => $sop->content,
            ];

            return;
        }

        $this->authorize('create', Sop::class);
    }

    protected function rules(): array
    {
        return [
            'form.title' => ['required', 'string', 'max:255'],
            // Nomor dokumen hanya divalidasi saat membuat. Saat mengubah, nomor tidak boleh berubah.
            'form.document_number' => $this->sop
                ? ['nullable']
                : ['required', 'regex:/^SOP\/[A-Z]{2,5}\/\d{3}$/', Rule::unique('sops', 'document_number')],
            'form.category' => ['required', Rule::in(Sop::CATEGORIES)],
            'form.content' => ['required', 'string', 'min:20'],
        ];
    }

    protected function messages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'max' => ':attribute maksimal :max karakter.',
            'min' => ':attribute minimal :min karakter.',
            'unique' => ':attribute sudah digunakan.',
            'in' => ':attribute tidak valid.',
            'regex' => 'Format :attribute tidak valid. Contoh: SOP/HRD/001.',
        ];
    }

    protected function validationAttributes(): array
    {
        return [
            'form.title' => 'Judul',
            'form.document_number' => 'Nomor dokumen',
            'form.category' => 'Kategori',
            'form.content' => 'Isi SOP',
        ];
    }

    public function saveDraft(SopWorkflow $workflow): void
    {
        $this->validate();

        $this->persist($workflow);

        session()->flash('status', 'Draf SOP berhasil disimpan.');
        $this->redirect(route('sops.manage'), navigate: true);
    }

    public function saveAndSubmit(SopWorkflow $workflow): void
    {
        $this->validate();

        // Simpan dan ajukan dalam satu transaksi: kalau pengajuan gagal, penyimpanan ikut dibatalkan.
        DB::transaction(function () use ($workflow) {
            $sop = $this->persist($workflow);
            $workflow->submitForReview(auth()->user(), $sop);
        });

        session()->flash('status', 'SOP berhasil diajukan ke Manager untuk ditinjau.');
        $this->redirect(route('sops.manage'), navigate: true);
    }

    private function persist(SopWorkflow $workflow): Sop
    {
        $user = auth()->user();

        if ($this->sop) {
            return $workflow->updateDraft($user, $this->sop, [
                'title' => $this->form['title'],
                'category' => $this->form['category'],
                'content' => $this->form['content'],
            ]);
        }

        return $workflow->createDraft($user, $this->form);
    }
};
?>

<div class="mx-auto max-w-3xl space-y-6">
    <div>
        <a href="{{ route('sops.manage') }}" wire:navigate class="text-sm text-brand-600 hover:underline">&larr; Kembali ke Direktori SOP</a>
        <h1 class="mt-2 text-2xl font-semibold">
            {{ $sop ? 'Ubah Draf SOP' : 'Buat SOP Baru' }}
            @if ($sop)
                <span class="text-base font-normal text-slate-500">&middot; versi {{ $sop->version }}</span>
            @endif
        </h1>
    </div>

    @if ($sop?->revision_note)
        <div class="rounded-md bg-amber-50 px-4 py-3 text-sm text-amber-800">
            <p class="font-medium">Catatan revisi dari Manager:</p>
            <p class="mt-1">{{ $sop->revision_note }}</p>
        </div>
    @endif

    <form wire:submit="saveDraft" class="space-y-5 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
        <div class="grid gap-5 sm:grid-cols-2">
            <div>
                <label for="document_number" class="block text-sm font-medium text-slate-700">Nomor Dokumen</label>
                <input id="document_number" type="text" wire:model="form.document_number"
                       placeholder="SOP/HRD/004" @readonly((bool) $sop)
                       class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500 read-only:bg-slate-100 read-only:text-slate-500">
                @error('form.document_number') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="category" class="block text-sm font-medium text-slate-700">Kategori</label>
                <select id="category" wire:model="form.category"
                        class="mt-1 block w-full rounded-md border border-slate-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
                    <option value="">Pilih kategori</option>
                    @foreach (\App\Models\Sop::CATEGORIES as $item)
                        <option value="{{ $item }}">{{ $item }}</option>
                    @endforeach
                </select>
                @error('form.category') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label for="sop_title" class="block text-sm font-medium text-slate-700">Judul SOP</label>
            <input id="sop_title" type="text" wire:model="form.title"
                   class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500">
            @error('form.title') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="content" class="block text-sm font-medium text-slate-700">Isi SOP</label>
            <p class="text-xs text-slate-500">Gunakan Markdown: <code>## Judul bagian</code>, <code>- poin</code>, <code>1. langkah</code>, <code>**tebal**</code>.</p>
            <textarea id="content" rows="18" wire:model="form.content"
                      class="mt-1 block w-full rounded-md border border-slate-300 px-3 py-2 font-mono text-sm shadow-sm focus:border-brand-500 focus:outline-none focus:ring-1 focus:ring-brand-500"></textarea>
            @error('form.content') <p class="mt-1 text-sm text-red-600">{{ $message }}</p> @enderror
        </div>

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-slate-100 pt-5">
            <a href="{{ route('sops.manage') }}" wire:navigate class="text-sm text-slate-600 hover:underline">Batal</a>

            <button type="submit" wire:loading.attr="disabled"
                    class="rounded-md border border-slate-300 bg-white px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50 disabled:opacity-60">
                Simpan Draf
            </button>

            <button type="button" wire:click="saveAndSubmit" wire:loading.attr="disabled"
                    wire:confirm="Ajukan SOP ini ke Manager untuk ditinjau? Setelah diajukan, draf tidak bisa diubah sampai ditolak atau disetujui."
                    class="rounded-md bg-brand-600 px-4 py-2 text-sm font-semibold text-white hover:bg-brand-500 disabled:opacity-60">
                Simpan &amp; Ajukan untuk Review
            </button>
        </div>
    </form>
</div>