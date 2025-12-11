<?php

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
