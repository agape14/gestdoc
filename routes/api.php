<?php

use App\Http\Controllers\DocumentoController;
use Illuminate\Support\Facades\Route;

Route::post('/documentos', [DocumentoController::class, 'store']);
