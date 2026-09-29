# PRD - Tryout Online (CBT)

## 1. Latar Belakang

Bimbingan belajar dan sekolah membutuhkan cara untuk mengadakan simulasi ujian (tryout) berbasis komputer secara terjadwal, dengan soal yang diacak per peserta agar mengurangi kecurangan, waktu ujian yang tidak bisa dimanipulasi dari sisi klien, dan hasil peringkat yang bisa dilihat semua peserta begitu tryout ditutup. Aplikasi ini, Tryout Online CBT, dibangun untuk kebutuhan tersebut: sebuah platform ujian berbasis komputer (Computer Based Test) yang sederhana, aman dari manipulasi waktu di sisi klien, dan bisa dikelola sendiri oleh admin tanpa bantuan developer.

## 2. Tujuan

- Memungkinkan admin membuat bank soal per mata pelajaran dan merangkainya menjadi paket tryout terjadwal.
- Memungkinkan peserta mengikuti tryout pada jendela waktu yang ditentukan, dengan soal dan urutan pilihan jawaban yang diacak khusus untuk peserta tersebut.
- Menjamin waktu pengerjaan dihitung dan ditegakkan oleh server, bukan oleh jam di browser peserta, sehingga me-refresh halaman atau mengubah jam di perangkat peserta tidak memberi keuntungan apa pun.
- Menyimpan jawaban peserta secara otomatis setiap kali peserta memilih opsi, tanpa peserta perlu menekan tombol "simpan".
- Menampilkan peringkat nasional (lintas seluruh peserta yang mengikuti tryout tersebut) begitu tryout ditutup, diurutkan berdasarkan skor lalu waktu selesai.

## 3. Aktor

### 3.1 Admin
- Mengelola mata pelajaran (subjects).
- Mengelola bank soal (questions) beserta pilihan jawaban (question options).
- Membuat paket tryout (judul, mata pelajaran, durasi, jadwal buka-tutup, jumlah soal yang diambil per peserta) dan menentukan pool soal yang menjadi sumber pengacakan.
- Mempublikasikan (publish) tryout agar bisa diikuti peserta, atau menutupnya (close) secara manual sebelum jadwal berakhir.
- Melihat dashboard hasil (daftar attempt, skor, status) untuk setiap tryout kapan saja, termasuk sebelum tryout ditutup untuk peserta lain.

### 3.2 Peserta
- Mendaftar (register) dan login.
- Melihat daftar tryout yang sedang publish dan dalam jendela waktu pengerjaan.
- Memulai tryout (paling banyak satu kali per tryout).
- Menjawab soal, berpindah antar soal tanpa kehilangan jawaban yang sudah tersimpan.
- Melihat hasil (skor) miliknya sendiri setelah submit atau setelah waktu habis.
- Melihat peringkat nasional untuk tryout yang sudah ditutup.

## 4. Lingkup Fitur

### Termasuk dalam lingkup
1. Autentikasi (register/login/logout) dengan peran admin dan peserta.
2. CRUD mata pelajaran, soal (pilihan jamak, satu jawaban benar), dan paket tryout oleh admin.
3. Pengaturan pool soal per tryout (admin memilih soal mana yang boleh diambil sebagai sumber pengacakan; jumlah pool boleh lebih besar dari jumlah soal yang benar-benar diujikan).
4. Mulai tryout oleh peserta: validasi jendela waktu, validasi belum pernah mengikuti, pengambilan sampel acak soal dari pool, dan pengacakan urutan pilihan jawaban, semuanya dibuat sekali saat mulai dan disimpan permanen untuk peserta tersebut.
5. Halaman pengerjaan soal dengan timer mundur yang disinkronkan ke server, navigasi antar soal, dan autosave jawaban.
6. Penegakan waktu di server: setiap permintaan (buka halaman, autosave) memeriksa ulang apakah waktu sudah habis; jika habis, jawaban langsung dikunci dan attempt di-submit otomatis.
7. Auto-submit terjadwal (`schedule:work`) untuk attempt yang ditinggal (tab ditutup) begitu waktunya habis.
8. Perhitungan skor otomatis saat submit (manual atau otomatis).
9. Auto-close tryout begitu `ends_at` terlewati, dan penutupan manual oleh admin.
10. Halaman peringkat nasional per tryout, hanya tampil setelah tryout berstatus closed.
11. Dashboard hasil admin per tryout.
12. Seeder data demo (mata pelajaran, soal, tryout, akun demo admin dan peserta).

