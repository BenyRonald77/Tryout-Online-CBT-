# Tryout Online CBT

Aplikasi tryout online berbasis komputer (Computer Based Test) untuk simulasi ujian: admin membuat bank soal dan paket tryout terjadwal, peserta mengerjakan tryout dengan soal dan pilihan jawaban yang diacak per peserta, waktu pengerjaan ditegakkan oleh server (bukan jam browser), jawaban tersimpan otomatis, dan peringkat nasional tampil begitu tryout ditutup.

Lihat `PRD.md` untuk latar belakang, lingkup fitur, dan kriteria penerimaan lengkap. Lihat `DESIGN.md` untuk arah desain (palet warna, dial energi/rhythm/motion) yang dipakai di seluruh UI.

## Tech stack

- Laravel 12 (PHP 8.4)
- Laravel Breeze (stack Livewire) untuk autentikasi
- Livewire 3 untuk halaman ujian, ranking, dan CRUD admin
- SQLite untuk database (default di environment ini)
- Tailwind CSS untuk styling

## Instalasi & Setup

```bash
composer install
cp .env.example .env
php artisan key:generate

# Buat file database SQLite (kosongkan dulu jika sudah ada isinya)
touch database/database.sqlite

php artisan migrate --seed

npm install
npm run build

php artisan serve
```

Aplikasi bisa diakses di `http://127.0.0.1:8000`.

### Menjalankan penegakan waktu otomatis (wajib untuk fitur timer & auto-close)

Ada satu command terjadwal, `attempts:auto-submit-expired`, yang:

1. Men-submit otomatis (dan menilai) setiap attempt yang statusnya masih `ongoing` tapi waktunya sudah habis, termasuk kalau peserta menutup tab tanpa menekan submit.
2. Menutup (`status = closed`) setiap tryout yang jadwal `ends_at`-nya sudah lewat tapi belum ditutup manual oleh admin.

Command ini dijadwalkan berjalan setiap menit lewat `routes/console.php`. Untuk lingkungan development, jalankan di terminal terpisah:

```bash
php artisan schedule:work
```

