<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\LicitacionController;
use App\Http\Controllers\CurriculumController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ConfigurationController;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Sistema de carpetas y gestión documental
    Route::resource('folders', FolderController::class);
    Route::get('/folders-tree', [FolderController::class, 'getTree'])->name('folders.tree');
    Route::get('/folders/{folder}/export-contracts', [FolderController::class, 'exportContracts'])->name('folders.export-contracts');

    // Contratos dentro del sistema de carpetas
    Route::resource('contracts', ContractController::class);
    Route::get('/contracts/{contract}/download', [ContractController::class, 'download'])->name('contracts.download');
    Route::get('/contracts/{contract}/view', [ContractController::class, 'viewPdf'])->name('contracts.view');

    // Módulos existentes
    Route::resource('licitaciones', LicitacionController::class)->parameters(['licitaciones' => 'licitacion']);
    Route::resource('cvs', CurriculumController::class);
    Route::resource('users', UserController::class); // Config maps to users here

    // Alias for config menu link
    Route::get('/config', [UserController::class, 'index'])->name('config');

    // Configuración de imagen 360
    Route::get('/config/image360', [ConfigurationController::class, 'image360'])->name('config.image360');
    Route::post('/config/image360/update', [ConfigurationController::class, 'updateImage360'])->name('config.image360.update');
    Route::post('/config/image360/restore', [ConfigurationController::class, 'restoreDefault360'])->name('config.image360.restore');
});

require __DIR__.'/auth.php';