### Tidak termasuk dalam lingkup (v1)
- Soal dengan lebih dari satu jawaban benar (multi-select) atau esai/isian bebas.
- Proctoring (webcam, deteksi wajah, dsb).
- Pembagian peringkat per sekolah/wilayah (yang ada hanya peringkat nasional/global per tryout).
- Notifikasi email/SMS.

## 5. Entitas Data Utama

| Entitas | Keterangan |
|---|---|
| `users` | Akun dengan kolom `role` (`admin`/`peserta`). |
| `subjects` | Mata pelajaran (contoh: Matematika Dasar, Bahasa Indonesia, Wawasan Umum). |
| `questions` | Soal: `subject_id`, `body`, `difficulty` (easy/medium/hard), `points`. |
| `question_options` | Pilihan jawaban per soal, tepat satu `is_correct = true`. |
| `tryouts` | Paket ujian: `title`, `subject_id` (nullable, tryout boleh campuran lintas mapel), `duration_minutes`, `starts_at`, `ends_at`, `question_count`, `status` (draft/published/closed). |
| `tryout_questions` | Pool soal yang boleh diambil untuk suatu tryout (bisa lebih banyak dari `question_count`). |
| `attempts` | Satu percobaan peserta pada satu tryout: `started_at`, `submitted_at`, `status` (ongoing/submitted/expired), `score`. Unik per (`tryout_id`, `user_id`). |
| `attempt_questions` | Soal-soal hasil pengacakan untuk satu attempt: `display_order`, `shuffled_option_order` (json), `selected_option_id`, `is_correct`, `answered_at`. |

Catatan desain: `tryouts.subject_id` bersifat nullable karena satu tryout bisa berisi campuran beberapa mata pelajaran (misal tryout simulasi UTBK yang menggabungkan beberapa subtes). Jika `subject_id` diisi, tryout tersebut dianggap tryout per-mapel tunggal untuk kebutuhan tampilan/filter, namun pool soalnya tetap ditentukan lewat `tryout_questions`, tidak otomatis dari `subject_id`.

## 6. Alur Pengguna

### 6.1 Peserta mengikuti tryout (end-to-end)
1. Peserta login, membuka daftar tryout yang berstatus `published` dan berada dalam jendela `starts_at..ends_at`.
2. Peserta menekan "Mulai Tryout". Server memvalidasi jendela waktu dan memastikan peserta belum punya attempt untuk tryout ini. Jika valid, server membuat `Attempt` (`started_at = now()`), mengambil sampel acak `question_count` soal dari pool `tryout_questions`, mengacak urutan soal (`display_order`) dan urutan opsi jawaban tiap soal (`shuffled_option_order`), menyimpan semuanya ke `attempt_questions`, lalu mengarahkan peserta ke halaman pengerjaan.
3. Di halaman pengerjaan, peserta melihat soal sesuai `display_order`, memilih jawaban; setiap pemilihan langsung tersimpan (autosave) dengan indikator status simpan yang jujur (menyimpan/tersimpan/gagal).
4. Timer di halaman dihitung ulang dari server setiap kali halaman dimuat atau di-poll; jika sisa waktu sudah 0, server memaksa submit dan mengarahkan ke halaman hasil.
5. Peserta bisa submit manual sebelum waktu habis, atau membiarkan sistem auto-submit saat waktu habis (baik lewat pemeriksaan on-access maupun command terjadwal untuk attempt yang ditinggal).
6. Setelah submit, peserta melihat skor miliknya. Peringkat nasional untuk tryout tersebut bisa dilihat begitu tryout berstatus `closed`.

