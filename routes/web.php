<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\GradeController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — Aplikasi Absensi & Penilaian Guru
|--------------------------------------------------------------------------
*/

// ── Authentication & Registration ─────────────────────────────────────
Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.process');
Route::post('/logout',[AuthController::class, 'logout'])->name('logout');

Route::get('/signup', [AuthController::class, 'showSignup'])->name('signup');
Route::post('/signup', [AuthController::class, 'processSignup'])->name('signup.process');

// ── Routes yang Membutuhkan Login ─────────────────────────────────────
Route::middleware('auth')->group(function () {

    // ── Dashboard ──────────────────────────────────────────────────────
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // ── Absensi ────────────────────────────────────────────────────────
    Route::prefix('absensi')->name('attendance.')->group(function () {
        Route::get('/',     [AttendanceController::class, 'index'])->name('index');
        Route::post('/',    [AttendanceController::class, 'store'])->name('store');
    });

    // ── Penilaian & Leger ──────────────────────────────────────────────
    Route::prefix('nilai')->name('grades.')->group(function () {
        Route::get('/',     [GradeController::class, 'index'])->name('index');
        Route::post('/',    [GradeController::class, 'store'])->name('store');
        Route::post('/tasks', [GradeController::class, 'updateTasks'])->name('update_tasks');
    });

    // ── Super Admin Only ──────────────────────────────────────────────
    Route::middleware('role:super_admin')->group(function () {


        // Data Siswa
        Route::prefix('siswa')->name('students.')->group(function () {
            Route::get('/',         [StudentController::class, 'index'])->name('index');
            Route::get('/template', [StudentController::class, 'downloadTemplate'])->name('template');
            Route::post('/tambah',  [StudentController::class, 'store'])->name('store');
            Route::post('/impor',   [StudentController::class, 'import'])->name('import');
            Route::put('/{nis}',    [StudentController::class, 'update'])->name('update');
            Route::delete('/{nis}', [StudentController::class, 'destroy'])->name('destroy');
        });

        // Data Guru, Verifikasi & Manajemen Akun Lokal
        Route::prefix('admin')->name('admin.')->group(function () {
            
            Route::post('/deploy-update', [App\Http\Controllers\UserManagementController::class, 'runUpdate'])->name('deploy-update');

            // Manajemen Akun Lokal (Users)
            Route::prefix('users')->name('users.')->group(function () {
                Route::get('/', [App\Http\Controllers\UserManagementController::class, 'index'])->name('index');
                Route::put('/{id}', [App\Http\Controllers\UserManagementController::class, 'update'])->name('update');
                Route::post('/{id}/reset-password', [App\Http\Controllers\UserManagementController::class, 'resetPassword'])->name('reset-password');
                Route::delete('/{id}', [App\Http\Controllers\UserManagementController::class, 'destroy'])->name('destroy');
            });

            Route::prefix('verifikasi')->name('verifications.')->group(function () {
                Route::get('/',         [App\Http\Controllers\VerificationController::class, 'index'])->name('index');
                Route::post('/{id}',    [App\Http\Controllers\VerificationController::class, 'verify'])->name('verify');
            });

            Route::get('subjects', [App\Http\Controllers\SubjectController::class, 'index'])->name('subjects.index');
            Route::post('subjects', [App\Http\Controllers\SubjectController::class, 'store'])->name('subjects.store');
            Route::put('subjects/{id}', [App\Http\Controllers\SubjectController::class, 'update'])->name('subjects.update');
            Route::delete('subjects/{id}', [App\Http\Controllers\SubjectController::class, 'destroy'])->name('subjects.destroy');
        });
    });
});
