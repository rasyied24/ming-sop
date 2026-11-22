<?php

namespace Database\Seeders;

use App\Enums\ReportStatus;
use App\Enums\ReportType;
use App\Models\SopReport;
use App\Models\Sop;
use App\Models\User;
use Illuminate\Database\Seeder;

class SopReportSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id', 'email');
        $sops = Sop::pluck('id', 'document_number');

        $reports = [
            [
                'user' => 'fitri.handayani@ming.test',
                'sop' => 'SOP/HRD/001',
                'type' => ReportType::SopIssue,
                'subject' => 'Alur persetujuan cuti tidak jelas',
                'message' => 'Saat mengajukan cuti, urutan persetujuan atasan dan HRD tidak dijelaskan sehingga pengajuan saya tertahan 4 hari. Mohon diperjelas batas waktu tiap tahap.',
                'status' => ReportStatus::InProgress,
                'days_ago' => 3,
            ],
            [
                'user' => 'joko.susilo@ming.test',
                'sop' => 'SOP/FIN/001',
                'type' => ReportType::SopIssue,
                'subject' => 'Batas nominal reimbursement perjalanan dinas',
                'message' => 'SOP belum menjelaskan batas nominal akomodasi dan uang makan untuk perjalanan dinas luar kota. Mohon dibuatkan tabel batas per kota.',
                'status' => ReportStatus::Pending,
                'days_ago' => 2,
            ],
            [
                'user' => 'gilang.ramadhan@ming.test',
                'sop' => 'SOP/IT/001',
                'type' => ReportType::Violation,
                'subject' => 'Dugaan pelanggaran: kata sandi dibagikan di grup chat',
                'message' => 'Saya melihat kata sandi akun sistem inventori dibagikan di grup WhatsApp divisi. Hal ini bertentangan dengan ketentuan kata sandi di SOP. Mohon ditindaklanjuti.',
                'status' => ReportStatus::InProgress,
                'days_ago' => 5,
            ],
            [
                'user' => 'lukman.hakim@ming.test',
                'sop' => null,
                'type' => ReportType::FieldMismatch,
                'subject' => 'Ruang arsip dokumen tidak terkunci di luar jam kerja',
                'message' => 'Beberapa kali saya mendapati ruang arsip di lantai 2 tidak terkunci setelah jam kerja. Belum ada SOP yang mengatur pengamanan ruang arsip.',
                'status' => ReportStatus::Pending,
                'days_ago' => 1,
            ],
            [
                'user' => 'dewi.anggraini@ming.test',
                'sop' => 'SOP/FIN/001',
                'type' => ReportType::FieldMismatch,
                'subject' => 'Bukti pembayaran digital ditolak sebagai lampiran',
                'message' => 'Bukti transfer dari aplikasi mobile banking ditolak oleh tim Keuangan padahal SOP menyebut salinan digital diperbolehkan.',
                'status' => ReportStatus::Resolved,
                'days_ago' => 15,
            ],
        ];

        foreach ($reports as $r) {
            $at = now()->subDays($r['days_ago'])->setTime(10, 0);

            SopReport::create([
                'sop_id' => $r['sop'] ? $sops[$r['sop']] : null,
                'user_id' => $users[$r['user']],
                'type' => $r['type'],
                'subject' => $r['subject'],
                'message' => $r['message'],
                'status' => $r['status'],
                'created_at' => $at,
                'updated_at' => $at,
            ]);
        }
    }
}