Untuk production, pasang satu entri cron yang menjalankan `php artisan schedule:run` setiap menit, sesuai [dokumentasi scheduler Laravel](https://laravel.com/docs/scheduling).

Tanpa `schedule:work`/cron ini, timer tetap ditegakkan secara defensif setiap kali peserta membuka halaman ujian atau melakukan autosave (attempt yang waktunya habis akan langsung dipaksa submit saat itu juga), jadi tidak ada kecurangan yang mungkin lewat refresh browser. Yang butuh scheduler adalah kasus peserta menutup tab dan tidak pernah membuka halaman itu lagi, serta penutupan otomatis tryout supaya peringkat tampil tanpa perlu ada yang mengakses halaman itu dulu.

### Queue

Environment ini memakai `QUEUE_CONNECTION=database` (tidak butuh Redis). Tabel queue sudah termasuk dalam migration bawaan Laravel (`jobs`), tidak ada job berat yang dipakai aplikasi ini saat ini, tapi worker bisa dijalankan dengan `php artisan queue:work` bila diperlukan di kemudian hari.

### Database untuk deployment (MySQL)

Environment sandbox ini memakai SQLite agar mudah dijalankan tanpa server database terpisah. Untuk deployment ke hosting yang umum dipakai (misalnya shared hosting dengan MySQL), buka `.env.example`: ada blok konfigurasi MySQL yang tinggal dibuka komentarnya (dan blok SQLite dikomentari), lalu isi kredensialnya di `.env`.

## Akun Demo

Akun-akun berikut dibuat oleh seeder (`database/seeders/DatabaseSeeder.php`). Ini adalah akun demo untuk keperluan pengujian aplikasi, bukan data orang sungguhan.

| Peran | Email | Password |
|---|---|---|
| Admin | `admin@example.com` | `password` |
| Peserta | `peserta@example.com` | `password` |
| Peserta | `peserta2@example.com` | `password` |

Data demo yang ikut dibuat oleh seeder:
- 3 mata pelajaran (Matematika Dasar, Bahasa Indonesia, Wawasan Umum) dengan 10 soal pilihan ganda per mata pelajaran (total 30 soal, masing-masing 4 opsi jawaban).
- **Tryout Simulasi Dasar (Campuran)**: berstatus `published`, jendela waktu sedang terbuka, berisi campuran ketiga mapel, siap dicoba langsung oleh akun peserta demo.
- **Tryout Matematika Dasar (Contoh, Sudah Ditutup)**: berstatus `closed`, sudah punya 2 attempt selesai (satu per akun peserta demo) sehingga halaman peringkatnya langsung terisi tanpa perlu mengerjakan apa pun.

## Menjalankan Tes

```bash
php artisan test
```

Ada 39 test (semuanya lulus saat terakhir dijalankan), mencakup: autentikasi bawaan Breeze, dan test khusus aplikasi ini di `tests/Feature/Tryout` dan `tests/Feature/Admin` yang mencakup:
- Memulai attempt menghasilkan set soal & urutan opsi yang diacak dan tersimpan permanen untuk peserta tersebut.
- Memulai attempt dua kali untuk tryout yang sama ditolak.
- Autosave benar-benar menyimpan jawaban ke database, dan jawaban tidak hilang saat pindah soal.
- Submit menghitung skor dengan benar berdasarkan jawaban yang tersimpan.
- Attempt yang waktunya sudah habis tidak bisa lagi dijawab (baik lewat pemanggilan langsung maupun lewat halaman ujian, yang otomatis redirect ke halaman hasil).
- Halaman peringkat tersembunyi (menampilkan pesan jujur) sampai tryout berstatus closed, lalu menampilkan data yang benar setelah itu.
- Command terjadwal `attempts:auto-submit-expired` menutup attempt yang ditinggal dan tryout yang lewat jadwal.
- CRUD admin: mata pelajaran, soal (dengan validasi tepat satu opsi benar), dan tryout (termasuk pengelolaan pool soal, publish, dan close), beserta pembatasan bahwa halaman admin tidak bisa diakses peserta atau tamu.

## Fitur Utama

**Untuk peserta:**
- Registrasi & login.
- Daftar tryout yang sedang dibuka, dengan status jelas (belum dibuka / bisa dimulai / sudah ditutup / sudah pernah diikuti).
- Mengerjakan tryout: satu soal per layar, navigasi bebas antar soal, indikator "Menyimpan.../Tersimpan/Gagal menyimpan" yang jujur, timer mundur yang disinkronkan dari server.
- Waktu pengerjaan ditegakkan oleh server: refresh halaman tidak menambah waktu, dan begitu waktu habis, jawaban terkunci otomatis.
- Melihat hasil (skor dan rincian jawaban) milik sendiri.
- Melihat peringkat nasional setelah tryout ditutup.

**Untuk admin:**
- CRUD mata pelajaran.
- CRUD bank soal (pilihan ganda, 2-6 opsi, tepat satu jawaban benar).
- Membuat tryout, mengatur pool soal sumber pengacakan, mempublikasikan, dan menutup tryout secara manual.
- Dashboard hasil: daftar attempt per tryout beserta skor dan statusnya.

## Struktur Proyek yang Relevan

- `app/Services/AttemptService.php`: logika inti memulai, menjawab (autosave), dan menyelesaikan (submit/auto-expire) attempt. Semua perhitungan waktu berdasarkan `started_at` di database, tidak pernah mempercayai input klien.
- `app/Services/TryoutService.php`: logika menutup tryout begitu jadwalnya lewat.
- `app/Console/Commands/AutoSubmitExpiredAttempts.php`: command terjadwal yang dijelaskan di atas.
- `app/Livewire/Peserta/ExamPage.php`: halaman ujian (timer, navigasi soal, autosave).
- `app/Livewire/Peserta/RankingPage.php`: halaman peringkat nasional.
- `app/Livewire/Admin/*`: CRUD admin (mata pelajaran, soal, tryout, dashboard hasil).
