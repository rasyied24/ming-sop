<?php

namespace Tests\Feature;

use App\Enums\SopStatus;
use App\Enums\UserRole;
use App\Services\ComplianceReport;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsDomainData;
use Tests\TestCase;

class ComplianceReportTest extends TestCase
{
    use BuildsDomainData;
    use RefreshDatabase;

    private ComplianceReport $report;

    protected function setUp(): void
    {
        parent::setUp();

        $this->report = app(ComplianceReport::class);
    }

    public function test_kepatuhan_per_sop_dan_keseluruhan(): void
    {
        $employees = $this->makeEmployees(4);
        $a = $this->makeSop(null, SopStatus::Published);
        $b = $this->makeSop(null, SopStatus::Published);

        $employees->take(3)->each(fn ($employee) => $this->acknowledge($employee, $a));
        $this->acknowledge($employees->first(), $b);

        $perSop = $this->report->perSop();

        $this->assertEquals(3, $perSop->firstWhere('id', $a->id)->acknowledged_count);
        $this->assertEquals(75, $perSop->firstWhere('id', $a->id)->percentage);
        $this->assertEquals(1, $perSop->firstWhere('id', $b->id)->acknowledged_count);
        $this->assertEquals(25, $perSop->firstWhere('id', $b->id)->percentage);

        $overall = $this->report->overall($perSop);

        $this->assertEquals(4, $overall['employees']);
        $this->assertEquals(2, $overall['sops']);
        $this->assertEquals(8, $overall['expected']);
        $this->assertEquals(4, $overall['acknowledged']);
        $this->assertEquals(50, $overall['percentage']);
    }

    public function test_hanya_sop_terbit_yang_dihitung(): void
    {
        $this->makeEmployees(2);
        $published = $this->makeSop(null, SopStatus::Published);

        foreach ([SopStatus::Draft, SopStatus::Review, SopStatus::Archived] as $status) {
            $this->makeSop(null, $status);
        }

        $perSop = $this->report->perSop();

        $this->assertCount(1, $perSop);
        $this->assertSame($published->id, $perSop->first()->id);
    }

    public function test_konfirmasi_versi_lama_tidak_dihitung(): void
    {
        $employee = $this->makeEmployees(1)->first();
        $sop = $this->makeSop(null, SopStatus::Published, ['version' => '1.1']);

        $this->acknowledge($employee, $sop, '1.0');

        $this->assertEquals(0, $this->report->perSop()->first()->acknowledged_count);
    }

    public function test_konfirmasi_dari_non_karyawan_tidak_dihitung(): void
    {
        $this->makeEmployees(2);
        $pic = $this->makeUser(UserRole::PicSop);
        $sop = $this->makeSop($pic, SopStatus::Published);

        $this->acknowledge($pic, $sop);

        $this->assertEquals(0, $this->report->perSop()->first()->acknowledged_count);
    }

    public function test_tanpa_karyawan_atau_tanpa_sop_hasilnya_nol_tanpa_error(): void
    {
        $this->makeSop(null, SopStatus::Published);

        $perSop = $this->report->perSop();

        $this->assertEquals(0, $perSop->first()->percentage);
        $this->assertEquals(0, $this->report->overall($perSop)['percentage']);
        $this->assertSame(0, $this->report->percentage(0, 0));
    }

    public function test_ringkasan_satu_sop_berjumlah_sama_dengan_total_karyawan(): void
    {
        [$e1, $e2, $e3] = $this->makeEmployees(5)->all();
        $sop = $this->makeSop(null, SopStatus::Published, ['version' => '1.1']);

        $this->acknowledge($e1, $sop);
        $this->acknowledge($e2, $sop);
        $this->acknowledge($e3, $sop, '1.0');

        $summary = $this->report->summaryFor($sop);

        $this->assertEquals(2, $summary['acknowledged']);
        $this->assertEquals(1, $summary['outdated']);
        $this->assertEquals(2, $summary['missing']);
        $this->assertEquals(40, $summary['percentage']);
        $this->assertEquals($summary['employees'], $summary['acknowledged'] + $summary['outdated'] + $summary['missing']);
    }

    public function test_penyaringan_dan_status_karyawan(): void
    {
        [$current, $outdated, $missing] = $this->makeEmployees(3)->all();
        $sop = $this->makeSop(null, SopStatus::Published, ['version' => '1.1']);

        $this->acknowledge($current, $sop);
        $this->acknowledge($outdated, $sop, '1.0');

        $idsFor = fn (string $status) => $this->report
            ->filterByStatus($this->report->employeesQuery($sop), $sop, $status)
            ->get()
            ->pluck('id')
            ->all();

        $this->assertSame([$current->id], $idsFor('sudah'));
        $this->assertSame([$outdated->id], $idsFor('versi_lama'));
        $this->assertSame([$missing->id], $idsFor('belum'));
        $this->assertCount(3, $idsFor(''));

        $rows = $this->report->employeesQuery($sop)->get()->keyBy('id');

        $this->assertSame('sudah', ComplianceReport::statusOf($rows[$current->id]));
        $this->assertSame('versi_lama', ComplianceReport::statusOf($rows[$outdated->id]));
        $this->assertSame('belum', ComplianceReport::statusOf($rows[$missing->id]));
    }

    public function test_database_menolak_konfirmasi_ganda_untuk_versi_yang_sama(): void
    {
        $employee = $this->makeEmployees(1)->first();
        $sop = $this->makeSop(null, SopStatus::Published);

        $this->acknowledge($employee, $sop);

        $this->expectException(QueryException::class);

        $this->acknowledge($employee, $sop);
    }
}