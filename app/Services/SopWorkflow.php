<?php

namespace App\Services;

use App\Enums\SopAction;
use App\Enums\SopStatus;
use App\Models\Sop;
use App\Models\SopLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Satu-satunya tempat yang boleh mengubah status SOP.
 * Setiap method: cek hak akses, ubah data, dan catat audit trail dalam satu transaksi.
 */
class SopWorkflow
{
    /**
     * @param  array{title: string, document_number: string, category: string, content: string}  $data
     */
    public function createDraft(User $actor, array $data): Sop
    {
        Gate::forUser($actor)->authorize('create', Sop::class);

        return DB::transaction(function () use ($actor, $data) {
            $sop = Sop::create([
                'title' => $data['title'],
                'document_number' => $data['document_number'],
                'category' => $data['category'],
                'content' => $data['content'],
                'version' => '1.0',
                'status' => SopStatus::Draft,
                'created_by' => $actor->id,
            ]);

            $this->log($sop, $actor, SopAction::Created, "Draf SOP dibuat (v{$sop->version})");

            return $sop;
        });
    }

    /**
     * @param  array{title: string, category: string, content: string}  $data
     */
    public function updateDraft(User $actor, Sop $sop, array $data): Sop
    {
        return DB::transaction(function () use ($actor, $sop, $data) {
            $sop = $this->lock($sop);
            Gate::forUser($actor)->authorize('update', $sop);

            $sop->fill([
                'title' => $data['title'],
                'category' => $data['category'],
                'content' => $data['content'],
            ]);

            // Hanya catat log kalau memang ada yang berubah.
            if ($sop->isDirty()) {
                $sop->save();
                $this->log($sop, $actor, SopAction::Updated, "Draf diperbarui (v{$sop->version})");
            }

            return $sop;
        });
    }

    public function submitForReview(User $actor, Sop $sop): Sop
    {
        return DB::transaction(function () use ($actor, $sop) {
            $sop = $this->lock($sop);
            Gate::forUser($actor)->authorize('submitForReview', $sop);

            $sop->update([
                'status' => SopStatus::Review,
                'revision_note' => null,
            ]);

            $this->log($sop, $actor, SopAction::SubmittedForReview, "Diajukan untuk ditinjau (v{$sop->version})");

            return $sop;
        });
    }

    public function approve(User $actor, Sop $sop): Sop
    {
        return DB::transaction(function () use ($actor, $sop) {
            $sop = $this->lock($sop);
            Gate::forUser($actor)->authorize('approve', $sop);

            $sop->update([
                'status' => SopStatus::Published,
                'approved_by' => $actor->id,
                'revision_note' => null,
            ]);

            $this->log($sop, $actor, SopAction::Approved, "Disetujui dan diterbitkan (v{$sop->version})");

            return $sop;
        });
    }

    public function reject(User $actor, Sop $sop, string $note): Sop
    {
        return DB::transaction(function () use ($actor, $sop, $note) {
            $sop = $this->lock($sop);
            Gate::forUser($actor)->authorize('reject', $sop);

            $sop->update([
                'status' => SopStatus::Draft,
                'revision_note' => $note,
            ]);

            $this->log($sop, $actor, SopAction::Rejected, $note);

            return $sop;
        });
    }

    /**
     * Memulai revisi SOP yang sudah terbit: versi naik dan status kembali ke draf.
     */
    public function startRevision(User $actor, Sop $sop, bool $major = false): Sop
    {
        return DB::transaction(function () use ($actor, $sop, $major) {
            $sop = $this->lock($sop);
            Gate::forUser($actor)->authorize('revise', $sop);

            $oldVersion = $sop->version;
            $newVersion = $sop->nextVersion($major);

            $sop->update([
                'status' => SopStatus::Draft,
                'version' => $newVersion,
                'approved_by' => null,
                'revision_note' => null,
            ]);

            $this->log($sop, $actor, SopAction::Updated, "Revisi dimulai: v{$oldVersion} → v{$newVersion}");

            return $sop;
        });
    }

    public function archive(User $actor, Sop $sop): Sop
    {
        return DB::transaction(function () use ($actor, $sop) {
            $sop = $this->lock($sop);
            Gate::forUser($actor)->authorize('archive', $sop);

            $sop->update(['status' => SopStatus::Archived]);

            $this->log($sop, $actor, SopAction::Archived, "SOP diarsipkan (v{$sop->version})");

            return $sop;
        });
    }

    /**
     * Ambil ulang baris SOP dengan kunci, supaya pengecekan status memakai data terbaru
     * dan tidak ada dua orang yang mengubah SOP yang sama secara bersamaan.
     */
    private function lock(Sop $sop): Sop
    {
        return Sop::query()->lockForUpdate()->findOrFail($sop->id);
    }

    private function log(Sop $sop, User $actor, SopAction $action, ?string $notes = null): void
    {
        SopLog::create([
            'sop_id' => $sop->id,
            'user_id' => $actor->id,
            'action' => $action,
            'notes' => $notes,
        ]);
    }
}