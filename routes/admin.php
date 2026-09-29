<?php

use App\Livewire\Admin\QuestionManager;
use App\Livewire\Admin\ResultsDashboard;
use App\Livewire\Admin\SubjectManager;
use App\Livewire\Admin\TryoutManager;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::redirect('/', '/admin/mata-pelajaran');
    Route::get('/mata-pelajaran', SubjectManager::class)->name('subjects');
    Route::get('/soal', QuestionManager::class)->name('questions');
    Route::get('/tryout', TryoutManager::class)->name('tryouts');
    Route::get('/hasil', ResultsDashboard::class)->name('results');
});
