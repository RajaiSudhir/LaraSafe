<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProjectsController;
use App\Http\Controllers\BackupController;

Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.view');
Route::get('/login', [DashboardController::class, 'index'])->name('login');

Route::prefix('/projects')->group(function () {
    Route::get('/manage-projects', [ProjectsController::class, 'index'])->name('manage-projects');
    Route::get('/create-project', [ProjectsController::class, 'createProject'])->name('create-project');
    Route::post('/store-project', [ProjectsController::class, 'storeProject'])->name('store-project');
    Route::get('/edit-project/{id}', [ProjectsController::class, 'index'])->name('edit-project');
    Route::delete('/delete-project/{id}', [ProjectsController::class, 'destroyProject'])->name('delete-project');
    Route::get('/view-project/{id}', [ProjectsController::class, 'index'])->name('view-project');
});

Route::prefix('/backups')->group(function () {
    Route::get('/manage-backups', [BackupController::class, 'index'])->name('manage-backups');
    Route::get('/create-backup', [BackupController::class, 'createBackup'])->name('create-backup');
    Route::get('/view-backup/{id}', [BackupController::class, 'viewBackups'])->name('view-backup');
    Route::post('/store-backup', [BackupController::class, 'storeBackup'])->name('store-backup');
    Route::post('/retry-backup/{id}', [BackupController::class, 'retryBackup'])->name('retry-backup');
    Route::delete('/delete-backup/{id}', [BackupController::class, 'destroy'])->name('backups.destroy');
    Route::get('/download/{id}', [BackupController::class, 'download'])->name('download.backup');
    Route::get('/edit-backup/{id}', [BackupController::class, 'edit'])->name('backups.edit');
    Route::put('/update-backup/{id}', [BackupController::class, 'updateBackup'])->name('backups.update');
});

