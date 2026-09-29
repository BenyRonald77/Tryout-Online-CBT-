# DESIGN.md - Tryout Online CBT

## Jenis produk

Ini adalah aplikasi kerja internal (alat ujian dan administrasi), bukan situs marketing/landing page. Tidak ada hero section, tidak ada copy penjualan, tidak ada testimoni atau statistik yang dipajang untuk meyakinkan pengunjung. Setiap layar dibangun untuk satu tugas nyata: peserta mengerjakan soal, admin mengelola data. Kejelasan dan kecepatan kerja lebih penting daripada kesan visual.

## Dial (ENERGY / RHYTHM / MOTION)

`Dial: ENERGY 2 / RHYTHM 2 / MOTION 1`

- **ENERGY 2**: setara Stripe/Vercel, tenang tapi tidak kosong. Ada satu warna aksen yang dipakai secukupnya (lihat di bawah), bukan nol aksen (steril) dan bukan aksen di semua tempat (ramai).
- **RHYTHM 2**: layout konsisten dengan sedikit variasi. Halaman ujian (fokus pada satu soal, timer, indikator simpan) berbeda susunannya dari halaman daftar/tabel admin (tabel + form), karena kebutuhan kontennya memang berbeda, bukan karena ingin bervariasi demi variasi.
- **MOTION 1**: hanya hover/focus state dan transisi bawaan Tailwind (misal transisi warna tombol). Tidak ada animasi scroll-reveal, parallax, atau animasi hias lain. Timer mundur memang berubah setiap detik, itu data real-time, bukan animasi dekoratif.

## Palet warna

- Basis netral: skala **slate/gray** dari Tailwind (bawaan Breeze) untuk teks, border, latar, dan kartu.
- Satu warna aksen: **teal** (`teal-700` untuk tombol/tautan/state aktif, `teal-50`/`teal-100` untuk latar penekanan ringan seperti badge status "Published").
  - Alasan satu baris: teal terasa tenang dan tegas untuk alat ujian/edukasi, cukup berbeda dari warna default AI (gradient biru-ke-ungu) maupun dari warna fokus bawaan Breeze (indigo), dan `teal-700` di atas putih lolos kontras WCAG AA (rasio kontras dihitung sekitar 5.4:1 untuk teks/tombol normal).
- Warna status memakai konvensi standar tanpa dijadikan aksen kedua: hijau (`green-700`) untuk benar/sukses, merah (`red-700`) untuk salah/gagal/expired, kuning/amber (`amber-700`) untuk peringatan (misal sisa waktu sedikit). Ini adalah warna fungsional (menandai kondisi nyata), bukan dekorasi, sehingga tidak melanggar batas "maksimal 2-3 warna inti + 1 aksen" (R-29) karena warna netral dan warna status tidak dihitung sebagai bagian dari aksen identitas.

## Tipografi

Font sistem default Tailwind (Inter, dibawa oleh Breeze) dipakai karena aplikasi ini butuh keterbacaan tinggi untuk teks soal dan tabel data, bukan karakter visual yang mencolok. Tidak ada monospace besar, tidak ada uppercase dengan letter-spacing lebar.

## Yang sengaja tidak dipakai

- Tidak ada ilustrasi atau gambar dekoratif (produk ini tidak butuh ilustrasi untuk dijelaskan).
- Tidak ada testimoni, statistik pengguna, atau klaim ("dipercaya X sekolah", dsb) karena tidak ada data nyata untuk itu.
- Tidak ada gradient sebagai warna utama, tidak ada glassmorphism, tidak ada glow.
- Tidak ada badge kapsul dekoratif; badge yang ada (status draft/published/closed, status jawaban tersimpan) menandai kondisi nyata, bukan hiasan.

## Komponen kunci dan alasannya

- **Timer ujian**: angka besar, warna netral yang berubah ke amber saat sisa waktu < 5 menit dan merah saat waktu habis. Perubahan warna menandai urgensi nyata (bukan dekorasi).
- **Indikator autosave**: teks kecil di sebelah pilihan jawaban ("Menyimpan...", "Tersimpan", "Gagal menyimpan, coba lagi") karena peserta harus tahu keadaan simpan yang sebenarnya, bukan asumsi selalu berhasil.
- **Tabel admin**: kolom dipilih dari keputusan yang diambil admin di tabel tersebut (misal kolom skor dan status untuk tabel hasil, bukan kolom generik "Name/Status/Date/Actions" yang tidak relevan).
