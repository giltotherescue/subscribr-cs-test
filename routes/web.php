<?php

use App\Http\Controllers\Admin\ExportSubmissionsController;
use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

// Candidate routes
Route::middleware(['throttle:10,1'])->group(function () {
    Volt::route('/', 'candidate.start')->name('start');
});

Route::middleware(['throttle:60,1'])->group(function () {
    Volt::route('/a/{token}', 'candidate.runner')->name('attempt');
    Volt::route('/a/{token}/done', 'candidate.complete')->name('attempt.done');
});

// Admin routes (HTTP Basic Auth)
Route::prefix('admin')
    ->middleware(['admin.basic', 'throttle:60,1'])
    ->group(function () {
        Route::get('/', fn () => redirect()->route('admin.attempts'))->name('admin.home');
        Volt::route('/attempts', 'admin.attempts-index')->name('admin.attempts');
        Volt::route('/attempts/{attempt}', 'admin.attempt-show')->name('admin.attempts.show');

        Route::get('/export/submissions.csv', ExportSubmissionsController::class)
            ->name('admin.export.submissions');
    });
