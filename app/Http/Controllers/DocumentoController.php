<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class DocumentoController extends Controller
{
    /**
     * Sube un archivo PDF a Cloudflare R2 (disco 'r2') en la carpeta 'expedientes'.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'archivo' => 'required|file|mimes:pdf|max:25600',
        ], [
            'archivo.required' => 'Debe adjuntar un archivo PDF.',
            'archivo.file' => 'El campo debe ser un archivo válido.',
            'archivo.mimes' => 'Solo se permiten archivos PDF.',
            'archivo.max' => 'El archivo no debe superar 25 MB.',
        ], [
            'archivo' => 'archivo',
        ]);

        $file = $request->file('archivo');
        $nombreOriginal = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension() ?: 'pdf';
        $nombreUnico = Str::uuid() . '-' . Str::slug(pathinfo($nombreOriginal, PATHINFO_FILENAME)) . '.' . $extension;

        $path = $file->storeAs('expedientes', $nombreUnico, 'r2');

        $url = Storage::disk('r2')->url($path);

        return response()->json([
            'success' => true,
            'url' => $url,
            'path' => $path,
            'nombre' => $nombreOriginal,
        ], 201);
    }
}
