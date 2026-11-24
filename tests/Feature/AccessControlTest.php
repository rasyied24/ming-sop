<?php

namespace Tests\Feature;

use App\Enums\SopStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\BuildsDomainData;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use BuildsDomainData;
    use RefreshDatabase;

    /**
     * @return array<string, array{0: UserRole, 1: string, 2: int}>
     */
    public static function routeMatrix(): array
    {
        $matrix = [
            [UserRole::Employee, [
                'sops.index' => 200, 'reports.index' => 200, 'reports.create' => 200,
                'sops.manage' => 403, 'sops.create' => 403, 'dashboard' => 403,
                'approvals.index' => 403, 'compliance' => 403, 'reports.inbox' => 403,
            ]],
            [UserRole::PicSop, [
                'sops.index' => 200, 'sops.manage' => 200, 'sops.create' => 200,
                'dashboard' => 403, 'approvals.index' => 403, 'compliance' => 403,
                'reports.index' => 403, 'reports.create' => 403, 'reports.inbox' => 403,
            ]],
            [UserRole::Manager, [
                'sops.index' => 200, 'sops.manage' => 200, 'dashboard' => 200, 'approvals.index' => 200,
                'sops.create' => 403, 'compliance' => 403, 'reports.inbox' => 403,
                'reports.index' => 403, 'reports.create' => 403,
            ]],
            [UserRole::Auditor, [
                'sops.index' => 200, 'compliance' => 200, 'reports.inbox' => 200,
                'sops.manage' => 403, 'sops.create' => 403, 'dashboard' => 403,
                'approvals.index' => 403, 'reports.index' => 403, 'reports.create' => 403,
            ]],
        ];

        $cases = [];

        foreach ($matrix as [$role, $routes]) {
            foreach ($routes as $route => $status) {
                $cases["{$role->label()} membuka {$route} ({$status})"] = [$role, $route, $status];
            }
        }

        return $cases;
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function protectedRoutes(): array
    {
        $names = [
            'sops.index', 'sops.manage', 'sops.create', 'dashboard', 'approvals.index',
            'compliance', 'reports.index', 'reports.create', 'reports.inbox',
        ];

        return array_combine($names, array_map(fn (string $name) => [$name], $names));
    }

    #[DataProvider('routeMatrix')]
    public function test_akses_halaman_sesuai_role(UserRole $role, string $routeName, int $expected): void
    {
        $user = $this->makeUser($role);

        $this->actingAs($user)->get(route($routeName))->assertStatus($expected);
    }

    #[DataProvider('protectedRoutes')]
    public function test_tamu_diarahkan_ke_login(string $routeName): void
    {
        $this->get(route($routeName))->assertRedirect(route('login'));
    }

    public function test_halaman_utama_diarahkan_ke_login(): void
    {
        $this->get('/')->assertRedirect('/login');
    }

    public function test_user_yang_sudah_login_dialihkan_dari_halaman_login_ke_halaman_awal_rolenya(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = $this->makeUser($role);

            $this->actingAs($user)->get(route('login'))->assertRedirect(route($role->homeRoute()));
        }
    }

    public function test_karyawan_tidak_boleh_membuka_sop_yang_belum_terbit(): void
    {
        $employee = $this->makeUser(UserRole::Employee);

        foreach ([SopStatus::Draft, SopStatus::Review, SopStatus::Archived] as $status) {
            $sop = $this->makeSop(null, $status);

            $this->actingAs($employee)->get(route('sops.show', $sop))->assertForbidden();
        }
    }

    public function test_sop_terbit_boleh_dibuka_karyawan_dan_draf_boleh_dibuka_pic(): void
    {
        $employee = $this->makeUser(UserRole::Employee);
        $pic = $this->makeUser(UserRole::PicSop);

        $published = $this->makeSop($pic, SopStatus::Published);
        $draft = $this->makeSop($pic, SopStatus::Draft);

        $this->actingAs($employee)->get(route('sops.show', $published))->assertOk();
        $this->actingAs($pic)->get(route('sops.show', $draft))->assertOk();
    }

    public function test_halaman_ubah_hanya_untuk_draf_milik_pic(): void
    {
        $pic = $this->makeUser(UserRole::PicSop);
        $draft = $this->makeSop($pic, SopStatus::Draft);
        $published = $this->makeSop($pic, SopStatus::Published);
        $review = $this->makeSop($pic, SopStatus::Review);

        $this->actingAs($pic)->get(route('sops.edit', $draft))->assertOk();
        $this->actingAs($pic)->get(route('sops.edit', $published))->assertForbidden();
        $this->actingAs($pic)->get(route('sops.edit', $review))->assertForbidden();
    }

    public function test_laporan_hanya_dapat_dibuka_pemilik_dan_auditor(): void
    {
        $owner = $this->makeUser(UserRole::Employee);
        $other = $this->makeUser(UserRole::Employee);
        $auditor = $this->makeUser(UserRole::Auditor);
        $manager = $this->makeUser(UserRole::Manager);
        $pic = $this->makeUser(UserRole::PicSop);

        $report = $this->makeReport($owner);

        $this->actingAs($owner)->get(route('reports.show', $report))->assertOk();
        $this->actingAs($auditor)->get(route('reports.show', $report))->assertOk();
        $this->actingAs($other)->get(route('reports.show', $report))->assertForbidden();
        $this->actingAs($manager)->get(route('reports.show', $report))->assertForbidden();
        $this->actingAs($pic)->get(route('reports.show', $report))->assertForbidden();
    }

    public function test_rincian_kepatuhan_hanya_untuk_auditor_dan_sop_terbit(): void
    {
        $auditor = $this->makeUser(UserRole::Auditor);
        $manager = $this->makeUser(UserRole::Manager);

        $published = $this->makeSop(null, SopStatus::Published);
        $draft = $this->makeSop(null, SopStatus::Draft);

        $this->actingAs($auditor)->get(route('compliance.show', $published))->assertOk();
        $this->actingAs($auditor)->get(route('compliance.show', $draft))->assertNotFound();
        $this->actingAs($manager)->get(route('compliance.show', $published))->assertForbidden();
    }
}