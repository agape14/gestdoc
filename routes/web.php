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
    Route::get('/licitaciones/export', [LicitacionController::class, 'export'])->name('licitaciones.export');
    Route::get('/licitaciones/{licitacion}/export', [LicitacionController::class, 'exportProject'])->name('licitaciones.export-project');
    Route::resource('cvs', CurriculumController::class);
    Route::resource('users', UserController::class); // Config maps to users here

    // TECCONING Modules
    Route::resource('consultor-obras', \App\Http\Controllers\ConsultorObraController::class);
    Route::get('/consultor-obras/export', [\App\Http\Controllers\ConsultorObraController::class, 'export'])->name('consultor-obras.export');
    Route::get('/consultor-obras/{consultorObra}/export', [\App\Http\Controllers\ConsultorObraController::class, 'exportProject'])->name('consultor-obras.export-project');
    Route::resource('ejecutor-obra', \App\Http\Controllers\EjecutorObraController::class);
    Route::get('/ejecutor-obra/export', [\App\Http\Controllers\EjecutorObraController::class, 'export'])->name('ejecutor-obra.export');
    Route::get('/ejecutor-obra/{ejecutorObra}/export', [\App\Http\Controllers\EjecutorObraController::class, 'exportProject'])->name('ejecutor-obra.export-project');
    Route::resource('proveedor-servicios', \App\Http\Controllers\ProveedorServicioController::class);
    Route::get('/proveedor-servicios/export', [\App\Http\Controllers\ProveedorServicioController::class, 'export'])->name('proveedor-servicios.export');
    Route::get('/proveedor-servicios/{proveedorServicio}/export', [\App\Http\Controllers\ProveedorServicioController::class, 'exportProject'])->name('proveedor-servicios.export-project');
    // Placeholder routes for others or reuse generic if needed, currently just resources
    Route::resource('proveedor-bienes', \App\Http\Controllers\ProveedorBienController::class);
    Route::resource('especialistas-ejecucion', \App\Http\Controllers\EspecialistaEjecucionController::class);
    Route::resource('especialistas-consultoria', \App\Http\Controllers\EspecialistaConsultoriaController::class);
    Route::resource('inmobiliaria', \App\Http\Controllers\InmobiliariaController::class);
    Route::resource('topografia', \App\Http\Controllers\TopografiaController::class);
    Route::resource('tecnologia', \App\Http\Controllers\TecnologiaController::class);
    Route::resource('plantillas-ing', \App\Http\Controllers\PlantillaIngController::class);

    // Alias for config menu link
    Route::get('/config', [UserController::class, 'index'])->name('config');
    Route::get('/panel-control', [UserController::class, 'index'])->name('panel-control');

    // Configuración de imagen 360
    Route::get('/config/image360', [ConfigurationController::class, 'image360'])->name('config.image360');
    Route::post('/config/image360/update', [ConfigurationController::class, 'updateImage360'])->name('config.image360.update');
    Route::post('/config/image360/restore', [ConfigurationController::class, 'restoreDefault360'])->name('config.image360.restore');
});

require __DIR__.'/auth.php';
