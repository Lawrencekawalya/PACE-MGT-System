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
use App\Http\Controllers\PaceAssignmentController;
use App\Http\Controllers\PaceAssignmentStatusController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentEnrollmentController;
use App\Http\Controllers\StudentStatusController;
use Illuminate\Support\Facades\Route;

Route::get('/', fn () => redirect()->route(auth()->check() ? 'dashboard' : 'login'))->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::resource('pace-assignments', PaceAssignmentController::class)->only(['index', 'show', 'store']);
    Route::put('pace-assignments/{pace_assignment}/status', PaceAssignmentStatusController::class)->name('pace-assignments.status.update');
    Route::resource('students', StudentController::class)->except('destroy');
    Route::put('students/{student}/status', StudentStatusController::class)->name('students.status.update');
    Route::get('students/{student}/enrollments/create', [StudentEnrollmentController::class, 'create'])->name('students.enrollments.create');
    Route::post('students/{student}/enrollments', [StudentEnrollmentController::class, 'store'])->name('students.enrollments.store');
    Route::get('students/{student}/enrollments/{enrollment}/edit', [StudentEnrollmentController::class, 'edit'])->name('students.enrollments.edit');
    Route::put('students/{student}/enrollments/{enrollment}', [StudentEnrollmentController::class, 'update'])->name('students.enrollments.update');

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
