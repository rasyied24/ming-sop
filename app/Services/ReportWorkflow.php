<?php

namespace App\Services;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\SopReport;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReportWorkflow
{
    /**
     * @param  array{sop_id: int|null, type: ReportType, subject: string, message: string}  $data
     */
    public function submit(User $actor, array $data): SopReport
    {
        Gate::forUser($actor)->authorize('create', SopReport::class);

        return SopReport::create([
            'sop_id' => $data['sop_id'],
            'user_id' => $actor->id,
            'type' => $data['type'],
            'subject' => $data['subject'],
            'message' => $data['message'],
            'status' => ReportStatus::Pending,
        ]);
    }

    public function changeStatus(User $actor, SopReport $report, ReportStatus $target): SopReport
    {
        return DB::transaction(function () use ($actor, $report, $target) {
            // Ambil ulang dengan kunci supaya dua Auditor tidak saling menimpa.
            $report = SopReport::query()->lockForUpdate()->findOrFail($report->id);

            Gate::forUser($actor)->authorize('updateStatus', $report);

            if (! in_array($target, $report->status->allowedNext(), true)) {
                throw ValidationException::withMessages([
                    'status' => "Status tidak bisa diubah dari {$report->status->label()} ke {$target->label()}. Muat ulang halaman untuk melihat status terbaru.",
                ]);
            }

            $report->update(['status' => $target]);

            return $report;
        });
    }
}