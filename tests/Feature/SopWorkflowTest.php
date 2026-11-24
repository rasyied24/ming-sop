<?php

namespace Tests\Feature;

use App\Enums\SopAction;
use App\Enums\SopStatus;
use App\Enums\UserRole;
use App\Models\Sop;
use App\Services\SopWorkflow;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\BuildsDomainData;
use Tests\TestCase;

class SopWorkflowTest extends TestCase
{
    use BuildsDomainData;
    use RefreshDatabase;

    private SopWorkflow $workflow;

    protected function setUp(): void
    {
        parent::setUp();

        $this->workflow = app(SopWorkflow::class);
    }

    /**
     * @return array<int, string>
     */
    private function actions(Sop $sop): array
    {
        return $sop->logs()->orderBy('id')->get()->map(fn ($log) => $log->action->value)->all();
    }

    /**
     * @return array{title: string, document_number: string, category: string, content: string}
     */
    private function payload(string $number = 'SOP/TST/900'): array
    {
        return [
            'title' => 'SOP Uji Alur',
            'document_number' => $number,
            'category' => 'IT',
            'content' => 'Isi SOP uji alur yang lebih dari dua puluh karakter.',
        ];
    }

    public function test_pic_membuat_draf_dan_tercatat_di_audit_trail(): void
    {
        $pic = $this->makeUser(UserRole::PicSop);

        $sop = $this->workflow->createDraft($pic, $this->payload());

        $this->assertSame(SopStatus::Draft, $sop->status);
        $this->assertSame('1.0', $sop->version);
        $this->assertSame($pic->id, $sop->created_by);
        $this->assertSame(['created'], $this->actions($sop));
    }

    public function test_selain_pic_tidak_boleh_membuat_draf(): void
    {
        foreach ([UserRole::Employee, UserRole::Manager, UserRole::Auditor] as $role) {
            $user = $this->makeUser($role);

            $this->assertDenied(fn () => $this->workflow->createDraft($user, $this->payload()));
        }

        $this->assertDatabaseCount('sops', 0);
    }

    public function test_siklus_penuh_draf_review_terbit(): void
    {
        $pic = $this->makeUser(UserRole::PicSop);
        $manager = $this->makeUser(UserRole::Manager);

        $sop = $this->workflow->createDraft($pic, $this->payload());
        $this->workflow->submitForReview($pic, $sop);
        $sop = $this->workflow->approve($manager, $sop);

        $this->assertSame(SopStatus::Published, $sop->status);
        $this->assertSame($manager->id, $sop->approved_by);
        $this->assertSame(['created', 'submitted_for_review', 'approved'], $this->actions($sop));
    }

    public function test_draf_yang_belum_diajukan_tidak_bisa_disetujui(): void
    {
        $manager = $this->makeUser(UserRole::Manager);
        $draft = $this->makeSop(null, SopStatus::Draft);

        $this->assertDenied(fn () => $this->workflow->approve($manager, $draft));
        $this->assertSame(SopStatus::Draft, $draft->fresh()->status);
    }

    public function test_pic_tidak_boleh_menyetujui_dan_manager_tidak_boleh_mengajukan(): void
    {
        $pic = $this->makeUser(UserRole::PicSop);
        $manager = $this->makeUser(UserRole::Manager);

        $review = $this->makeSop($pic, SopStatus::Review);
        $draft = $this->makeSop($pic, SopStatus::Draft);

        $this->assertDenied(fn () => $this->workflow->approve($pic, $review));
        $this->assertDenied(fn () => $this->workflow->submitForReview($manager, $draft));
    }

    public function test_penolakan_menyimpan_catatan_lalu_diajukan_ulang_membersihkannya(): void
    {
        $pic = $this->makeUser(UserRole::PicSop);
        $manager = $this->makeUser(UserRole::Manager);
        $sop = $this->makeSop($pic, SopStatus::Review);

        $sop = $this->workflow->reject($manager, $sop, 'Lengkapi langkah verifikasi.');

        $this->assertSame(SopStatus::Draft, $sop->status);
        $this->assertSame('Lengkapi langkah verifikasi.', $sop->revision_note);

        $log = $sop->logs()->latest('id')->first();
        $this->assertSame(SopAction::Rejected, $log->action);
        $this->assertSame('Lengkapi langkah verifikasi.', $log->notes);

        $sop = $this->workflow->submitForReview($pic, $sop);

        $this->assertSame(SopStatus::Review, $sop->status);
        $this->assertNull($sop->revision_note);
    }

