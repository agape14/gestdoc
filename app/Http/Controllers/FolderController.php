<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Document;
use Illuminate\Http\Request;
use Inertia\Inertia;

class FolderController extends Controller
{
    /**
     * Gestión Documental: solo carpetas con module = null (tipos de documento: Cartas, Oficios, Memos).
     */
    private function scopeGestionDocumental($query)
    {
        return $query->whereNull('module');
    }

    /**
     * Listado de carpetas (tipos de documento). Sin jerarquía: solo carpetas raíz.
     */
    public function index()
    {
        $folders = Folder::whereNull('parent_id')
            ->whereNull('module')
            ->withCount('documents')
            ->orderBy('name')
            ->get();

        $recentDocuments = Document::with(['folder', 'files'])
            ->whereHas('folder', fn ($q) => $q->whereNull('module'))
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($doc) {
                return [
                    'id' => $doc->id,
                    'numero' => $doc->numero,
                    'asunto' => $doc->asunto,
                    'fecha_documento' => $doc->fecha_documento?->format('Y-m-d'),
                    'folder_name' => $doc->folder?->name,
                    'created_at' => $doc->created_at,
                ];
            });

        return Inertia::render('Folders/Index', [
            'folders' => $folders,
            'currentFolder' => null,
            'breadcrumb' => [],
            'documents' => [],
            'recentDocuments' => $recentDocuments,
            'filters' => [],
        ]);
    }

    /**
     * Contenido de una carpeta (tipo de documento): listado de documentos.
     */
    public function show(Request $request, $id)
    {
        $folder = Folder::with(['parent'])
            ->whereNull('module')
            ->withCount('documents')
            ->findOrFail($id);

        $query = $folder->documents()->with('files');

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', '%' . $search . '%')
                    ->orWhere('asunto', 'like', '%' . $search . '%')
                    ->orWhere('remitente', 'like', '%' . $search . '%')
                    ->orWhere('destinatario', 'like', '%' . $search . '%')
                    ->orWhere('referencia', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('date_start')) {
            $query->whereDate('fecha_documento', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('fecha_documento', '<=', $request->date_end);
        }

        $documents = $query->latest('fecha_documento')->latest()->get();

        return Inertia::render('Folders/Index', [
            'folders' => Folder::whereNull('parent_id')->whereNull('module')->withCount('documents')->orderBy('name')->get(),
            'currentFolder' => $folder,
            'breadcrumb' => $folder->path,
            'documents' => $documents,
            'recentDocuments' => [],
            'filters' => $request->only(['search', 'date_start', 'date_end']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:folders,id',
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);
        $validated['module'] = null;
        $validated['parent_id'] = $validated['parent_id'] ?? null; // Solo carpetas raíz como tipos de documento

        Folder::create($validated);

        return redirect()->back()->with('success', 'Carpeta (tipo de documento) creada exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Folder $folder)
    {
        if ($folder->is_system) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'nullable|string|max:7',
                'description' => 'nullable|string|max:500',
            ]);
        } else {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'nullable|string|max:7',
                'icon' => 'nullable|string|max:50',
                'description' => 'nullable|string|max:500',
            ]);
        }

        $folder->update($validated);

        return redirect()->back()->with('success', 'Carpeta actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Folder $folder)
    {
        if ($folder->is_system) {
            return redirect()->back()->with('error', 'No se pueden eliminar carpetas del sistema.');
        }

        $folder->delete();

        return redirect()->back()->with('success', 'Carpeta eliminada exitosamente.');
    }

    /**
     * Obtiene las carpetas para el selector (gestión documental).
     */
    public function getTree()
    {
        $folders = Folder::whereNull('parent_id')
            ->whereNull('module')
            ->orderBy('name')
            ->get();

        return response()->json($folders);
    }
}
