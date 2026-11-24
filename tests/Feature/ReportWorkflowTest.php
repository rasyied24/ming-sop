<?php

namespace Tests\Feature;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Enums\SopStatus;
use App\Enums\UserRole;
use App\Models\SopReport;
use App\Services\ReportWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\BuildsDomainData;
use Tests\TestCase;

class ReportWorkflowTest extends TestCase
{
    use BuildsDomainData;
    use RefreshDatabase;

    private ReportWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflow = app(ReportWorkflow::class);
    }

    public function test_karyawan_mengirim_laporan_dengan_jenis_dan_status_awal_menunggu(): void
    {
        $employee = $this->makeUser(UserRole::Employee);
        $sop = $this->makeSop(null, SopStatus::Published);

        $report = $this->workflow->submit($employee, [
            'sop_id' => $sop->id,
            'type' => ReportType::Violation,
            'subject' => 'Kata sandi dibagikan',
            'message' => 'Kata sandi sistem dibagikan lewat grup chat divisi.',
        ]);

        $this->assertSame(ReportStatus::Pending, $report->status);
        $this->assertSame(ReportType::Violation, $report->type);
        $this->assertSame($employee->id, $report->user_id);
        $this->assertSame($sop->id, $report->sop_id);
    }

    public function test_hanya_karyawan_yang_boleh_membuat_laporan(): void
    {
        foreach ([UserRole::PicSop, UserRole::Manager, UserRole::Auditor] as $role) {
            $user = $this->makeUser($role);

            $this->assertDenied(fn () => $this->workflow->submit($user, [
                'sop_id' => null,
                'type' => ReportType::SopIssue,
                'subject' => 'Tidak boleh',
                'message' => 'Role ini tidak berhak membuat laporan.',
            ]));
        }

        $this->assertDatabaseCount('sop_reports', 0);
    }

    public function test_auditor_menjalankan_alur_status_termasuk_membuka_kembali(): void
    {
        $auditor = $this->makeUser(UserRole::Auditor);
        $report = $this->makeReport($this->makeUser(UserRole::Employee));

        $report = $this->workflow->changeStatus($auditor, $report, ReportStatus::InProgress);
        $this->assertSame(ReportStatus::InProgress, $report->status);

        $report = $this->workflow->changeStatus($auditor, $report, ReportStatus::Resolved);
        $this->assertSame(ReportStatus::Resolved, $report->status);

        $report = $this->workflow->changeStatus($auditor, $report, ReportStatus::InProgress);
        $this->assertSame(ReportStatus::InProgress, $report->status);
    }

    public function test_perpindahan_status_yang_tidak_diizinkan_ditolak(): void
    {
        $auditor = $this->makeUser(UserRole::Auditor);
        $report = $this->makeReport($this->makeUser(UserRole::Employee), ['status' => ReportStatus::Resolved]);

        $this->expectException(ValidationException::class);

        $this->workflow->changeStatus($auditor, $report, ReportStatus::Pending);
    }

    public function test_status_yang_sama_tidak_boleh_diulang(): void
    {
        $auditor = $this->makeUser(UserRole::Auditor);
        $report = $this->makeReport($this->makeUser(UserRole::Employee));

        $this->expectException(ValidationException::class);

        $this->workflow->changeStatus($auditor, $report, ReportStatus::Pending);
    }

    public function test_selain_auditor_tidak_boleh_mengubah_status(): void
    {
        $owner = $this->makeUser(UserRole::Employee);
        $report = $this->makeReport($owner);

        foreach ([$owner, $this->makeUser(UserRole::Manager), $this->makeUser(UserRole::PicSop)] as $user) {
            $this->assertDenied(fn () => $this->workflow->changeStatus($user, $report, ReportStatus::Resolved));
        }

        $this->assertSame(ReportStatus::Pending, $report->fresh()->status);
    }

    public function test_policy_laporan_membatasi_pemilik_dan_auditor(): void
    {
        $owner = $this->makeUser(UserRole::Employee);
        $other = $this->makeUser(UserRole::Employee);
        $auditor = $this->makeUser(UserRole::Auditor);
        $report = $this->makeReport($owner);

        $this->assertTrue($owner->can('view', $report));
        $this->assertTrue($auditor->can('view', $report));
        $this->assertFalse($other->can('view', $report));
        $this->assertFalse($owner->can('updateStatus', $report));
        $this->assertTrue($auditor->can('updateStatus', $report));
        $this->assertTrue($auditor->can('viewAny', SopReport::class));
        $this->assertFalse($owner->can('viewAny', SopReport::class));
    }

    public function test_alur_status_laporan_didefinisikan_di_enum(): void
    {
        $this->assertSame([ReportStatus::InProgress, ReportStatus::Resolved], ReportStatus::Pending->allowedNext());
        $this->assertSame([ReportStatus::Resolved], ReportStatus::InProgress->allowedNext());
        $this->assertSame([ReportStatus::InProgress], ReportStatus::Resolved->allowedNext());
    }
}