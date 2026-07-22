<?php

use App\Http\Controllers\Admin\AcademicPeriodController;
use App\Http\Controllers\Admin\CatalogueImportController;
use App\Http\Controllers\Admin\CatalogueSetupController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\CurriculumController;
use App\Http\Controllers\Admin\LevelController;
use App\Http\Controllers\Admin\PaceController;
use App\Http\Controllers\Admin\SchoolSettingController;
use App\Http\Controllers\Admin\StaffController;
use App\Http\Controllers\Admin\StaffPasswordController;
use App\Http\Controllers\Admin\SubjectController;
use App\Http\Controllers\Admin\TermController;
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

        Route::get('academic-periods', [AcademicPeriodController::class, 'index'])->name('academic-periods.index');
        Route::post('academic-years', [AcademicPeriodController::class, 'store'])->name('academic-years.store');
        Route::put('academic-years/{academic_year}', [AcademicPeriodController::class, 'update'])->name('academic-years.update');
        Route::post('academic-years/{academic_year}/terms', [TermController::class, 'store'])->name('academic-years.terms.store');
        Route::put('academic-years/{academic_year}/terms/{term}', [TermController::class, 'update'])->name('academic-years.terms.update');

        Route::get('catalogue-setup', CatalogueSetupController::class)->name('catalogue-setup.index');
        Route::resource('levels', LevelController::class)->only(['store', 'update']);
        Route::resource('subjects', SubjectController::class)->only(['store', 'update']);
        Route::resource('courses', CourseController::class)->only(['store', 'update']);
        Route::resource('paces', PaceController::class)->only(['index', 'show', 'store', 'update']);
        Route::get('curriculum', [CurriculumController::class, 'index'])->name('curriculum.index');
        Route::post('curriculum', [CurriculumController::class, 'store'])->name('curriculum.store');
        Route::resource('catalogue-imports', CatalogueImportController::class)->only(['index', 'store', 'show']);
        Route::post('catalogue-imports/{catalogue_import}/commit', [CatalogueImportController::class, 'commit'])->name('catalogue-imports.commit');
        Route::post('catalogue-imports/{catalogue_import}/cancel', [CatalogueImportController::class, 'cancel'])->name('catalogue-imports.cancel');
    });
});

require __DIR__.'/settings.php';
