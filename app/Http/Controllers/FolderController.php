<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;

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
     * Operador: solo sus carpetas. Administrador: todas o filtradas por user_id.
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        $query = Folder::whereNull('parent_id')->whereNull('module');

        if ($user->role === 'Administrador' && $request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        } else {
            $query->visibleForGestionDocumental($user);
        }

        $folders = $query->withCount('documents')->orderBy('name')->get();

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return Inertia::render('Folders/Index', [
            'folders' => $folders,
            'subfolders' => [],
            'currentFolder' => null,
            'breadcrumb' => [],
            'documents' => [],
            'operadores' => $operadores,
            'filters' => $request->only(['user_id']),
        ]);
    }

    /**
     * Contenido de una carpeta (tipo de documento): listado de documentos.
     */
    public function show(Request $request, $id)
    {
        $user = Auth::user();
        $folder = Folder::with(['parent'])
            ->whereNull('module')
            ->visibleForGestionDocumental($user)
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

        $foldersQuery = Folder::whereNull('parent_id')->whereNull('module');
        if ($user->role === 'Administrador' && $request->filled('user_id')) {
            $foldersQuery->where('user_id', $request->user_id);
        } else {
            $foldersQuery->visibleForGestionDocumental($user);
        }
        $foldersList = $foldersQuery->withCount('documents')->orderBy('name')->get();

        $subfoldersQuery = $folder->children()->whereNull('module');
        if ($user->role === 'Administrador' && $request->filled('user_id')) {
            $subfoldersQuery->where('user_id', $request->user_id);
        } else {
            $subfoldersQuery->visibleForGestionDocumental($user);
        }
        $subfolders = $subfoldersQuery->withCount('documents')->orderBy('name')->get();

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return Inertia::render('Folders/Index', [
            'folders' => $foldersList,
            'subfolders' => $subfolders,
            'currentFolder' => $folder,
            'breadcrumb' => $folder->path,
            'documents' => $documents,
            'operadores' => $operadores,
            'filters' => $request->only(['search', 'date_start', 'date_end', 'user_id']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        if ($request->user()->role === 'Visualizador') {
            abort(403, 'No tienes permiso para crear carpetas. Solo puedes ver.');
        }
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:folders,id',
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);
        $validated['module'] = null;
        $validated['parent_id'] = $validated['parent_id'] ?? null; // null = raíz; si se crea dentro de una carpeta, parent_id viene en la petición
        $validated['user_id'] = $request->user()->id;

        Folder::create($validated);

        return redirect()->back()->with('success', 'Carpeta (tipo de documento) creada exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     * Administrador puede editar cualquier carpeta; Operador solo las propias.
     */
    public function update(Request $request, Folder $folder)
    {
        $user = $request->user();
        if ($user->role === 'Visualizador') {
            abort(403, 'No tienes permiso para editar carpetas. Solo puedes ver.');
        }
        if ($user->role === 'Administrador') {
            // ok
        } elseif ($user->role === 'Operador') {
            if ($folder->user_id !== $user->id) {
                abort(403, 'Solo puedes editar tus propias carpetas.');
            }
        } else {
            abort(403, 'No tienes permiso para editar esta carpeta.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:9',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

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
        $user = request()->user();
        if ($user->role === 'Visualizador') {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar carpetas. Solo puedes ver.');
        }
        if ($user->role === 'Administrador') {
            // ok
        } elseif ($user->role === 'Operador') {
            if ($folder->user_id !== $user->id) {
                return redirect()->back()->with('error', 'Solo puedes eliminar tus propias carpetas.');
            }
        } else {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar carpetas.');
        }

        $folder->delete();

        return redirect()->back()->with('success', 'Carpeta eliminada exitosamente.');
    }

    /**
     * Obtiene las carpetas para el selector (gestión documental).
     */
    public function getTree()
    {
        $user = Auth::user();
        $folders = Folder::whereNull('parent_id')
            ->whereNull('module')
            ->visibleForGestionDocumental($user)
            ->orderBy('name')
            ->get();

        return response()->json($folders);
    }
}