### 6.2 Admin mengelola soal & tryout
1. Admin login, membuat mata pelajaran.
2. Admin membuat soal per mata pelajaran beserta 4-5 opsi jawaban, menandai satu opsi sebagai benar.
3. Admin membuat paket tryout baru (judul, mapel, durasi, jadwal, jumlah soal per peserta), lalu menambahkan soal-soal ke pool tryout tersebut.
4. Admin mempublikasikan tryout agar tampil ke peserta.
5. Admin bisa menutup tryout secara manual kapan saja, atau membiarkannya tertutup otomatis saat `ends_at` terlewati.
6. Admin membuka dashboard hasil untuk melihat semua attempt (skor, status) pada tryout tersebut, kapan saja, tanpa menunggu tryout ditutup.

## 7. Kriteria Penerimaan per Fitur Inti

### Timer server-side
- Sisa waktu yang ditampilkan selalu dihitung dari `started_at` + `duration_minutes` yang tersimpan di database, tidak pernah dari input klien.
- Me-refresh halaman pengerjaan tidak menambah atau mengurangi sisa waktu.
- Ketika sisa waktu mencapai 0: permintaan berikutnya (muat halaman atau autosave) langsung memicu submit otomatis dan mengarahkan ke halaman hasil; peserta tidak bisa lagi mengubah jawaban.
- Command terjadwal (berjalan tiap menit) menemukan dan men-submit otomatis semua attempt `ongoing` yang sudah lewat waktu, termasuk yang tab-nya sudah ditutup peserta.

### Pengacakan soal & opsi per peserta
- Saat attempt dibuat, sistem mengambil sejumlah `question_count` soal secara acak dari pool tryout dan mengacak urutan opsi jawaban tiap soal, lalu menyimpan hasilnya ke `attempt_questions`.
- Susunan soal dan opsi tersebut tetap sama untuk peserta itu di sepanjang sisa attempt (tidak diacak ulang setiap kali halaman dimuat).
- Dua peserta yang mengikuti tryout yang sama kemungkinan besar mendapat set soal dan/atau urutan berbeda (dapat diverifikasi lewat data `attempt_questions`).

### Autosave
- Memilih opsi jawaban langsung mengirim permintaan simpan ke server tanpa perlu tombol submit per soal.
- Berpindah ke soal lain dan kembali menampilkan kembali jawaban yang sudah dipilih sebelumnya.
- Indikator status simpan menunjukkan keadaan nyata: sedang menyimpan, tersimpan, atau gagal menyimpan (bukan selalu mengklaim berhasil).

### Ranking nasional
- Halaman ranking untuk tryout yang belum `closed` menampilkan pesan jujur bahwa peringkat akan tampil setelah tryout ditutup, bukan tabel kosong atau data palsu.
- Setelah tryout `closed`, halaman menampilkan seluruh attempt berstatus `submitted`, diurutkan skor tertinggi lebih dulu, dan jika skor sama, peserta yang submit lebih cepat berada di posisi lebih atas.
- Status `closed` tercapai otomatis begitu `ends_at` terlewati (lewat command terjadwal) atau ditutup manual oleh admin sebelum itu.

### Admin CRUD
- Admin dapat membuat, mengubah, menghapus mata pelajaran dan soal (termasuk opsi jawabannya) tanpa error.
- Admin dapat membuat tryout, menambah/menghapus soal dari pool tryout, serta mengubah statusnya (draft -> published -> closed) melalui aksi yang jelas di UI.
- Dashboard hasil menampilkan daftar attempt beserta skor dan status untuk tryout yang dipilih.
