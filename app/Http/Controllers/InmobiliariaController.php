<?php

namespace App\Http\Controllers;

use App\Models\Inmobiliaria;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\InmobiliariaExport;
use App\Traits\HasRoleBasedAccess;
use App\Traits\MovesToFolder;
use App\Support\GridPagination;

class InmobiliariaController extends Controller
{
    use HasRoleBasedAccess, MovesToFolder;

    const MODULE = 'inmobiliaria';

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

        $query = Inmobiliaria::query()->activo();
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
                  ->orWhere('ubicacion', 'like', '%' . $request->search . '%');
            });
        }

        $sortable = ['titulo', 'ubicacion', 'precio', 'estado', 'created_at'];
        $sort = (string) $request->input('sort', 'created_at');
        if (!in_array($sort, $sortable, true)) {
            $sort = 'created_at';
        }
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction)->orderBy('id', 'desc');

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        $anulados = $user->role === 'Administrador'
            ? Inmobiliaria::where('anulado', true)
                ->when($folderId, fn ($q) => $q->where('folder_id', $folderId))
                ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
                ->latest()->get()
            : collect();

        return Inertia::render('Inmobiliaria/Index', [
            'items' => GridPagination::paginate(clone $query, $request),
            'filters' => array_merge(
                $request->only(['search', 'folder_id', 'user_id']),
                ['sort' => $sort, 'direction' => $direction, 'per_page' => GridPagination::perPageFilterValue($request)]
            ),
            'userRole' => $user->role,
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'breadcrumb' => $breadcrumb,
            'operadores' => $operadores,
            'anulados' => $anulados,
        ]);
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;
        $query = Inmobiliaria::query()->activo();
        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }
        $query = $this->applyExportRoleFilter($query, $user, $request);
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                    ->orWhere('ubicacion', 'like', '%' . $request->search . '%');
            });
        }
        $rows = $query->orderBy('id')->get();
        $filename = 'inmobiliaria_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new InmobiliariaExport($rows), $filename);
    }

    public function storeFolder(Request $request)
    {
        $user = $request->user();
        if ($user->role === 'Visualizador') {
            abort(403, 'No tienes permiso para crear carpetas.');
        }
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:folders,id',
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);
        $validated['module'] = self::MODULE;
        $validated['user_id'] = $user->id;
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
            $data['imagen'] = $request->file('imagen')->store('expedientes/inmobiliaria', 'r2');
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
                Storage::disk(storage_disk_for_path($inmobiliaria->imagen))->delete($inmobiliaria->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('expedientes/inmobiliaria', 'r2');
        }

        $inmobiliaria->update($data);

        $folderId = $inmobiliaria->folder_id;
        return redirect()->route('inmobiliaria.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro actualizado.');
    }

    public function destroy(Request $request)
    {
        $id = (int) ($request->route('inmobiliarium') ?? $request->segment(2));
        if ($id < 1) {
            return redirect()->back()->with('error', 'Registro no válido.');
        }
        $registro = Inmobiliaria::find($id);
        if (!$registro) {
            return redirect()->back()->with('error', 'Registro no encontrado.');
        }
        $user = auth()->user();
        if (!$this->canDelete($registro, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para anular este registro.');
        }
        DB::table('inmobiliarias')->where('id', $id)->update(['anulado' => 1, 'updated_at' => now()]);
        return redirect()->back()->with('success', 'Registro anulado.');
    }

    public function move(Request $request, Inmobiliaria $inmobiliaria)
    {
        return $this->moveItem($request, $inmobiliaria, self::MODULE, 'inmobiliaria.index');
    }

    public function moveBulk(Request $request)
    {
        return $this->moveBulkItems($request, Inmobiliaria::class, self::MODULE, 'item_ids', 'inmobiliaria.index');
    }
}
