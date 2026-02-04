<?php

namespace App\Http\Controllers;

use App\Models\Inmobiliaria;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;

class InmobiliariaController extends Controller
{
    use HasRoleBasedAccess;

    const MODULE = 'inmobiliaria';

    public function index(Request $request)
    {
        $user = auth()->user();
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;
        if ($folderId) {
            $currentFolder = Folder::where('module', self::MODULE)->findOrFail($folderId);
            $currentFolder->load(['parent']);
            $folders = $currentFolder->children()->orderBy('name')->get();
            $breadcrumb = $currentFolder->path;
        } else {
            $currentFolder = null;
            $folders = Folder::whereNull('parent_id')->where('module', self::MODULE)->orderBy('name')->get();
            $breadcrumb = [];
        }

        $query = Inmobiliaria::query();
        $query = $this->applyRoleBasedFilter($query, $user);
        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('ubicacion', 'like', '%' . $request->search . '%');
            });
        }

        return Inertia::render('Inmobiliaria/Index', [
            'items' => $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id'])),
            'filters' => $request->only(['search', 'folder_id']),
            'userRole' => $user->role,
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'breadcrumb' => $breadcrumb,
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
        return Inertia::render('Inmobiliaria/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'precio' => 'nullable|numeric',
            'estado' => 'nullable|string',
            'imagen' => 'nullable|image|max:2048',
        ]);

        $data = $request->except(['imagen']);
        $data['user_id'] = auth()->id();
        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $data['folder_id'] = $folder->id;
            }
        }

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('inmobiliaria', 'public');
        }

        $item = Inmobiliaria::create($data);
        $folderId = $item->folder_id;
        return redirect()->route('inmobiliaria.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro creado.');
    }

    public function edit(Inmobiliaria $inmobiliaria)
    {
        $user = auth()->user();
        if (!$this->canEdit($inmobiliaria, $user)) {
            return redirect()->route('inmobiliaria.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('Inmobiliaria/Edit', [
            'item' => $inmobiliaria
        ]);
    }

    public function update(Request $request, Inmobiliaria $inmobiliaria)
    {
        $user = auth()->user();
        if (!$this->canEdit($inmobiliaria, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'precio' => 'nullable|numeric',
            'estado' => 'nullable|string',
            'imagen' => 'nullable|image|max:2048',
        ]);

        $data = $request->except(['imagen']);

        if ($request->hasFile('imagen')) {
            if ($inmobiliaria->imagen) {
                Storage::disk('public')->delete($inmobiliaria->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('inmobiliaria', 'public');
        }

        $inmobiliaria->update($data);

        $folderId = $inmobiliaria->folder_id;
        return redirect()->route('inmobiliaria.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro actualizado.');
    }

    public function destroy(Inmobiliaria $inmobiliaria)
    {
        $user = auth()->user();
        if (!$this->canDelete($inmobiliaria, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        if ($inmobiliaria->imagen) {
            Storage::disk('public')->delete($inmobiliaria->imagen);
        }
        $folderId = $inmobiliaria->folder_id;
        $inmobiliaria->delete();
        return redirect()->route('inmobiliaria.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro eliminado.');
    }
}
