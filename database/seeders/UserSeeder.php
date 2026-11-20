<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $password = Hash::make('password');

        $users = [
            // Manager
            ['Budi Santoso', 'manager@ming.test', UserRole::Manager, 'Manajemen'],

            // PIC SOP
            ['Sari Wulandari', 'pic.hrd@ming.test', UserRole::PicSop, 'HRD'],
            ['Andi Pratama', 'pic.it@ming.test', UserRole::PicSop, 'IT'],
            ['Dewi Lestari', 'pic.keuangan@ming.test', UserRole::PicSop, 'Keuangan'],

            // Auditor / HRD
            ['Rina Kusuma', 'auditor@ming.test', UserRole::Auditor, 'HRD'],

            // Karyawan
            ['Ahmad Fauzi', 'ahmad.fauzi@ming.test', UserRole::Employee, 'Keuangan'],
            ['Dewi Anggraini', 'dewi.anggraini@ming.test', UserRole::Employee, 'Marketing'],
            ['Eko Prasetyo', 'eko.prasetyo@ming.test', UserRole::Employee, 'IT'],
            ['Fitri Handayani', 'fitri.handayani@ming.test', UserRole::Employee, 'Operasional'],
            ['Gilang Ramadhan', 'gilang.ramadhan@ming.test', UserRole::Employee, 'IT'],
            ['Hana Safitri', 'hana.safitri@ming.test', UserRole::Employee, 'HRD'],
            ['Irfan Maulana', 'irfan.maulana@ming.test', UserRole::Employee, 'Operasional'],
            ['Joko Susilo', 'joko.susilo@ming.test', UserRole::Employee, 'Keuangan'],
            ['Kartika Putri', 'kartika.putri@ming.test', UserRole::Employee, 'Marketing'],
            ['Lukman Hakim', 'lukman.hakim@ming.test', UserRole::Employee, 'Operasional'],
        ];

        foreach ($users as [$name, $email, $role, $department]) {
            User::create([
                'name' => $name,
                'email' => $email,
                'password' => $password,
                'role' => $role,
                'department' => $department,
                'email_verified_at' => now(),
            ]);
        }
    }
}