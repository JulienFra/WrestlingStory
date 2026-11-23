<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\SettingsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'can:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', DashboardController::class)->name('index');

    Route::get('settings', [SettingsController::class, 'edit'])->name('settings.edit');
    Route::put('settings/ai', [SettingsController::class, 'updateAi'])->name('settings.ai.update');
    Route::put('users/{user}/tier', [SettingsController::class, 'updateTier'])->name('users.tier.update');
});
