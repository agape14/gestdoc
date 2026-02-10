<?php

namespace App\Http\Controllers;

use App\Models\Tecnologia;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;

class TecnologiaController extends Controller
{
    use HasRoleBasedAccess;

    const MODULE = 'tecnologia';

    public function index(Request $request)
    {
        $user = auth()->user();
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;
        $effectiveUserId = $user->role === 'Administrador'
            ? ($request->filled('user_id') ? (int) $request->user_id : null)
            : $user->id;

        if ($folderId) {
            $currentFolder = Folder::where('module', self::MODULE)
                ->forEffectiveUser($effectiveUserId)
                ->findOrFail($folderId);
            $currentFolder->load(['parent']);
            $folders = $currentFolder->children()->forEffectiveUser($effectiveUserId)->orderBy('name')->get();
            $breadcrumb = $currentFolder->path;
        } else {
            $currentFolder = null;
            $folders = Folder::whereNull('parent_id')
                ->visibleForUser(self::MODULE, $effectiveUserId)
                ->orderBy('name')
                ->get();
            $breadcrumb = [];
        }

        $query = Tecnologia::query();
        $query = $this->applyRoleBasedFilter($query, $user);
        if ($request->filled('user_id') && $user->role === 'Administrador') {
            $query->where('user_id', $request->user_id);
        }
        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
            });
        }

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return Inertia::render('Tecnologia/Index', [
            'items' => $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id'])),
            'filters' => $request->only(['search', 'folder_id', 'user_id']),
            'userRole' => $user->role,
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'breadcrumb' => $breadcrumb,
            'operadores' => $operadores,
        ]);
    }

    public function create(Request $request)
    {
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;
        $breadcrumbLabel = '';
        if ($folderId) {
            $folder = Folder::where('module', self::MODULE)->find($folderId);
            if ($folder) {
                $folder->load(['parent']);
                $path = $folder->path;
                $breadcrumbLabel = is_array($path) ? implode(' / ', array_column($path, 'name')) : $folder->name;
            }
        }
        return Inertia::render('Tecnologia/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['archivo']);
        $data['user_id'] = auth()->id();
        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $data['folder_id'] = $folder->id;
            }
        }

        if ($request->hasFile('archivo')) {
            $data['archivo'] = $request->file('archivo')->store('tecnologia', 'public');
        }

        $item = Tecnologia::create($data);
        $folderId = $item->folder_id;
        return redirect()->route('tecnologia.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro creado.');
    }

    public function edit(Tecnologia $tecnologia)
    {
        $user = auth()->user();
        if (!$this->canEdit($tecnologia, $user)) {
            return redirect()->route('tecnologia.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('Tecnologia/Edit', [
            'item' => $tecnologia
        ]);
    }

    public function update(Request $request, Tecnologia $tecnologia)
    {
        $user = auth()->user();
        if (!$this->canEdit($tecnologia, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['archivo']);

        if ($request->hasFile('archivo')) {
            if ($tecnologia->archivo) {
                Storage::disk('public')->delete($tecnologia->archivo);
            }
            $data['archivo'] = $request->file('archivo')->store('tecnologia', 'public');
        }

        $tecnologia->update($data);

        $folderId = $tecnologia->folder_id;
        return redirect()->route('tecnologia.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro actualizado.');
    }

    public function destroy(Tecnologia $tecnologia)
    {
        $user = auth()->user();
        if (!$this->canDelete($tecnologia, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        if ($tecnologia->archivo) {
            Storage::disk('public')->delete($tecnologia->archivo);
        }
        $folderId = $tecnologia->folder_id;
        $tecnologia->delete();
        return redirect()->route('tecnologia.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro eliminado.');
    }
}
