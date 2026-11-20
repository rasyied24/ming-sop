<?php

namespace Database\Seeders;

use App\Enums\SopAction;
use App\Enums\SopStatus;
use App\Models\Sop;
use App\Models\SopLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class SopSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::pluck('id', 'email');

        foreach ($this->sops() as $data) {
            $timeline = $data['timeline'];
            unset($data['timeline']);

            // Jam bertambah per entri supaya urutan log di hari yang sama tetap benar.
            $times = collect($timeline)->map(
                fn (array $entry, int $i) => now()->subDays($entry[3])->setTime(9 + $i, 0)
            );

            $sop = Sop::create([
                ...$data,
                'created_by' => $users[$data['created_by']],
                'approved_by' => $data['approved_by'] ? $users[$data['approved_by']] : null,
                'created_at' => $times->first(),
                'updated_at' => $times->last(),
            ]);

            foreach ($timeline as $i => [$action, $email, $notes]) {
                SopLog::create([
                    'sop_id' => $sop->id,
                    'user_id' => $users[$email],
                    'action' => $action,
                    'notes' => $notes,
                    'created_at' => $times[$i],
                    'updated_at' => $times[$i],
                ]);
            }
        }
    }

    /**
     * Setiap entri timeline: [action, email user, catatan, berapa hari lalu].
     */
    private function sops(): array
    {
        return [
            // 1. Published, sudah direvisi v1.0 -> v1.1
            [
                'document_number' => 'SOP/HRD/001',
                'title' => 'Prosedur Pengajuan Cuti Karyawan',
                'category' => 'HRD',
                'version' => '1.1',
                'status' => SopStatus::Published->value,
                'created_by' => 'pic.hrd@ming.test',
                'approved_by' => 'manager@ming.test',
                'content' => <<<'MD'
## 1. Tujuan
Mengatur tata cara pengajuan, persetujuan, dan pencatatan cuti karyawan agar operasional tetap berjalan lancar.

## 2. Ruang Lingkup
Berlaku untuk seluruh karyawan tetap dan karyawan kontrak yang telah melewati masa percobaan.

## 3. Ketentuan
- Cuti tahunan sebanyak 12 hari kerja per tahun.
- Pengajuan cuti paling lambat **H-3** sebelum tanggal cuti.
- Cuti setengah hari diperbolehkan maksimal 2 kali dalam satu bulan.
- Cuti lebih dari 5 hari berturut-turut wajib dikoordinasikan dengan atasan minimal 2 minggu sebelumnya.

## 4. Prosedur
1. Karyawan mengisi formulir cuti pada sistem HRIS.
2. Atasan langsung meninjau dan memberi persetujuan maksimal 1 hari kerja.
3. HRD memverifikasi sisa kuota cuti dan mencatatnya.
4. Karyawan menerima notifikasi hasil pengajuan melalui email.

## 5. Tanggung Jawab
- **Karyawan**: mengajukan cuti sesuai ketentuan.
- **Atasan**: menyetujui atau menolak dengan alasan jelas.
- **HRD**: memelihara data kuota cuti.
MD,
                'timeline' => [
                    [SopAction::Created, 'pic.hrd@ming.test', 'Draf awal SOP Cuti v1.0', 45],
                    [SopAction::SubmittedForReview, 'pic.hrd@ming.test', 'Diajukan untuk ditinjau Manager', 44],
                    [SopAction::Approved, 'manager@ming.test', 'Disetujui dan diterbitkan (v1.0)', 42],
                    [SopAction::Updated, 'pic.hrd@ming.test', 'Revisi v1.0 → v1.1: menambahkan ketentuan cuti setengah hari dan batas pengajuan H-3', 14],
                    [SopAction::SubmittedForReview, 'pic.hrd@ming.test', 'Diajukan untuk ditinjau (v1.1)', 14],
                    [SopAction::Approved, 'manager@ming.test', 'Disetujui dan diterbitkan (v1.1)', 12],
                ],
            ],

            // 2. Published
            [
                'document_number' => 'SOP/FIN/001',
                'title' => 'Prosedur Reimbursement Biaya Operasional',
                'category' => 'Keuangan',
                'version' => '1.0',
                'status' => SopStatus::Published->value,
                'created_by' => 'pic.keuangan@ming.test',
                'approved_by' => 'manager@ming.test',
                'content' => <<<'MD'
## 1. Tujuan
Memastikan penggantian biaya operasional karyawan dilakukan tertib, akuntabel, dan tepat waktu.

## 2. Ruang Lingkup
Seluruh biaya operasional yang dikeluarkan karyawan untuk kepentingan perusahaan, seperti transportasi dinas, akomodasi, dan konsumsi rapat.

## 3. Ketentuan
- Pengajuan maksimal **14 hari kalender** setelah pengeluaran.
- Wajib melampirkan bukti pembayaran asli atau salinan digital yang jelas.
- Biaya di atas Rp 1.000.000 memerlukan persetujuan Manager.
- Biaya yang tidak sesuai kebijakan perusahaan tidak akan diganti.

## 4. Prosedur
1. Karyawan mengisi formulir reimbursement dan melampirkan bukti.
2. Atasan langsung memberi persetujuan.
3. Tim Keuangan memverifikasi kelengkapan dan kewajaran nominal.
4. Pembayaran ditransfer ke rekening karyawan dalam 5 hari kerja setelah disetujui.

## 5. Tanggung Jawab
- **Karyawan**: kebenaran data dan keaslian bukti.
- **Tim Keuangan**: verifikasi dan pembayaran tepat waktu.
MD,
                'timeline' => [
                    [SopAction::Created, 'pic.keuangan@ming.test', 'Draf awal SOP Reimbursement', 32],
                    [SopAction::SubmittedForReview, 'pic.keuangan@ming.test', 'Diajukan untuk ditinjau Manager', 31],
                    [SopAction::Approved, 'manager@ming.test', 'Disetujui dan diterbitkan (v1.0)', 30],
                ],
            ],

            // 3. Published, major revision v1.0 -> v2.0
            [
                'document_number' => 'SOP/IT/001',
                'title' => 'Kebijakan Keamanan Informasi dan Penggunaan Perangkat',
                'category' => 'IT',
                'version' => '2.0',
                'status' => SopStatus::Published->value,
                'created_by' => 'pic.it@ming.test',
                'approved_by' => 'manager@ming.test',
                'content' => <<<'MD'
## 1. Tujuan
Melindungi aset informasi perusahaan dari akses tidak sah, kebocoran, dan penyalahgunaan.

## 2. Ruang Lingkup
Seluruh karyawan, kontraktor, dan pihak ketiga yang menggunakan sistem atau perangkat milik perusahaan.

## 3. Ketentuan
- Kata sandi minimal 12 karakter dan **tidak boleh dibagikan** kepada siapa pun.
- Autentikasi dua faktor (2FA) wajib aktif untuk email dan sistem internal.
- Perangkat kerja wajib dikunci otomatis setelah 5 menit tidak aktif.
- Dilarang memasang perangkat lunak tanpa izin tim IT.
- Data rahasia tidak boleh disimpan di penyimpanan pribadi atau dikirim lewat aplikasi pesan pribadi.

## 4. Pelaporan Insiden
Segala dugaan insiden keamanan wajib dilaporkan ke tim IT maksimal **1 jam** setelah diketahui.

## 5. Sanksi
Pelanggaran dikenakan sanksi sesuai peraturan perusahaan, mulai dari teguran hingga tindakan disipliner.
MD,
                'timeline' => [
                    [SopAction::Created, 'pic.it@ming.test', 'Draf awal kebijakan keamanan v1.0', 90],
                    [SopAction::SubmittedForReview, 'pic.it@ming.test', 'Diajukan untuk ditinjau Manager', 89],
                    [SopAction::Approved, 'manager@ming.test', 'Disetujui dan diterbitkan (v1.0)', 88],
                    [SopAction::Updated, 'pic.it@ming.test', 'Revisi v1.0 → v2.0: menambahkan kewajiban 2FA dan aturan penyimpanan data rahasia', 21],
                    [SopAction::SubmittedForReview, 'pic.it@ming.test', 'Diajukan untuk ditinjau (v2.0)', 21],
                    [SopAction::Approved, 'manager@ming.test', 'Disetujui dan diterbitkan (v2.0)', 20],
                ],
            ],

            // 4. Review (Pending Approval)
            [
                'document_number' => 'SOP/OPS/001',
                'title' => 'Prosedur Pengadaan Barang dan Jasa',
                'category' => 'Operasional',
                'version' => '1.0',
                'status' => SopStatus::Review->value,
                'created_by' => 'pic.keuangan@ming.test',
                'approved_by' => null,
                'content' => <<<'MD'
## 1. Tujuan
Mengatur proses pengadaan barang dan jasa agar transparan, efisien, dan sesuai anggaran.

## 2. Ketentuan
- Pengadaan di atas Rp 5.000.000 wajib membandingkan minimal **3 penawaran**.
- Permintaan pengadaan harus disetujui kepala departemen.
- Vendor harus terdaftar dalam daftar vendor resmi perusahaan.

## 3. Prosedur
1. Departemen mengajukan permintaan pengadaan beserta spesifikasi.
2. Tim Keuangan memeriksa ketersediaan anggaran.
3. Tim Operasional mengumpulkan dan membandingkan penawaran vendor.
4. Manager memberi persetujuan akhir.
5. Barang atau jasa diterima, diperiksa, lalu dicatat.
MD,
                'timeline' => [
                    [SopAction::Created, 'pic.keuangan@ming.test', 'Draf awal SOP Pengadaan', 4],
                    [SopAction::SubmittedForReview, 'pic.keuangan@ming.test', 'Diajukan untuk ditinjau Manager', 2],
                ],
            ],

            // 5. Draft (ditolak Manager, ada catatan revisi)
            [
                'document_number' => 'SOP/HRD/002',
                'title' => 'Prosedur Onboarding Karyawan Baru',
                'category' => 'HRD',
                'version' => '1.0',
                'status' => SopStatus::Draft->value,
                'revision_note' => 'Mohon lengkapi alur hari pertama (orientasi, serah terima perangkat) dan tambahkan checklist peralatan kerja per departemen.',
                'created_by' => 'pic.hrd@ming.test',
                'approved_by' => null,
                'content' => <<<'MD'
## 1. Tujuan
Memastikan karyawan baru memahami budaya, aturan, dan alur kerja perusahaan sejak hari pertama.

## 2. Ketentuan
- Proses onboarding berlangsung selama 5 hari kerja pertama.
- HRD menyiapkan akun email dan akses sistem sebelum hari pertama.

## 3. Prosedur
1. HRD mengirim informasi kehadiran hari pertama kepada karyawan baru.
2. Karyawan mengikuti sesi orientasi perusahaan.
3. Atasan langsung memperkenalkan tim dan menjelaskan target awal.
MD,
                'timeline' => [
                    [SopAction::Created, 'pic.hrd@ming.test', 'Draf awal SOP Onboarding', 10],
                    [SopAction::SubmittedForReview, 'pic.hrd@ming.test', 'Diajukan untuk ditinjau Manager', 9],
                    [SopAction::Rejected, 'manager@ming.test', 'Mohon lengkapi alur hari pertama (orientasi, serah terima perangkat) dan tambahkan checklist peralatan kerja per departemen.', 8],
                ],
            ],

            // 6. Archived
            [
                'document_number' => 'SOP/HRD/003',
                'title' => 'Prosedur Absensi Manual',
                'category' => 'HRD',
                'version' => '1.0',
                'status' => SopStatus::Archived->value,
                'created_by' => 'pic.hrd@ming.test',
                'approved_by' => 'manager@ming.test',
                'content' => <<<'MD'
## 1. Tujuan
Mengatur pencatatan kehadiran karyawan secara manual menggunakan buku absensi.

## 2. Ketentuan
- Karyawan mengisi buku absensi saat tiba dan pulang.
- HRD merekap data kehadiran setiap akhir pekan.

> Dokumen ini telah diarsipkan karena digantikan sistem absensi fingerprint.
MD,
                'timeline' => [
                    [SopAction::Created, 'pic.hrd@ming.test', 'Draf awal SOP Absensi Manual', 200],
                    [SopAction::SubmittedForReview, 'pic.hrd@ming.test', 'Diajukan untuk ditinjau Manager', 199],
                    [SopAction::Approved, 'manager@ming.test', 'Disetujui dan diterbitkan (v1.0)', 198],
                    [SopAction::Archived, 'manager@ming.test', 'Diarsipkan: digantikan sistem absensi fingerprint', 60],
                ],
            ],
        ];
    }
}