<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Menjaga waktu ujian tetap ditegakkan oleh server: attempt yang waktunya
// habis tapi tab-nya ditinggalkan peserta akan tetap ditutup dan dinilai,
// dan tryout yang jadwalnya lewat akan otomatis berstatus closed.
// Wajib menjalankan `php artisan schedule:work` (atau cron schedule:run
// setiap menit) di production agar ini benar-benar berjalan.
Schedule::command('attempts:auto-submit-expired')->everyMinute();
