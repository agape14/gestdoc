<?php

namespace App\Http\Controllers;

use App\Models\ProveedorBien;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Traits\HasRoleBasedAccess;

class ProveedorBienController extends Controller
{
    use HasRoleBasedAccess;

    const MODULE = 'proveedor-bienes';

    public function index(Request $request)
    {
        $user = auth()->user();
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;
        $effectiveUserId = $user->role === 'Administrador'
            ? ($request->filled('user_id') ? (int) $request->user_id : null)
            : $user->id;

        if ($folderId) {
            $currentFolder = Folder::visibleForModuleUser(self::MODULE, $user)->findOrFail($folderId);
            $currentFolder->load(['parent']);
            $folders = $currentFolder->children()->visibleForModuleUser(self::MODULE, $user)->orderBy('name')->get();
            $breadcrumb = $currentFolder->path;
        } else {
            $currentFolder = null;
            $folders = Folder::whereNull('parent_id')
                ->visibleForModuleUser(self::MODULE, $user)
                ->orderBy('name')
                ->get();
            $breadcrumb = [];
        }

        $query = ProveedorBien::query()->active()->with('documentos');
        $query = $this->applyRoleBasedFilter($query, $user);
        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }
        if ($request->filled('user_id') && $user->role === 'Administrador') {
            $query->where('user_id', $request->user_id);
        }
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('entidad', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('tipo')) {
            $query->where('categoria', $request->tipo);
        }

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return Inertia::render('ProveedorBienes/Index', [
            'bienes' => $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id'])),
            'filters' => $request->only(['search', 'tipo', 'user_id', 'folder_id']),
            'userRole' => $user->role,
            'operadores' => $operadores,
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    public function storeFolder(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:folders,id',
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);
        $validated['module'] = self::MODULE;
        $validated['user_id'] = auth()->id();
        Folder::create($validated);
        return redirect()->back()->with('success', 'Carpeta creada.');
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
        return Inertia::render('ProveedorBienes/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'nullable|string|max:255',
            'estado' => 'nullable|string',
            'costo' => 'nullable|numeric',
            'clasificacion' => 'nullable|string|max:500',
        ]);

        $data = $validated;
        $data['user_id'] = auth()->id();

        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $folder->load(['parent']);
                $path = $folder->path;
                $pathLabel = is_array($path) ? implode(' / ', array_column($path, 'name')) : $folder->name;
                $data['folder_id'] = $folder->id;
                $data['clasificacion'] = $request->filled('clasificacion') ? $request->clasificacion : $pathLabel;
                $data['categoria'] = (stripos($pathLabel, 'PRIVAD') !== false) ? 'Privada' : 'Publica';
            }
        } else {
            $data['categoria'] = $data['categoria'] ?? 'Publica';
        }

        $bien = ProveedorBien::create($data);
        $folderId = $bien->folder_id;

        return redirect()->route('proveedor-bienes.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro creado.');
    }

    public function edit(ProveedorBien $proveedor_bien)
    {
        $user = auth()->user();
        if (!$this->canEdit($proveedor_bien, $user)) {
            return redirect()->route('proveedor-bienes.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('ProveedorBienes/Edit', [
            'bien' => $proveedor_bien
        ]);
    }

    public function update(Request $request, ProveedorBien $proveedor_bien)
    {
        $user = auth()->user();
        if (!$this->canEdit($proveedor_bien, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'nullable|string|max:255',
            'estado' => 'nullable|string',
            'costo' => 'nullable|numeric',
        ]);

        $proveedor_bien->update(array_merge($validated, [
            'categoria' => $request->input('categoria', $proveedor_bien->categoria ?? 'Privada'),
        ]));

        return redirect()->route('proveedor-bienes.index')->with('success', 'Registro actualizado.');
    }

    public function destroy(ProveedorBien $proveedor_bien)
    {
        $user = auth()->user();
        if (!$this->canDelete($proveedor_bien, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        $proveedor_bien->delete();
        return redirect()->route('proveedor-bienes.index')->with('success', 'Registro eliminado.');
    }
}
