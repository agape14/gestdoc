<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\DocumentFile;
use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class FolderDocumentController extends Controller
{
    /**
     * Store a new document in the folder (gestión documental).
     */
    public function store(Request $request)
    {
        if ($request->user()->role === 'Visualizador') {
            abort(403, 'No tienes permiso para crear documentos. Solo puedes ver.');
        }
        $validated = $request->validate([
            'folder_id' => 'required|exists:folders,id',
            'numero' => 'nullable|string|max:100',
            'fecha_documento' => 'nullable|date',
            'asunto' => 'nullable|string|max:500',
            'remitente' => 'nullable|string|max:255',
            'destinatario' => 'nullable|string|max:255',
            'referencia' => 'nullable|string|max:5000',
            'observaciones' => 'nullable|string',
            'folios' => 'required|integer|min:0',
        ], [], ['folios' => 'folios']);

        $folder = Folder::findOrFail($validated['folder_id']);
        if ($folder->module !== null) {
            return redirect()->back()->with('error', 'Esta carpeta no es de gestión documental.');
        }

        $archivosData = $this->parseArchivosFromRequest($request);
        if (empty($archivosData)) {
            return redirect()->back()->withErrors(['archivos' => 'Debe adjuntar al menos un archivo PDF con su nombre.'])->withInput();
        }

        $document = Document::create([
            'folder_id' => $validated['folder_id'],
            'user_id' => auth()->id(),
            'numero' => $validated['numero'] ?? null,
            'fecha_documento' => $validated['fecha_documento'] ?? null,
            'asunto' => $validated['asunto'] ?? null,
            'remitente' => $validated['remitente'] ?? null,
            'destinatario' => $validated['destinatario'] ?? null,
            'referencia' => $validated['referencia'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'folios' => (int) ($validated['folios'] ?? 0),
        ]);

        foreach ($archivosData as $index => $item) {
            $request->validate([
                "archivos.{$index}.file" => 'required|file|mimes:pdf|max:25600',
            ], [
                "archivos.{$index}.file.max" => 'Cada archivo PDF no debe superar 25 MB.',
            ], ["archivos.{$index}.file" => 'archivo PDF']);
            $nombre = \Str::limit($item['nombre_archivo'], 255);
            $path = $item['file']->store('expedientes/documentos', 'r2');
            DocumentFile::create([
                'document_id' => $document->id,
                'nombre_archivo' => $nombre,
                'path' => $path,
                'orden' => $index,
            ]);
        }

        return redirect()->route('folders.show', $folder->id)
            ->with('success', 'Documento registrado correctamente.');
    }

    /**
     * Update document and optionally replace/add files.
     */
    public function update(Request $request, Document $document)
    {
        if ($request->user()->role === 'Visualizador') {
            abort(403, 'No tienes permiso para editar documentos. Solo puedes ver.');
        }
        $document->load('folder');
        if ($document->folder->module !== null) {
            return redirect()->back()->with('error', 'Este documento no pertenece a gestión documental.');
        }

        $validated = $request->validate([
            'numero' => 'nullable|string|max:100',
            'fecha_documento' => 'nullable|date',
            'asunto' => 'nullable|string|max:500',
            'remitente' => 'nullable|string|max:255',
            'destinatario' => 'nullable|string|max:255',
            'referencia' => 'nullable|string|max:5000',
            'observaciones' => 'nullable|string',
            'folios' => 'required|integer|min:0',
            'archivos' => 'nullable|array',
            'archivos.*.nombre_archivo' => 'required_with:archivos.*.file|string|max:255',
            'archivos.*.file' => 'nullable|file|mimes:pdf|max:25600',
            'archivos_existentes' => 'nullable|array',
            'archivos_existentes.*.id' => 'required|exists:document_files,id',
            'archivos_existentes.*.nombre_archivo' => 'required|string|max:255',
        ], [], [
            'archivos.*.nombre_archivo' => 'nombre del archivo',
            'archivos.*.file' => 'archivo PDF',
        ]);

        $document->update([
            'numero' => $validated['numero'] ?? null,
            'fecha_documento' => $validated['fecha_documento'] ?? null,
            'asunto' => $validated['asunto'] ?? null,
            'remitente' => $validated['remitente'] ?? null,
            'destinatario' => $validated['destinatario'] ?? null,
            'referencia' => $validated['referencia'] ?? null,
            'observaciones' => $validated['observaciones'] ?? null,
            'folios' => (int) ($validated['folios'] ?? 0),
        ]);

        // Actualizar nombres de archivos existentes
        if (!empty($validated['archivos_existentes'])) {
            foreach ($validated['archivos_existentes'] as $item) {
                $docFile = DocumentFile::where('document_id', $document->id)->find($item['id']);
                if ($docFile) {
                    $docFile->update(['nombre_archivo' => $item['nombre_archivo']]);
                }
            }
        }

        // Añadir nuevos archivos
        $archivos = $request->input('archivos', []);
        $orden = $document->files()->max('orden') ?? -1;
        foreach ($archivos as $index => $item) {
            $file = $request->file("archivos.{$index}.file");
            if (!$file) {
                continue;
            }
            $orden++;
            $path = $file->store('expedientes/documentos', 'r2');
            DocumentFile::create([
                'document_id' => $document->id,
                'nombre_archivo' => $item['nombre_archivo'] ?? $file->getClientOriginalName(),
                'path' => $path,
                'orden' => $orden,
            ]);
        }

        return redirect()->route('folders.show', $document->folder_id)
            ->with('success', 'Documento actualizado correctamente.');
    }

    /**
     * Remove document and all its files.
     */
    public function destroy(Document $document)
    {
        if (request()->user()->role === 'Visualizador') {
            abort(403, 'No tienes permiso para eliminar documentos. Solo puedes ver.');
        }
        $document->load('folder');
        $folderId = $document->folder_id;
        if ($document->folder->module !== null) {
            return redirect()->back()->with('error', 'Este documento no pertenece a gestión documental.');
        }

        foreach ($document->files as $file) {
            Storage::disk(storage_disk_for_path($file->path))->delete($file->path);
        }
        $document->delete();

        return redirect()->route('folders.show', $folderId)
            ->with('success', 'Documento eliminado correctamente.');
    }

    /**
     * Soft delete a single file (solo marca eliminado, no borra de R2).
     */
    public function destroyFile(Document $document, DocumentFile $file)
    {
        if (request()->user()->role === 'Visualizador') {
            abort(403, 'No tienes permiso.');
        }
        if ($file->document_id !== $document->id) {
            abort(404);
        }
        $file->delete(); // Soft delete
        if (request()->header('X-Inertia')) {
            return back();
        }
        return redirect()->route('folders.show', $document->folder_id)->with('success', 'Archivo eliminado.');
    }

    /**
     * Mover documento a otra carpeta.
     */
    public function move(Request $request, Document $document)
    {
        if ($request->user()->role === 'Visualizador') {
            abort(403, 'No tienes permiso.');
        }
        $document->load('folder');
        if ($document->folder->module !== null) {
            return redirect()->back()->with('error', 'Este documento no es de gestión documental.');
        }
        $validated = $request->validate([
            'folder_id' => 'required|exists:folders,id',
        ]);
        $folder = Folder::findOrFail($validated['folder_id']);
        if ($folder->module !== null) {
            return redirect()->back()->with('error', 'La carpeta de destino debe ser de gestión documental.');
        }
        $document->update(['folder_id' => $folder->id]);
        if (request()->header('X-Inertia')) {
            return back();
        }
        return redirect()->route('folders.show', $document->folder_id)->with('success', 'Documento movido.');
    }

    /**
     * Mover varios documentos a otra carpeta (gestión documental).
     */
    public function moveBulk(Request $request)
    {
        if ($request->user()->role === 'Visualizador') {
            abort(403, 'No tienes permiso.');
        }
        $validated = $request->validate([
            'document_ids' => 'required|array',
            'document_ids.*' => 'required|integer|exists:documents,id',
            'folder_id' => 'required|exists:folders,id',
        ]);
        $folder = Folder::findOrFail($validated['folder_id']);
        if ($folder->module !== null) {
            return redirect()->back()->with('error', 'La carpeta de destino debe ser de gestión documental.');
        }
        $documents = Document::whereIn('id', $validated['document_ids'])->with('folder')->get();
        $moved = 0;
        foreach ($documents as $document) {
            if ($document->folder->module !== null) {
                continue;
            }
            $document->update(['folder_id' => $folder->id]);
            $moved++;
        }
        if (request()->header('X-Inertia')) {
            return back()->with('success', $moved > 0 ? "Se movieron {$moved} documento(s) correctamente." : 'No se movió ningún documento.');
        }
        return redirect()->back()->with('success', $moved > 0 ? "Se movieron {$moved} documento(s) correctamente." : 'No se movió ningún documento.');
    }

    /**
     * Download a single document file.
     */
    public function download(Document $document, DocumentFile $file)
    {
        if ($file->document_id !== $document->id) {
            abort(404);
        }
        $disk = storage_disk_for_path($file->path);
        if (!Storage::disk($disk)->exists($file->path)) {
            return redirect()->back()->with('error', 'El archivo no existe.');
        }
        $downloadName = \Str::slug($file->nombre_archivo) . '.pdf';
        return Storage::disk($disk)->download($file->path, $downloadName);
    }

    /**
     * View PDF in browser (for iframe).
     */
    public function view(Document $document, DocumentFile $file)
    {
        if ($file->document_id !== $document->id) {
            abort(404);
        }
        $disk = storage_disk_for_path($file->path);
        if (!Storage::disk($disk)->exists($file->path)) {
            abort(404);
        }
        return redirect(Storage::disk($disk)->url($file->path));
    }

    /**
     * Download selected or all document PDFs from the folder as a ZIP.
     * Files are named with the name from "Archivos PDF (nombre + archivo)" (nombre_archivo).
     */
    public function downloadZip(Request $request, Folder $folder)
    {
        if ($folder->module !== null) {
            return redirect()->back()->with('error', 'Esta carpeta no es de gestión documental.');
        }

        $ids = $request->input('ids', []);
        $all = $request->boolean('all');

        if ($all) {
            $documents = $folder->documents()->with('files')->get();
        } else {
            if (!is_array($ids) || empty($ids)) {
                return redirect()->back()->with('error', 'Seleccione al menos un documento.');
            }
            $documents = Document::where('folder_id', $folder->id)
                ->whereIn('id', $ids)
                ->with('files')
                ->get();
        }

        $filesToZip = [];
        foreach ($documents as $doc) {
            foreach ($doc->files as $file) {
                $disk = storage_disk_for_path($file->path);
                if (!Storage::disk($disk)->exists($file->path)) {
                    continue;
                }
                if ($disk === 'public') {
                    $filesToZip[] = [
                        'path' => Storage::disk('public')->path($file->path),
                        'nombre_archivo' => $file->nombre_archivo,
                    ];
                } else {
                    $content = Storage::disk($disk)->get($file->path);
                    $tmpPath = storage_path('app/documentos_temp_' . uniqid() . '.pdf');
                    file_put_contents($tmpPath, $content);
                    $filesToZip[] = [
                        'path' => $tmpPath,
                        'nombre_archivo' => $file->nombre_archivo,
                        'temp' => true,
                    ];
                }
            }
        }

        if (empty($filesToZip)) {
            return redirect()->back()->with('error', 'No hay archivos PDF para descargar.');
        }

        $usedBaseNames = [];
        $zip = new ZipArchive();
        $zipPath = storage_path('app/public/documentos_temp_' . uniqid() . '.zip');

        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'No se pudo crear el archivo ZIP.');
        }

        foreach ($filesToZip as $item) {
            $baseName = \Str::slug($item['nombre_archivo']);
            if (empty($baseName)) {
                $baseName = 'documento';
            }
            $ext = pathinfo($item['path'], PATHINFO_EXTENSION) ?: 'pdf';
            if (!isset($usedBaseNames[$baseName])) {
                $usedBaseNames[$baseName] = 0;
            }
            $usedBaseNames[$baseName]++;
            $fileName = $usedBaseNames[$baseName] === 1
                ? $baseName . '.' . $ext
                : $baseName . '-' . $usedBaseNames[$baseName] . '.' . $ext;
            $zip->addFile($item['path'], $fileName);
        }

        $zip->close();

        foreach ($filesToZip as $item) {
            if (!empty($item['temp']) && file_exists($item['path'])) {
                @unlink($item['path']);
            }
        }

        $downloadName = \Str::slug($folder->name) . '-documentos-' . now()->format('Y-m-d-His') . '.zip';

        $response = response()->download($zipPath, $downloadName, [
            'Content-Type' => 'application/zip',
        ])->deleteFileAfterSend(true);

        return $response;
    }

    /**
     * Parsea archivos desde el request (soporta archivos.0.nombre_archivo / archivos.0.file).
     */
    private function parseArchivosFromRequest(Request $request): array
    {
        $out = [];
        $input = $request->input('archivos');
        if (is_array($input)) {
            foreach ($input as $index => $item) {
                $nombre = is_array($item) ? ($item['nombre_archivo'] ?? '') : '';
                $file = $request->file("archivos.{$index}.file") ?? $request->file("archivos.{$index}");
                if ($nombre !== '' && $file) {
                    $out[$index] = ['nombre_archivo' => $nombre, 'file' => $file];
                }
            }
        }
        if (empty($out)) {
            for ($i = 0; $i < 20; $i++) {
                $nombre = $request->input("archivos.{$i}.nombre_archivo");
                $file = $request->file("archivos.{$i}.file") ?? $request->file("archivos.{$i}");
                if ($nombre !== null && $nombre !== '' && $file) {
                    $out[$i] = ['nombre_archivo' => $nombre, 'file' => $file];
                }
            }
        }
        return $out;
    }
}
