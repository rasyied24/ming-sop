# MING SOP Center: Penjelasan Pengerjaan

Tes Teknis Full Stack Programmer, periode 19-25 November 2026.

## 1. Ringkasan

Aplikasi web internal untuk mengelola SOP perusahaan. Fokus pengerjaan: membuat **informasi SOP yang berlaku selalu benar, dapat ditelusuri, dan terbukti sampai ke karyawan**.

Yang berjalan:
- Siklus dokumen SOP (draft, review, published, archived) dengan persetujuan Manager dan audit trail.
- Pencarian SOP dan konfirmasi baca karyawan yang dicatat per versi.
- Pelaporan masalah dan dugaan pelanggaran, dengan tindak lanjut oleh Auditor/HRD.
- Dashboard Manager dan pemantauan kepatuhan baca untuk Auditor/HRD.

Yang belum: pengumuman/sosialisasi, materi pembelajaran dan evaluasi pemahaman, lampiran PDF, dan beberapa hal lain (bagian 8).

## 2. Cara menjalankan

Langkah lengkap ada di [README.md](../README.md), termasuk daftar akun demo (kata sandi semua akun: `password`). Ringkasnya:

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
# buat database PostgreSQL, samakan dengan .env
php artisan migrate:fresh --seed
npm run build
php artisan serve
```

Skenario demo ada di [docs/DEMO.md](DEMO.md).

## 3. Cara saya memahami kebutuhan dan memilih prioritas

Masalah inti di brief: dokumen, informasi perubahan, bukti sosialisasi, dan tindak lanjut tersebar, sehingga sulit memastikan informasi yang dipakai benar dan mudah ditelusuri. Saya menerjemahkannya menjadi empat alur yang saling menopang:

1. **Dokumen yang benar**: SOP dibuat, ditinjau, disetujui, diterbitkan, dan setiap langkahnya tercatat.
2. **Bukti sosialisasi**: karyawan mengonfirmasi sudah membaca **versi yang berlaku**, tercatat dengan waktu.
3. **Umpan balik dari lapangan**: laporan masalah atau dugaan pelanggaran, dengan status penanganan.
4. **Gambaran untuk manajemen**: pekerjaan tertunda, kepatuhan, dan laporan yang belum selesai.

Prioritas: saya mengerjakan fondasi yang dibutuhkan semua fitur lain lebih dulu (peran dan hak akses, siklus SOP, konfirmasi baca), lalu fitur yang bergantung padanya (laporan, dashboard, kepatuhan). Pengumuman dan evaluasi pemahaman saya tunda karena keduanya bergantung pada fondasi itu (siapa yang boleh membuat, siapa target pembaca, bagaimana bukti tercatat), dan bukti sosialisasi inti sudah terpenuhi lewat konfirmasi baca per versi. Waktu yang tersisa saya pakai untuk pengujian otomatis dan dokumentasi, karena kestabilan bagian yang sudah jadi lebih berharga daripada tambahan fitur yang belum teruji.

Urutan pengerjaan: (1) environment dan skema data, (2) login dan hak akses, (3) daftar SOP dan konfirmasi baca, (4) alur PIC SOP dan Manager, (5) laporan masalah, (6) dashboard dan kepatuhan, (7) pengujian otomatis dan dokumentasi.

## 4. Asumsi

1. **HRD dan Auditor digabung** menjadi satu peran ("Auditor / HRD"), dan **manajemen = Manager**. Satu pengguna memiliki satu peran.
2. **Kategori SOP = departemen**, sehingga ada satu filter "kategori".
3. **"Kepatuhan" berarti kepatuhan membaca**: konfirmasi karyawan atas versi yang berlaku, dibagi (jumlah karyawan x jumlah SOP terbit). Bukan bukti SOP dijalankan di lapangan.
4. **Hanya karyawan** yang diwajibkan mengonfirmasi baca dan yang boleh membuat laporan.
5. **Merevisi SOP terbit mengembalikannya ke draft** dan SOP itu tidak tampil bagi karyawan sampai Manager menyetujui versi baru. Isi versi lama tertimpa. Yang tersimpan: nomor versi di log dan konfirmasi per versi.
6. **Hanya Manager yang boleh mengarsipkan** SOP.
7. **Laporan tidak anonim.** Auditor/HRD melihat nama pelapor. Manager hanya melihat ringkasan tanpa nama.
8. **Isi SOP berformat Markdown**, dirender dengan HTML mentah dibuang.
9. Semua data adalah **data contoh fiktif** (domain `@ming.test`). Tidak ada data pribadi atau data perusahaan sebenarnya.

## 5. Pilihan teknis dan alasannya

| Pilihan | Alasan |
|---|---|
| Laravel 13 + PostgreSQL | Stack yang saya kuasai, dan PostgreSQL mendukung kebutuhan seperti `ILIKE` dan penguncian baris |
| Livewire 4 + Blade + Tailwind | Fitur berupa form, tabel, filter, dan dashboard. Semua logika tetap di PHP, tanpa lapisan API dan state frontend terpisah, sehingga lebih cepat dibangun dan lebih mudah dijelaskan |
| Service (`SopWorkflow`, `ReportWorkflow`, `ComplianceReport`) | Semua perubahan status dan perhitungan kepatuhan berada di satu tempat, dalam satu transaksi dengan penguncian baris. Hak akses dicek di dalam service, jadi tidak ada komponen yang bisa melewatinya |
| Policy + middleware peran | Keamanan berlapis: middleware membatasi halaman, Policy membatasi aksi, tombol hanya disembunyikan untuk kenyamanan |
| Enum PHP untuk peran, status, jenis | Salah ketik menjadi error, bukan bug diam-diam. Label dan alur status ikut tersimpan di enum |
| Konfirmasi baca per versi + constraint unik | Bukti sosialisasi yang akurat saat SOP direvisi, dan database menolak konfirmasi ganda |
| Satu kolom tambahan `sop_reports.type` | Membedakan kendala SOP, ketidaksesuaian di lapangan, dan dugaan pelanggaran, supaya dapat difilter. Ditambahkan lewat migration terpisah |

## 6. Keamanan dasar

- Kata sandi di-hash, token CSRF di semua form, sesi diregenerasi saat login, dan login dibatasi lima percobaan gagal.
- Hak akses berlapis: middleware peran, Policy per aksi, dan pengecekan ulang di dalam service dan `mount()` komponen.
- Karyawan hanya bisa membuka laporannya sendiri (pencegahan akses data milik orang lain lewat ID di alamat).
- Input pengguna divalidasi di server. Nilai filter dari alamat dipetakan lewat daftar yang diizinkan, bukan dipakai apa adanya.
- Pencarian memakai parameter terikat dan karakter wildcard di-escape. Isi SOP dirender dengan HTML mentah dibuang, dan isi laporan di-escape.
- Perubahan status bersamaan diatasi dengan transaksi dan penguncian baris.

## 7. Bagian yang sudah berjalan

| Area | Yang bisa dilakukan |
|---|---|
| Login dan peran | Masuk, keluar, halaman awal sesuai peran, halaman akses ditolak |
| Karyawan | Cari dan filter SOP terbit, baca, konfirmasi baca per versi, buat dan pantau laporan |
| PIC SOP | Direktori SOP dengan tab status, buat dan ubah draf, ajukan review, revisi minor/mayor |
| Manager | Antrean persetujuan, setujui atau tolak dengan catatan, arsipkan, dashboard ringkasan |
| Auditor / HRD | Kepatuhan per SOP dan per karyawan, laporan masuk dengan filter, ubah status penanganan |
| Lintas peran | Audit trail per SOP, pengujian otomatis `[isi: hasil php artisan test, mis. "N tes, semuanya lulus"]` |

Bukti tampilan: lihat tangkapan layar di [docs/screenshots](screenshots). Video demo tidak disertakan karena keterbatasan waktu.

## 8. Bagian yang belum selesai

| Bagian | Alasan belum | Rencana |
|---|---|---|
| Pengumuman/sosialisasi | Diprioritaskan di bawah fondasi dan kepatuhan | Tabel pengumuman dengan target peran, dikaitkan ke SOP, tampil di halaman awal karyawan |
| Materi pembelajaran dan evaluasi pemahaman | Opsional menurut brief | Kuis singkat per SOP; konfirmasi baca bisa mensyaratkan lulus kuis |
| Lampiran PDF pada SOP | Kolom `file_path` sudah ada, belum dipakai | Unggah di form, unduh dengan otorisasi Policy |
| Salinan isi tiap versi | Skema memberi satu baris isi per SOP | Tabel `sop_versions` agar versi lama tetap bisa dibaca dan SOP lama tetap tayang selama revisi |
| Hapus dan pulihkan SOP | Aturan bisnisnya perlu diputuskan | Hapus hanya draf v1.0 yang belum pernah diajukan; pulihkan oleh Manager |
| Laporan: penanggung jawab dan komentar | Skema belum memuat | Kolom `handled_by` dan catatan tindak lanjut; opsi laporan anonim sesuai kebijakan |
| Notifikasi, pengingat, tenggat konfirmasi | Di luar lingkup waktu | Email atau notifikasi dalam aplikasi untuk SOP baru dan karyawan yang belum membaca |
| Manajemen pengguna | Pengguna saat ini dari seeder | Halaman kelola pengguna dan peran, impor |
| Ekspor laporan, deployment | Di luar lingkup waktu | Ekspor CSV/PDF, pipeline CI dan lingkungan demo daring |

## 9. Masalah yang ditemui dan penyelesaiannya

| Masalah | Penyebab | Penyelesaian |
|---|---|---|
| Error `tempnam()` saat membuka halaman pertama | PHP-FPM berjalan sebagai `www-data` dan tidak bisa menulis ke `storage` | Memberi hak tulis lewat ACL pada `storage` dan `bootstrap/cache`, bukan `chmod 777` |
| Tabel `sessions` tidak ada | Sesi memakai database dan migration belum dijalankan | Menjalankan migration; memastikan blok tabel sesi tidak ikut terhapus saat mengubah migration `users` |
| Karyawan mendapat 403 setelah login | Laravel menyimpan tujuan lama dan mengarahkan ke sana setelah login | Selalu mengarahkan ke halaman awal sesuai peran dan menghapus tujuan tersimpan |
| Error `Undefined constant Sop::CATEGORIES` | Berkas komponen terpasang dengan versi lama | Menemukannya dengan `grep` dan cap waktu berkas, lalu mengganti berkas secara utuh, bukan menambal |
| Menu sidebar tidak menyala di halaman detail SOP | Satu alamat detail dibuka dari beberapa halaman asal | Parameter `?from=` yang dipetakan lewat daftar yang diizinkan di layout |
| Kartu "Laporan Belum Selesai" (6) tidak cocok dengan tabel (5) | Kartu dan tabel dihitung dari dua query berbeda | Satu sumber query untuk total dan rincian, serta catatan jumlah yang tampil |
| Risiko urutan migration pada detik yang sama | Nama berkas memakai timestamp per detik | Memberi jeda satu detik saat membuat migration beruntun |

## 10. Penggunaan AI

Saya memakai Claude sebagai mitra pemrograman, dengan cara kerja bertahap: satu fase per giliran, meminta kode lengkap **beserta alasan tiap keputusan**, lalu melaporkan hasil uji sebelum lanjut. Peran saya:

- **Menentukan arah**: lingkup MVP, pilihan Livewire, redesain UI mengikuti mockup yang saya buat, penambahan jenis laporan, dan pembekuan fitur menjelang tenggat.
- **Memverifikasi, bukan menerima begitu saja**: setiap fase saya uji lewat browser per peran, mencocokkan angka aplikasi dengan SQL mandiri, memeriksa lewat `tinker`, dan menjalankan pengujian otomatis. Beberapa masalah di bagian 9 berasal dari ketidaksinkronan antara kode yang disarankan dan kondisi berkas saya. Saya menemukan penyebabnya dengan `grep`, `ls`, dan membaca pesan error, bukan menebak.
- **Mempertanyakan hasil**: contohnya angka kartu laporan yang tidak cocok dengan tabel, dan sorotan menu yang salah.

Saya dapat menjelaskan alur tiap berkas penting (`SopWorkflow`, `SopPolicy`, `ComplianceReport`, komponen halaman) dan alasan pilihannya.

## 11. Pengujian

```bash
php artisan test
```

Tes memakai database PostgreSQL terpisah (`ming_sop_testing`) dan menolak berjalan di database lain. Cakupan: akses halaman per peran (termasuk kasus yang harus ditolak), siklus SOP dan audit trail, perhitungan kepatuhan (versi lama, non-karyawan, pembagi nol), alur laporan, login dan pembatasan percobaan, serta konfirmasi baca.

## 12. Rencana berikutnya

1. Pengumuman/sosialisasi dengan target peran dan kaitan ke SOP.
2. Tabel `sop_versions` agar riwayat isi tersimpan dan SOP lama tetap tayang selama revisi.
3. Kuis pemahaman per SOP.
4. Lampiran PDF, hapus/pulihkan, dan penanggung jawab laporan.
5. Notifikasi dan pengingat konfirmasi baca, manajemen pengguna.
6. Ekspor laporan, integrasi berkelanjutan, dan lingkungan demo daring.