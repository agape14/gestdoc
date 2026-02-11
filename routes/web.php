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
use App\Http\Controllers\FolderDocumentController;
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

    // Rutas que requieren acceso al menú (Operador/Visualizador solo si tienen el menú en allowed_menus)
    Route::middleware('menu')->group(function () {
        Route::resource('folders', FolderController::class);
        Route::get('/folders-tree', [FolderController::class, 'getTree'])->name('folders.tree');
        Route::post('/folders/{folder}/documents', [FolderDocumentController::class, 'store'])->name('folders.documents.store');
        Route::put('/folders/documents/{document}', [FolderDocumentController::class, 'update'])->name('folders.documents.update');
        Route::delete('/folders/documents/{document}', [FolderDocumentController::class, 'destroy'])->name('folders.documents.destroy');
        Route::get('/folders/documents/{document}/files/{file}/download', [FolderDocumentController::class, 'download'])->name('folders.documents.files.download');
        Route::get('/folders/documents/{document}/files/{file}/view', [FolderDocumentController::class, 'view'])->name('folders.documents.files.view');
        Route::get('/folders/{folder}/documents/download-zip', [FolderDocumentController::class, 'downloadZip'])->name('folders.documents.download-zip');
        Route::resource('contracts', ContractController::class);
        Route::get('/contracts/{contract}/download', [ContractController::class, 'download'])->name('contracts.download');
        Route::get('/contracts/{contract}/view', [ContractController::class, 'viewPdf'])->name('contracts.view');
        Route::post('/licitaciones/folders', [LicitacionController::class, 'storeFolder'])->name('licitaciones.folders.store');
        Route::get('/licitaciones/export', [LicitacionController::class, 'export'])->name('licitaciones.export');
        Route::get('/licitaciones/{licitacion}/export', [LicitacionController::class, 'exportProject'])->name('licitaciones.export-project');
        Route::resource('licitaciones', LicitacionController::class)->parameters(['licitaciones' => 'licitacion']);
        Route::post('/cvs/folders', [CurriculumController::class, 'storeFolder'])->name('cvs.folders.store');
        Route::resource('cvs', CurriculumController::class);
        Route::post('/consultor-obras/folders', [\App\Http\Controllers\ConsultorObraController::class, 'storeFolder'])->name('consultor-obras.folders.store');
        Route::get('/consultor-obras/export', [\App\Http\Controllers\ConsultorObraController::class, 'export'])->name('consultor-obras.export');
        Route::get('/consultor-obras/{consultorObra}/export', [\App\Http\Controllers\ConsultorObraController::class, 'exportProject'])->name('consultor-obras.export-project');
        Route::resource('consultor-obras', \App\Http\Controllers\ConsultorObraController::class)->parameters(['consultor-obras' => 'consultorObra']);
        Route::post('/ejecutor-obra/folders', [\App\Http\Controllers\EjecutorObraController::class, 'storeFolder'])->name('ejecutor-obra.folders.store');
        Route::get('/ejecutor-obra/export', [\App\Http\Controllers\EjecutorObraController::class, 'export'])->name('ejecutor-obra.export');
        Route::get('/ejecutor-obra/{ejecutorObra}/export', [\App\Http\Controllers\EjecutorObraController::class, 'exportProject'])->name('ejecutor-obra.export-project');
        Route::resource('ejecutor-obra', \App\Http\Controllers\EjecutorObraController::class)->parameters(['ejecutor-obra' => 'ejecutorObra']);
        Route::post('/proveedor-servicios/folders', [\App\Http\Controllers\ProveedorServicioController::class, 'storeFolder'])->name('proveedor-servicios.folders.store');
        Route::get('/proveedor-servicios/export', [\App\Http\Controllers\ProveedorServicioController::class, 'export'])->name('proveedor-servicios.export');
        Route::get('/proveedor-servicios/{proveedorServicio}/export', [\App\Http\Controllers\ProveedorServicioController::class, 'exportProject'])->name('proveedor-servicios.export-project');
        Route::resource('proveedor-servicios', \App\Http\Controllers\ProveedorServicioController::class)->parameters(['proveedor-servicios' => 'proveedorServicio']);
        Route::post('/proveedor-bienes/folders', [\App\Http\Controllers\ProveedorBienController::class, 'storeFolder'])->name('proveedor-bienes.folders.store');
        Route::resource('proveedor-bienes', \App\Http\Controllers\ProveedorBienController::class)->parameters(['proveedor-bienes' => 'proveedorBien']);
        Route::post('/especialistas-ejecucion/folders', [\App\Http\Controllers\EspecialistaEjecucionController::class, 'storeFolder'])->name('especialistas-ejecucion.folders.store');
        Route::resource('especialistas-ejecucion', \App\Http\Controllers\EspecialistaEjecucionController::class)->parameters(['especialistas-ejecucion' => 'especialistaEjecucion']);
        Route::post('/especialistas-consultoria/folders', [\App\Http\Controllers\EspecialistaConsultoriaController::class, 'storeFolder'])->name('especialistas-consultoria.folders.store');
        Route::resource('especialistas-consultoria', \App\Http\Controllers\EspecialistaConsultoriaController::class)->parameters(['especialistas-consultoria' => 'especialistaConsultoria']);
        Route::post('/inmobiliaria/folders', [\App\Http\Controllers\InmobiliariaController::class, 'storeFolder'])->name('inmobiliaria.folders.store');
        Route::resource('inmobiliaria', \App\Http\Controllers\InmobiliariaController::class);
        Route::post('/topografia/folders', [\App\Http\Controllers\TopografiaController::class, 'storeFolder'])->name('topografia.folders.store');
        Route::resource('topografia', \App\Http\Controllers\TopografiaController::class);
        Route::post('/tecnologia/folders', [\App\Http\Controllers\TecnologiaController::class, 'storeFolder'])->name('tecnologia.folders.store');
        Route::resource('tecnologia', \App\Http\Controllers\TecnologiaController::class);
        Route::post('/plantillas-ing/folders', [\App\Http\Controllers\PlantillaIngController::class, 'storeFolder'])->name('plantillas-ing.folders.store');
        Route::resource('plantillas-ing', \App\Http\Controllers\PlantillaIngController::class);
        Route::post('/record-share', [\App\Http\Controllers\RecordShareController::class, 'store'])->name('record-share.store');
    });

    // Configuración y usuarios: solo Administrador
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::get('/config', [UserController::class, 'index'])->name('config');
        Route::get('/panel-control', [UserController::class, 'index'])->name('panel-control');
        Route::get('/config/image360', [ConfigurationController::class, 'image360'])->name('config.image360');
        Route::post('/config/image360/update', [ConfigurationController::class, 'updateImage360'])->name('config.image360.update');
        Route::post('/config/image360/restore', [ConfigurationController::class, 'restoreDefault360'])->name('config.image360.restore');
        Route::get('/config/reset-data', [ConfigurationController::class, 'resetData'])->name('config.resetData');
        Route::post('/config/reset-data/execute', [ConfigurationController::class, 'executeResetData'])->name('config.resetData.execute');
    });
});

require __DIR__.'/auth.php';
