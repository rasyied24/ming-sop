# MING SOP

Aplikasi web internal untuk mengelola dokumen SOP (Standard Operating Procedure) perusahaan: dari penyusunan draf, peninjauan, penerbitan, sampai memastikan karyawan benar-benar sudah membacanya.

Dibuat sebagai tes teknis Full Stack Programmer, dalam bentuk Expanded MVP.

## Fitur utama

| Peran | Yang bisa dilakukan |
|---|---|
| **Karyawan** | Mencari dan membaca SOP yang berlaku, mengonfirmasi sudah membaca (per versi), melaporkan kendala atau dugaan pelanggaran SOP |
| **PIC SOP** | Membuat dan mengubah draf, mengajukan ke Manager, merevisi SOP terbit (versi naik minor atau mayor) |
| **Manager** | Menyetujui atau menolak (dengan catatan revisi) SOP yang diajukan, mengarsipkan SOP, melihat dashboard ringkasan |
| **Auditor / HRD** | Memantau kepatuhan baca (siapa yang sudah dan belum), menangani laporan masuk |

Siklus dokumen: `draft` → `review` → `published` → `archived`. Setiap perubahan status tercatat di audit trail (`sop_logs`).

## Teknologi

Laravel 13, Livewire 4, Tailwind CSS 4 (Vite), PostgreSQL. Sisi klien memakai Alpine.js yang sudah terbawa Livewire.

## Menjalankan di komputer lokal

Prasyarat: PHP sesuai `composer.json` (dengan ekstensi `pdo_pgsql`, `mbstring`, `xml`, `curl`, `zip`, `intl`, `bcmath`), Composer, Node.js, dan PostgreSQL.

```bash
# 1. Dependensi
composer install
npm install

# 2. Konfigurasi
cp .env.example .env
php artisan key:generate

# 3. Database (ganti kata sandi sesuai selera, lalu samakan dengan DB_PASSWORD di .env)
sudo -u postgres psql -c "CREATE USER ming WITH PASSWORD 'ming_secret';"
sudo -u postgres psql -c "CREATE DATABASE ming_sop OWNER ming;"

# 4. Tabel dan data demo
php artisan migrate:fresh --seed

# 5. Aset
npm run build

# 6. Jalankan
php artisan serve
```

Buka `http://127.0.0.1:8000`.

Pengembangan memakai Nginx dan PHP-FPM, tetapi `php artisan serve` cukup untuk mencoba aplikasi.

## Akun demo

Kata sandi semua akun: `password`.

| Peran | Email |
|---|---|
| Manager | manager@ming.test |
| PIC SOP | pic.hrd@ming.test, pic.it@ming.test, pic.keuangan@ming.test |
| Auditor / HRD | auditor@ming.test |
| Karyawan | ahmad.fauzi@ming.test, lukman.hakim@ming.test, joko.susilo@ming.test, dan 7 akun lain berformat `nama.belakang@ming.test` |

Data demo berisi SOP di semua status, riwayat perubahan, konfirmasi baca dengan tingkat kepatuhan yang bervariasi, dan beberapa laporan. Ulangi `php artisan migrate:fresh --seed` untuk mengembalikan data awal.

Skenario presentasi ada di [docs/DEMO.md](docs/DEMO.md).

## Struktur dan keputusan desain

```
app/Enums/          Nilai tetap: peran, status SOP, aksi log, status dan jenis laporan
app/Services/       Aturan bisnis: SopWorkflow, ReportWorkflow, ComplianceReport
app/Policies/       Hak akses per aksi: SopPolicy, SopReportPolicy
app/Http/Middleware EnsureUserHasRole (alias "role")
resources/views/pages/    Halaman (komponen Livewire satu berkas)
resources/views/layouts/  Layout dengan sidebar
tests/              Pengujian otomatis
```

- **Aturan bisnis ada di satu tempat.** Semua perubahan status SOP dan laporan lewat service, dalam satu transaksi database dengan penguncian baris. Dua orang yang bertindak bersamaan pada SOP yang sama tidak bisa saling menimpa, dan log audit tidak mungkin tertinggal.
- **Keamanan berlapis.** Middleware membatasi halaman per peran, Policy membatasi tiap aksi (juga di dalam service), dan tombol hanya disembunyikan untuk kenyamanan, bukan untuk keamanan.
- **Konfirmasi baca dicatat per versi.** Saat SOP naik versi, konfirmasi lama tidak berlaku dan karyawan diminta membaca ulang. Persentase kepatuhan dihitung dari konfirmasi atas versi yang berlaku.
- **Konten SOP berupa Markdown** yang dirender dengan HTML mentah dibuang, untuk mencegah XSS.
- **Enum PHP** untuk nilai tetap (peran, status), sehingga salah ketik menjadi error, bukan bug diam-diam.
- **Kolom `type` pada `sop_reports`** ditambahkan lewat migration terpisah di luar skema awal, untuk membedakan kendala SOP, ketidaksesuaian di lapangan, dan dugaan pelanggaran.

## Keterbatasan yang disadari

- Merevisi SOP terbit mengembalikannya ke `draft`, sehingga SOP itu tidak tampil bagi karyawan sampai Manager menyetujui versi baru. Isi versi lama tertimpa. Yang tersimpan adalah nomor versi di log dan siapa yang mengonfirmasi versi mana. Menyimpan salinan isi tiap versi membutuhkan tabel tambahan.
- Laporan tidak anonim: Auditor melihat nama pelapor, Manager hanya melihat ringkasan.
- Tidak ada catatan siapa yang menangani laporan, hanya status terkini.
- Belum ada lampiran PDF, tombol Hapus atau Pulihkan, notifikasi email, tenggat konfirmasi baca, dan ekspor laporan.
- "Kepatuhan" berarti kepatuhan **membaca** (konfirmasi), bukan bukti bahwa SOP dijalankan.

## Pengujian

Tes memakai database PostgreSQL terpisah (fitur yang dipakai, seperti `ILIKE`, tidak tersedia di SQLite):

```bash
sudo -u postgres psql -c "CREATE DATABASE ming_sop_testing OWNER ming;"
php artisan test
```

Tes menolak berjalan di database selain `ming_sop_testing` agar data kerja tidak terhapus.