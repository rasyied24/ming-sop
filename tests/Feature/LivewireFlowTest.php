<?php

namespace Tests\Feature;

use App\Enums\SopStatus;
use App\Enums\UserRole;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\Concerns\BuildsDomainData;
use Tests\TestCase;

class LivewireFlowTest extends TestCase
{
    use BuildsDomainData;
    use RefreshDatabase;

    public function test_login_berhasil_mengarah_ke_halaman_awal_tiap_role(): void
    {
        foreach (UserRole::cases() as $role) {
            $user = $this->makeUser($role);

            Livewire::test('pages::auth.login')
                ->set('email', $user->email)
                ->set('password', 'password')
                ->call('login')
                ->assertRedirect(route($role->homeRoute()));

            $this->assertAuthenticatedAs($user);

            auth()->logout();
        }
    }

    public function test_login_gagal_dengan_kata_sandi_salah(): void
    {
        $user = $this->makeUser(UserRole::Employee);

        Livewire::test('pages::auth.login')
            ->set('email', $user->email)
            ->set('password', 'salah-total')
            ->call('login')
            ->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_login_dikunci_setelah_lima_percobaan_gagal(): void
    {
        $user = $this->makeUser(UserRole::Employee);

        $component = Livewire::test('pages::auth.login')
            ->set('email', $user->email);

        for ($i = 0; $i < 5; $i++) {
            $component->set('password', 'salah-total')->call('login');
        }

        // Percobaan keenam memakai kata sandi yang BENAR, tetapi tetap ditolak karena terkunci.
        $component->set('password', 'password')->call('login')->assertHasErrors(['email']);

        $this->assertGuest();
    }

    public function test_karyawan_mengonfirmasi_baca_satu_kali_per_versi(): void
    {
        $employee = $this->makeUser(UserRole::Employee);
        $sop = $this->makeSop(null, SopStatus::Published);

        $component = Livewire::actingAs($employee)->test('pages::sops.show', ['sop' => $sop]);

        $component->call('acknowledge');
        $component->call('acknowledge');

        $this->assertDatabaseCount('sop_acknowledgments', 1);
        $this->assertDatabaseHas('sop_acknowledgments', [
            'sop_id' => $sop->id,
            'user_id' => $employee->id,
            'sop_version' => '1.0',
        ]);
    }

    public function test_konfirmasi_ditolak_kalau_versi_berubah_setelah_halaman_dibuka(): void
    {
        $employee = $this->makeUser(UserRole::Employee);
        $sop = $this->makeSop(null, SopStatus::Published);

        $component = Livewire::actingAs($employee)->test('pages::sops.show', ['sop' => $sop]);

        $sop->update(['version' => '1.1']);

        $component->call('acknowledge')->assertHasErrors(['acknowledge']);

        $this->assertDatabaseCount('sop_acknowledgments', 0);
    }

    public function test_selain_karyawan_tidak_boleh_mengonfirmasi_baca(): void
    {
        $manager = $this->makeUser(UserRole::Manager);
        $sop = $this->makeSop(null, SopStatus::Published);

        Livewire::actingAs($manager)
            ->test('pages::sops.show', ['sop' => $sop])
            ->call('acknowledge')
            ->assertForbidden();

        $this->assertDatabaseCount('sop_acknowledgments', 0);
    }

    public function test_manager_menyetujui_sop_dari_halaman_detail(): void
    {
        $manager = $this->makeUser(UserRole::Manager);
        $sop = $this->makeSop(null, SopStatus::Review);

        Livewire::actingAs($manager)
            ->test('pages::sops.show', ['sop' => $sop])
            ->call('approve')
            ->assertRedirect(route('approvals.index'));

        $this->assertSame(SopStatus::Published, $sop->fresh()->status);
        $this->assertSame($manager->id, $sop->fresh()->approved_by);
    }

    public function test_penolakan_wajib_disertai_catatan_yang_cukup(): void
    {
        $manager = $this->makeUser(UserRole::Manager);
        $sop = $this->makeSop(null, SopStatus::Review);

        Livewire::actingAs($manager)
            ->test('pages::sops.show', ['sop' => $sop])
            ->set('rejectionNote', 'pendek')
            ->call('reject')
            ->assertHasErrors(['rejectionNote']);

        $this->assertSame(SopStatus::Review, $sop->fresh()->status);
    }

    public function test_karyawan_mengirim_laporan_lewat_form(): void
    {
        $employee = $this->makeUser(UserRole::Employee);

        Livewire::actingAs($employee)
            ->test('pages::reports.create')
            ->set('type', 'dugaan_pelanggaran')
            ->set('subject', 'Kata sandi dibagikan')
            ->set('message', 'Kata sandi sistem dibagikan lewat grup chat divisi.')
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('reports.index'));

        $this->assertDatabaseHas('sop_reports', [
            'user_id' => $employee->id,
            'type' => 'dugaan_pelanggaran',
            'status' => 'pending',
        ]);
    }

    public function test_form_laporan_menolak_jenis_kosong_dan_jenis_palsu(): void
    {
        $employee = $this->makeUser(UserRole::Employee);

        $component = Livewire::actingAs($employee)
            ->test('pages::reports.create')
            ->set('subject', 'Subjek')
            ->set('message', 'Uraian masalah yang lebih dari dua puluh karakter.');

        $component->call('submit')->assertHasErrors(['type']);
        $component->set('type', 'hack')->call('submit')->assertHasErrors(['type']);

        $this->assertDatabaseCount('sop_reports', 0);
    }
}