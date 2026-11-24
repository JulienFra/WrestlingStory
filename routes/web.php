<?php

use App\Http\Controllers\FederationController;
use App\Http\Controllers\PromoController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::resource('federations', FederationController::class)
        ->only(['index', 'create', 'store', 'show', 'destroy']);

    Route::get('promo', [PromoController::class, 'create'])->name('promo.create');
    Route::post('promo', [PromoController::class, 'store'])->name('promo.store');
});

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
