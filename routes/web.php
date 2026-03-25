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
Route::post('/dashboard/r2-refresh-storage', [DashboardController::class, 'refreshR2Storage'])
    ->middleware(['auth', 'verified', 'admin'])
    ->name('dashboard.r2-refresh-storage');

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
        Route::delete('/folders/documents/{document}/files/{file}', [FolderDocumentController::class, 'destroyFile'])->name('folders.documents.files.destroy');
        Route::put('/folders/documents/{document}/move', [FolderDocumentController::class, 'move'])->name('folders.documents.move');
        Route::post('/folders/documents/move-bulk', [FolderDocumentController::class, 'moveBulk'])->name('folders.documents.move-bulk');
        Route::get('/folders/{folder}/documents/download-zip', [FolderDocumentController::class, 'downloadZip'])->name('folders.documents.download-zip');
        Route::get('/folders/{folder}/documents/export-excel', [FolderController::class, 'exportDocuments'])->name('folders.documents.export-excel');
        Route::resource('contracts', ContractController::class);
        Route::get('/contracts/{contract}/download', [ContractController::class, 'download'])->name('contracts.download');
        Route::get('/contracts/{contract}/view', [ContractController::class, 'viewPdf'])->name('contracts.view');
        Route::post('/licitaciones/folders', [LicitacionController::class, 'storeFolder'])->name('licitaciones.folders.store');
        Route::get('/licitaciones/export', [LicitacionController::class, 'export'])->name('licitaciones.export');
        Route::get('/licitaciones/{licitacion}/export', [LicitacionController::class, 'exportProject'])->name('licitaciones.export-project');
        Route::put('/licitaciones/{licitacion}/move', [LicitacionController::class, 'move'])->name('licitaciones.move');
        Route::post('/licitaciones/move-bulk', [LicitacionController::class, 'moveBulk'])->name('licitaciones.move-bulk');
        Route::resource('licitaciones', LicitacionController::class)->parameters(['licitaciones' => 'licitacion']);
        Route::post('/cvs/folders', [CurriculumController::class, 'storeFolder'])->name('cvs.folders.store');
        Route::get('/cvs/download-zip', [CurriculumController::class, 'downloadZip'])->name('cvs.download-zip');
        Route::get('/cvs/{cv}/download', [CurriculumController::class, 'download'])->name('cvs.download');
        Route::get('/cvs/{cv}/files/{file}/download', [CurriculumController::class, 'downloadFile'])->name('cvs.files.download');
        Route::put('/cvs/{cv}/move', [CurriculumController::class, 'move'])->name('cvs.move');
        Route::post('/cvs/move-bulk', [CurriculumController::class, 'moveBulk'])->name('cvs.move-bulk');
        Route::resource('cvs', CurriculumController::class);
        Route::post('/consultor-obras/folders', [\App\Http\Controllers\ConsultorObraController::class, 'storeFolder'])->name('consultor-obras.folders.store');
        Route::get('/consultor-obras/export', [\App\Http\Controllers\ConsultorObraController::class, 'export'])->name('consultor-obras.export');
        Route::get('/consultor-obras/{consultorObra}/export', [\App\Http\Controllers\ConsultorObraController::class, 'exportProject'])->name('consultor-obras.export-project');
        Route::post('/consultor-obras/{consultor_obra}/update', [\App\Http\Controllers\ConsultorObraController::class, 'update'])->name('consultor-obras.update.post');
        Route::post('/consultor-obras/{consultor_obra}/reactivate', [\App\Http\Controllers\ConsultorObraController::class, 'reactivate'])->name('consultor-obras.reactivate');
        Route::put('/consultor-obras/{consultorObra}/move', [\App\Http\Controllers\ConsultorObraController::class, 'move'])->name('consultor-obras.move');
        Route::post('/consultor-obras/move-bulk', [\App\Http\Controllers\ConsultorObraController::class, 'moveBulk'])->name('consultor-obras.move-bulk');
        Route::resource('consultor-obras', \App\Http\Controllers\ConsultorObraController::class)->parameters(['consultor-obras' => 'consultorObra']);
        Route::post('/ejecutor-obra/folders', [\App\Http\Controllers\EjecutorObraController::class, 'storeFolder'])->name('ejecutor-obra.folders.store');
        Route::get('/ejecutor-obra/export', [\App\Http\Controllers\EjecutorObraController::class, 'export'])->name('ejecutor-obra.export');
        Route::get('/ejecutor-obra/{ejecutorObra}/export', [\App\Http\Controllers\EjecutorObraController::class, 'exportProject'])->name('ejecutor-obra.export-project');
        Route::put('/ejecutor-obra/{ejecutorObra}/move', [\App\Http\Controllers\EjecutorObraController::class, 'move'])->name('ejecutor-obra.move');
        Route::post('/ejecutor-obra/move-bulk', [\App\Http\Controllers\EjecutorObraController::class, 'moveBulk'])->name('ejecutor-obra.move-bulk');
        Route::resource('ejecutor-obra', \App\Http\Controllers\EjecutorObraController::class)->parameters(['ejecutor-obra' => 'ejecutorObra']);
        Route::post('/proveedor-servicios/folders', [\App\Http\Controllers\ProveedorServicioController::class, 'storeFolder'])->name('proveedor-servicios.folders.store');
        Route::get('/proveedor-servicios/export', [\App\Http\Controllers\ProveedorServicioController::class, 'export'])->name('proveedor-servicios.export');
        Route::get('/proveedor-servicios/{proveedorServicio}/export', [\App\Http\Controllers\ProveedorServicioController::class, 'exportProject'])->name('proveedor-servicios.export-project');
        Route::put('/proveedor-servicios/{proveedorServicio}/move', [\App\Http\Controllers\ProveedorServicioController::class, 'move'])->name('proveedor-servicios.move');
        Route::post('/proveedor-servicios/move-bulk', [\App\Http\Controllers\ProveedorServicioController::class, 'moveBulk'])->name('proveedor-servicios.move-bulk');
        Route::resource('proveedor-servicios', \App\Http\Controllers\ProveedorServicioController::class)->parameters(['proveedor-servicios' => 'proveedorServicio']);
        Route::post('/proveedor-bienes/folders', [\App\Http\Controllers\ProveedorBienController::class, 'storeFolder'])->name('proveedor-bienes.folders.store');
        Route::get('/proveedor-bienes/export', [\App\Http\Controllers\ProveedorBienController::class, 'export'])->name('proveedor-bienes.export');
        Route::put('/proveedor-bienes/{proveedorBien}/move', [\App\Http\Controllers\ProveedorBienController::class, 'move'])->name('proveedor-bienes.move');
        Route::post('/proveedor-bienes/move-bulk', [\App\Http\Controllers\ProveedorBienController::class, 'moveBulk'])->name('proveedor-bienes.move-bulk');
        Route::resource('proveedor-bienes', \App\Http\Controllers\ProveedorBienController::class)->parameters(['proveedor-bienes' => 'proveedorBien']);
        Route::post('/especialistas-ejecucion/folders', [\App\Http\Controllers\EspecialistaEjecucionController::class, 'storeFolder'])->name('especialistas-ejecucion.folders.store');
        Route::get('/especialistas-ejecucion/export', [\App\Http\Controllers\EspecialistaEjecucionController::class, 'export'])->name('especialistas-ejecucion.export');
        Route::put('/especialistas-ejecucion/{especialistaEjecucion}/move', [\App\Http\Controllers\EspecialistaEjecucionController::class, 'move'])->name('especialistas-ejecucion.move');
        Route::post('/especialistas-ejecucion/move-bulk', [\App\Http\Controllers\EspecialistaEjecucionController::class, 'moveBulk'])->name('especialistas-ejecucion.move-bulk');
        Route::resource('especialistas-ejecucion', \App\Http\Controllers\EspecialistaEjecucionController::class)->parameters(['especialistas-ejecucion' => 'especialistaEjecucion']);
        Route::post('/municipalidades-funcionario-publico/folders', [\App\Http\Controllers\MunicipalidadFuncionarioPublicoController::class, 'storeFolder'])->name('municipalidades-funcionario-publico.folders.store');
        Route::get('/municipalidades-funcionario-publico/export', [\App\Http\Controllers\MunicipalidadFuncionarioPublicoController::class, 'export'])->name('municipalidades-funcionario-publico.export');
        Route::put('/municipalidades-funcionario-publico/{municipalidadFuncionarioPublico}/move', [\App\Http\Controllers\MunicipalidadFuncionarioPublicoController::class, 'move'])->name('municipalidades-funcionario-publico.move');
        Route::post('/municipalidades-funcionario-publico/move-bulk', [\App\Http\Controllers\MunicipalidadFuncionarioPublicoController::class, 'moveBulk'])->name('municipalidades-funcionario-publico.move-bulk');
        Route::resource('municipalidades-funcionario-publico', \App\Http\Controllers\MunicipalidadFuncionarioPublicoController::class)->parameters(['municipalidades-funcionario-publico' => 'municipalidadFuncionarioPublico']);
        Route::post('/especialistas-consultoria/folders', [\App\Http\Controllers\EspecialistaConsultoriaController::class, 'storeFolder'])->name('especialistas-consultoria.folders.store');
        Route::get('/especialistas-consultoria/export', [\App\Http\Controllers\EspecialistaConsultoriaController::class, 'export'])->name('especialistas-consultoria.export');
        Route::put('/especialistas-consultoria/{especialistaConsultoria}/move', [\App\Http\Controllers\EspecialistaConsultoriaController::class, 'move'])->name('especialistas-consultoria.move');
        Route::post('/especialistas-consultoria/move-bulk', [\App\Http\Controllers\EspecialistaConsultoriaController::class, 'moveBulk'])->name('especialistas-consultoria.move-bulk');
        Route::resource('especialistas-consultoria', \App\Http\Controllers\EspecialistaConsultoriaController::class)->parameters(['especialistas-consultoria' => 'especialistaConsultoria']);
        Route::post('/inmobiliaria/folders', [\App\Http\Controllers\InmobiliariaController::class, 'storeFolder'])->name('inmobiliaria.folders.store');
        Route::get('/inmobiliaria/export', [\App\Http\Controllers\InmobiliariaController::class, 'export'])->name('inmobiliaria.export');
        Route::put('/inmobiliaria/{inmobiliaria}/move', [\App\Http\Controllers\InmobiliariaController::class, 'move'])->name('inmobiliaria.move');
        Route::post('/inmobiliaria/move-bulk', [\App\Http\Controllers\InmobiliariaController::class, 'moveBulk'])->name('inmobiliaria.move-bulk');
        Route::resource('inmobiliaria', \App\Http\Controllers\InmobiliariaController::class);
        Route::post('/topografia/folders', [\App\Http\Controllers\TopografiaController::class, 'storeFolder'])->name('topografia.folders.store');
        Route::get('/topografia/export', [\App\Http\Controllers\TopografiaController::class, 'export'])->name('topografia.export');
        Route::put('/topografia/{topografia}/move', [\App\Http\Controllers\TopografiaController::class, 'move'])->name('topografia.move');
        Route::post('/topografia/move-bulk', [\App\Http\Controllers\TopografiaController::class, 'moveBulk'])->name('topografia.move-bulk');
        Route::resource('topografia', \App\Http\Controllers\TopografiaController::class);
        Route::post('/tecnologia/folders', [\App\Http\Controllers\TecnologiaController::class, 'storeFolder'])->name('tecnologia.folders.store');
        Route::get('/tecnologia/export', [\App\Http\Controllers\TecnologiaController::class, 'export'])->name('tecnologia.export');
        Route::put('/tecnologia/{tecnologia}/move', [\App\Http\Controllers\TecnologiaController::class, 'move'])->name('tecnologia.move');
        Route::post('/tecnologia/move-bulk', [\App\Http\Controllers\TecnologiaController::class, 'moveBulk'])->name('tecnologia.move-bulk');
        Route::resource('tecnologia', \App\Http\Controllers\TecnologiaController::class);
        Route::post('/plantillas-ing/folders', [\App\Http\Controllers\PlantillaIngController::class, 'storeFolder'])->name('plantillas-ing.folders.store');
        Route::get('/plantillas-ing/export', [\App\Http\Controllers\PlantillaIngController::class, 'export'])->name('plantillas-ing.export');
        Route::put('/plantillas-ing/{plantillaIng}/move', [\App\Http\Controllers\PlantillaIngController::class, 'move'])->name('plantillas-ing.move');
        Route::post('/plantillas-ing/move-bulk', [\App\Http\Controllers\PlantillaIngController::class, 'moveBulk'])->name('plantillas-ing.move-bulk');
        Route::resource('plantillas-ing', \App\Http\Controllers\PlantillaIngController::class);
        Route::post('/registro-expedientes/folders', [\App\Http\Controllers\RegistroExpedienteController::class, 'storeFolder'])->name('registro-expedientes.folders.store');
        Route::get('/registro-expedientes/export', [\App\Http\Controllers\RegistroExpedienteController::class, 'export'])->name('registro-expedientes.export');
        Route::get('/registro-expedientes/listar-por-tipo', [\App\Http\Controllers\RegistroExpedienteController::class, 'listarPorTipo'])->name('registro-expedientes.listar-por-tipo');
        Route::put('/registro-expedientes/{registroExpediente}/move', [\App\Http\Controllers\RegistroExpedienteController::class, 'move'])->name('registro-expedientes.move');
        Route::post('/registro-expedientes/move-bulk', [\App\Http\Controllers\RegistroExpedienteController::class, 'moveBulk'])->name('registro-expedientes.move-bulk');
        Route::resource('registro-expedientes', \App\Http\Controllers\RegistroExpedienteController::class)->parameters(['registro-expedientes' => 'registroExpediente']);
        Route::post('/record-share', [\App\Http\Controllers\RecordShareController::class, 'store'])->name('record-share.store');
    });

    // Configuración y usuarios: solo Administrador
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class);
        Route::get('/users/{user}/folder-permissions', [UserController::class, 'folderPermissions'])->name('users.folder-permissions');
        Route::put('/users/{user}/folder-permissions', [UserController::class, 'updateFolderPermissions'])->name('users.folder-permissions.update');
        Route::get('/config', [UserController::class, 'index'])->name('config');
        Route::get('/panel-control', [UserController::class, 'index'])->name('panel-control');
        Route::get('/config/image360', [ConfigurationController::class, 'image360'])->name('config.image360');
        Route::post('/config/image360/update', [ConfigurationController::class, 'updateImage360'])->name('config.image360.update');
        Route::post('/config/image360/restore', [ConfigurationController::class, 'restoreDefault360'])->name('config.image360.restore');
        // Reseteo de datos deshabilitado por seguridad (acceso solo en entorno local vía controlador)
        // Route::get('/config/reset-data', [ConfigurationController::class, 'resetData'])->name('config.resetData');
        // Route::post('/config/reset-data/execute', [ConfigurationController::class, 'executeResetData'])->name('config.resetData.execute');
    });
});

require __DIR__.'/auth.php';
