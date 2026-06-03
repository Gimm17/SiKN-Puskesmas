<?php

use App\Http\Controllers\BackupController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\ImportController;
use App\Http\Controllers\PrintController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RekapController;
use App\Http\Controllers\UserController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// Redirect root ke dashboard atau login
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Rekap Data CRUD
    Route::get('/rekap', [RekapController::class, 'index'])->name('rekap.index');
    Route::get('/rekap/create', [RekapController::class, 'create'])->name('rekap.create');
    Route::post('/rekap', [RekapController::class, 'store'])->name('rekap.store');
    Route::get('/rekap/edit', [RekapController::class, 'edit'])->name('rekap.edit');
    Route::put('/rekap', [RekapController::class, 'update'])->name('rekap.update');
    Route::delete('/rekap/{rekap}', [RekapController::class, 'destroy'])->name('rekap.destroy');
    Route::delete('/rekap/bulk/destroy', [RekapController::class, 'bulkDestroy'])->name('rekap.bulk-destroy');

    Route::get('/rekap/yearly', [RekapController::class, 'yearly'])->name('rekap.yearly');
    
    // Import Excel
    Route::get('/rekap/import', [ImportController::class, 'show'])->name('rekap.import');
    Route::get('/rekap/import/batch', [ImportController::class, 'batchShow'])->name('rekap.import.batch');
    Route::post('/rekap/import/preview', [ImportController::class, 'preview'])->name('rekap.import.preview');
    Route::post('/rekap/import/switch-sheet', [ImportController::class, 'switchSheet'])->name('rekap.import.switch-sheet');
    Route::post('/rekap/import', [ImportController::class, 'store'])->name('rekap.import.store');
    // Batch Import (Opsi A: multi-file, Opsi C: multi-sheet)
    Route::post('/rekap/import/batch-preview', [ImportController::class, 'batchPreview'])->name('rekap.import.batch-preview');
    Route::post('/rekap/import/batch-store', [ImportController::class, 'batchStore'])->name('rekap.import.batch-store');
    Route::post('/rekap/import/multisheet-preview', [ImportController::class, 'multisheetPreview'])->name('rekap.import.multisheet-preview');
    Route::post('/rekap/import/multisheet-store', [ImportController::class, 'multisheetStore'])->name('rekap.import.multisheet-store');

    // Export
    Route::get('/export/xlsx', [ExportController::class, 'xlsx'])->name('export.xlsx');
    Route::get('/export/csv', [ExportController::class, 'csv'])->name('export.csv');
    Route::get('/export/pdf', [ExportController::class, 'pdf'])->name('export.pdf');
    Route::get('/export/yearly', [ExportController::class, 'yearly'])->name('export.yearly');

    // User Management (Admin only)
    Route::get('/users', [UserController::class, 'index'])->name('users.index');
    Route::post('/users', [UserController::class, 'store'])->name('users.store');
    Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');

    // Backup & Restore (Admin only)
    Route::get('/backup', [BackupController::class, 'index'])->name('backup.index');
    Route::get('/backup/export', [BackupController::class, 'export'])->name('backup.export');
    Route::post('/backup/import', [BackupController::class, 'import'])->name('backup.import');

    // Print / Preview Cetak
    Route::get('/print/preview', [PrintController::class, 'preview'])->name('print.preview');

    // Profile (bawaan Breeze)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
