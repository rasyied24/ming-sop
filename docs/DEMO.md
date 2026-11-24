# Skenario Demo MING SOP (sekitar 12 menit)

Persiapan: `php artisan migrate:fresh --seed`, lalu `npm run build`. Siapkan dua jendela peramban (satu biasa, satu privat) supaya pindah akun tidak perlu logout berulang. Kata sandi semua akun: `password`.

## Alur cerita

Satu SOP baru dari penyusunan sampai terbaca karyawan, lalu masalah dari lapangan sampai ditangani.

| # | Akun | Langkah | Yang ditekankan |
|---|---|---|---|
| 1 | `pic.hrd@ming.test` | Direktori SOP, **Buat SOP Baru** (`SOP/HRD/004`, isi Markdown), **Simpan & Ajukan untuk Review** | Validasi form, tombol aksi menyesuaikan hak akses, draf terkunci setelah diajukan |
| 2 | `manager@ming.test` | Dashboard, lalu **Pengajuan & Persetujuan**, **Tinjau**, **Tolak** dengan catatan | Dashboard ringkas, penolakan wajib alasan |
| 3 | `pic.hrd@ming.test` | Buka SOP tadi, lihat catatan revisi (banner kuning), perbaiki, ajukan ulang | Alur penolakan kembali ke PIC |
| 4 | `manager@ming.test` | **Setujui & Terbitkan**; buka **Riwayat Perubahan** | Audit trail: siapa melakukan apa, kapan |
| 5 | `lukman.hakim@ming.test` | Daftar SOP, cari dan filter kategori, buka SOP baru, **Saya Telah Membaca & Memahami SOP Ini** | Pencarian, konfirmasi tercatat dengan waktu |
| 6 | `auditor@ming.test` | **Kepatuhan**, **Rincian** SOP tadi, filter "Belum membaca" | Kepatuhan dihitung per versi, siapa yang belum |
| 7 | `pic.hrd@ming.test` | SOP Cuti, **Revisi Minor**, ajukan; Manager menyetujui | Versi naik, SOP kembali ke draf selama direvisi |
| 8 | `auditor@ming.test` | **Kepatuhan** lagi | Kepatuhan SOP Cuti turun ke 0%, karyawan berstatus "Versi lama" |
| 9 | `ahmad.fauzi@ming.test` | Buka SOP Cuti, **Laporkan Masalah**, pilih jenis **Dugaan Pelanggaran**, kirim | Jenis laporan, identitas pelapor terlihat Auditor |
| 10 | `auditor@ming.test` | **Laporan Masuk**, filter jenis, **Tinjau**, **Mulai Tangani**, **Tandai Selesai** | Alur status laporan |
| 11 | `manager@ming.test` | Dashboard | Angka berubah, laporan tanpa nama pelapor |

## Pertanyaan yang mungkin muncul

**Kenapa isi SOP versi lama tidak bisa dilihat lagi?**
Skema memberi satu baris isi per SOP. Nomor versi, log perubahan, dan konfirmasi per versi tersimpan. Menyimpan salinan isi tiap versi butuh tabel `sop_versions`, itu pengembangan lanjutan yang jelas.

**Apa yang mencegah karyawan membuka draf atau halaman Manager?**
Tiga lapis: middleware peran di route, Policy di setiap aksi (termasuk di dalam service), dan tampilan yang menyembunyikan tombol. Yang pertama dan kedua ada tes otomatisnya.

**Bagaimana kalau dua Manager menyetujui dan menolak bersamaan?**
Setiap perubahan status memakai transaksi dan penguncian baris. Yang kedua mendapat penolakan karena statusnya sudah bukan `review`.

**Apakah laporan bisa anonim?**
Belum. Kolom pelapor wajib, dan form menyatakan identitas terlihat oleh Auditor/HRD. Opsi anonim perlu perubahan skema dan kebijakan perusahaan.

**Apa arti "kepatuhan"?**
Persentase karyawan yang sudah mengonfirmasi versi SOP yang berlaku. Ini bukti SOP sampai ke karyawan, bukan bukti SOP dijalankan. Untuk itu ada fitur laporan.

**Bagaimana kalau jumlah data membesar?**
Perhitungan kepatuhan dan dashboard memakai agregat di database (jumlah query tetap berapa pun jumlah SOP), tabel dipaginasi, dan filter dilakukan di sisi database.