<?php

namespace Tests\Concerns;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Enums\SopStatus;
use App\Enums\UserRole;
use App\Models\Sop;
use App\Models\SopAcknowledgment;
use App\Models\SopReport;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

trait BuildsDomainData
{
    private static int $sopSequence = 0;

    protected function makeUser(UserRole $role, array $attributes = []): User
    {
        return User::create(array_merge([
            'name' => $role->label().' '.Str::random(4),
            'email' => $role->value.'-'.Str::random(8).'@example.test',
            'password' => 'password',
            'role' => $role,
            'department' => 'HRD',
        ], $attributes));
    }

    /**
     * @return Collection<int, User>
     */
    protected function makeEmployees(int $count): Collection
    {
        return collect(range(1, $count))->map(fn () => $this->makeUser(UserRole::Employee));
    }

    protected function makeSop(?User $creator = null, SopStatus $status = SopStatus::Draft, array $attributes = []): Sop
    {
        self::$sopSequence++;
        $creator ??= $this->makeUser(UserRole::PicSop);

        return Sop::create(array_merge([
            'title' => 'SOP Uji '.self::$sopSequence,
            'document_number' => sprintf('SOP/TST/%03d', self::$sopSequence),
            'category' => 'HRD',
            'content' => 'Isi SOP uji untuk pengujian otomatis, lebih dari dua puluh karakter.',
            'version' => '1.0',
            'status' => $status,
            'created_by' => $creator->id,
        ], $attributes));
    }

    protected function makeReport(User $reporter, array $attributes = []): SopReport
    {
        return SopReport::create(array_merge([
            'user_id' => $reporter->id,
            'type' => ReportType::SopIssue,
            'subject' => 'Subjek laporan uji',
            'message' => 'Uraian laporan uji yang cukup panjang untuk lolos validasi.',
            'status' => ReportStatus::Pending,
        ], $attributes));
    }

    protected function acknowledge(User $user, Sop $sop, ?string $version = null): SopAcknowledgment
    {
        return SopAcknowledgment::create([
            'sop_id' => $sop->id,
            'user_id' => $user->id,
            'sop_version' => $version ?? $sop->version,
            'acknowledged_at' => now(),
        ]);
    }

    /**
     * Pastikan aksi ditolak Policy (AuthorizationException).
     */
    protected function assertDenied(callable $action): void
    {
        try {
            $action();
        } catch (AuthorizationException) {
            $this->addToAssertionCount(1);

            return;
        }

        $this->fail('Aksi seharusnya ditolak dengan AuthorizationException, tetapi berhasil dijalankan.');
    }
}