<?php

use App\Http\Controllers\TryoutController;
use App\Livewire\Peserta\ExamPage;
use App\Livewire\Peserta\RankingPage;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->group(function () {
    Route::get('/tryout', [TryoutController::class, 'index'])->name('tryout.index');
    Route::post('/tryout/{tryout}/mulai', [TryoutController::class, 'mulai'])->name('tryout.mulai');
    Route::get('/tryout/{tryout}/kerjakan', ExamPage::class)->name('tryout.kerjakan');
    Route::get('/tryout/{tryout}/hasil', [TryoutController::class, 'hasil'])->name('tryout.hasil');
    Route::get('/tryout/{tryout}/ranking', RankingPage::class)->name('tryout.ranking');
});

require __DIR__.'/admin.php';
require __DIR__.'/auth.php';
