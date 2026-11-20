<?php

namespace Database\Seeders;

use App\Enums\SopAction;
use App\Enums\UserRole;
use App\Models\Sop;
use App\Models\SopAcknowledgment;
use App\Models\User;
use Illuminate\Database\Seeder;

class SopAcknowledgmentSeeder extends Seeder
{
    public function run(): void
    {
        $employees = User::where('role', UserRole::Employee->value)
            ->orderBy('id')
            ->get();

        // [nomor dokumen, versi, jumlah karyawan yang sudah konfirmasi]
        $plan = [
            ['SOP/HRD/001', '1.1', 7],
            ['SOP/FIN/001', '1.0', 6],
            ['SOP/IT/001', '2.0', 4],
        ];

        foreach ($plan as [$number, $version, $count]) {
            $sop = Sop::where('document_number', $number)->firstOrFail();

            $publishedAt = $sop->logs()
                ->where('action', SopAction::Approved->value)
                ->latest('created_at')
                ->firstOrFail()
                ->created_at;

            foreach ($employees->take($count)->values() as $i => $employee) {
                $at = $publishedAt->copy()->addDays($i + 1)->setTime(8 + $i, 30);

                SopAcknowledgment::create([
                    'sop_id' => $sop->id,
                    'user_id' => $employee->id,
                    'sop_version' => $version,
                    'acknowledged_at' => $at,
                    'created_at' => $at,
                    'updated_at' => $at,
                ]);
            }
        }

        // Satu karyawan baru konfirmasi v1.0 Cuti dan belum konfirmasi v1.1,
        // untuk memperlihatkan pelacakan kepatuhan per versi.
        $cuti = Sop::where('document_number', 'SOP/HRD/001')->firstOrFail();
        $at = now()->subDays(35)->setTime(10, 0);

        SopAcknowledgment::create([
            'sop_id' => $cuti->id,
            'user_id' => $employees->get(7)->id,
            'sop_version' => '1.0',
            'acknowledged_at' => $at,
            'created_at' => $at,
            'updated_at' => $at,
        ]);
    }
}