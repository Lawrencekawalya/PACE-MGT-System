<?php

use App\Http\Controllers\Admin\SchoolSettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffPasswordController;
use App\Http\Controllers\DashboardController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('school-settings', [SchoolSettingController::class, 'edit'])->name('school-settings.edit');
        Route::post('school-settings', [SchoolSettingController::class, 'update'])->name('school-settings.update');
        Route::resource('staff', StaffController::class)
            ->parameters(['staff' => 'user'])
            ->except(['show', 'destroy']);
        Route::put('staff/{user}/password', StaffPasswordController::class)->name('staff.password.update');
    });
});

require __DIR__.'/settings.php';