    public function test_draf_terkunci_selama_review(): void
    {
        $pic = $this->makeUser(UserRole::PicSop);
        $review = $this->makeSop($pic, SopStatus::Review);

        $this->assertDenied(fn () => $this->workflow->updateDraft($pic, $review, [
            'title' => 'Diubah diam-diam',
            'category' => 'IT',
            'content' => 'Isi baru yang lebih dari dua puluh karakter.',
        ]));

        $this->assertNotSame('Diubah diam-diam', $review->fresh()->title);
    }

    public function test_log_pembaruan_hanya_dicatat_kalau_ada_perubahan(): void
    {
        $pic = $this->makeUser(UserRole::PicSop);
        $sop = $this->makeSop($pic, SopStatus::Draft);

        $same = ['title' => $sop->title, 'category' => $sop->category, 'content' => $sop->content];

        $this->workflow->updateDraft($pic, $sop, $same);
        $this->assertSame([], $this->actions($sop));

        $this->workflow->updateDraft($pic, $sop, [...$same, 'title' => 'Judul Baru']);
        $this->assertSame(['updated'], $this->actions($sop));
    }

    public function test_revisi_minor_dan_mayor_menaikkan_versi_dan_mengembalikan_ke_draf(): void
    {
        $pic = $this->makeUser(UserRole::PicSop);
        $manager = $this->makeUser(UserRole::Manager);

        $a = $this->makeSop($pic, SopStatus::Published, ['version' => '1.1', 'approved_by' => $manager->id]);
        $b = $this->makeSop($pic, SopStatus::Published, ['version' => '1.1', 'approved_by' => $manager->id]);

        $minor = $this->workflow->startRevision($pic, $a);
        $major = $this->workflow->startRevision($pic, $b, major: true);

        $this->assertSame('1.2', $minor->version);
        $this->assertSame('2.0', $major->version);

        foreach ([$minor, $major] as $revised) {
            $this->assertSame(SopStatus::Draft, $revised->status);
            $this->assertNull($revised->approved_by);
        }

        $this->assertStringContainsString('v1.1', $minor->logs()->latest('id')->first()->notes);
        $this->assertStringContainsString('v1.2', $minor->logs()->latest('id')->first()->notes);
    }

    public function test_revisi_hanya_untuk_sop_terbit(): void
    {
        $pic = $this->makeUser(UserRole::PicSop);
        $draft = $this->makeSop($pic, SopStatus::Draft);

        $this->assertDenied(fn () => $this->workflow->startRevision($pic, $draft));
    }

    public function test_hanya_manager_yang_boleh_mengarsipkan_sop_terbit(): void
    {
        $pic = $this->makeUser(UserRole::PicSop);
        $manager = $this->makeUser(UserRole::Manager);

        $published = $this->makeSop($pic, SopStatus::Published);
        $draft = $this->makeSop($pic, SopStatus::Draft);

        $this->assertDenied(fn () => $this->workflow->archive($pic, $published));
        $this->assertDenied(fn () => $this->workflow->archive($manager, $draft));

        $archived = $this->workflow->archive($manager, $published);

        $this->assertSame(SopStatus::Archived, $archived->status);
        $this->assertSame(['archived'], $this->actions($archived));
    }

    public function test_perhitungan_nomor_versi_berikutnya(): void
    {
        $cases = [
            ['1.1', false, '1.2'],
            ['1.1', true, '2.0'],
            ['2.0', false, '2.1'],
            ['2.0', true, '3.0'],
            ['1', false, '1.1'],
            ['1', true, '2.0'],
        ];

        foreach ($cases as [$current, $major, $expected]) {
            $this->assertSame($expected, (new Sop(['version' => $current]))->nextVersion($major));
        }
    }
}