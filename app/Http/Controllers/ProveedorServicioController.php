<?php

namespace App\Http\Controllers;

use App\Models\ProveedorServicio;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProveedorServiciosExport;

class ProveedorServicioController extends Controller
{
    use HasRoleBasedAccess;

    const MODULE = 'proveedor-servicios';

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

        $query = ProveedorServicio::query()->active()->with('documentos');
        $query = $this->applyRoleBasedFilterWithShared($query, $user, ProveedorServicio::class);
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
                  ->orWhere('entidad', 'like', '%' . $request->search . '%')
                  ->orWhere('especialidad', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('tipo')) {
            $query->where('categoria', $request->tipo);
        }
        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        $serviciosPaginated = $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id']));
        $servicios = $query->latest()->get();
        $groupedByEspecialidad = $servicios->groupBy('especialidad');
        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        $sharedReadOnlyIds = $this->sharedReadOnlyIds($user, ProveedorServicio::class);

        return Inertia::render('ProveedorServicios/Index', [
            'servicios' => $serviciosPaginated,
            'groupedByEspecialidad' => $groupedByEspecialidad,
            'filters' => $request->only(['search', 'tipo', 'especialidad', 'user_id', 'folder_id']),
            'userRole' => $user->role,
            'operadores' => $operadores,
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'breadcrumb' => $breadcrumb,
            'sharedReadOnlyIds' => $sharedReadOnlyIds,
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

    public function export(Request $request)
    {
        $user = auth()->user();
        $query = ProveedorServicio::query();
        $query = $this->applyRoleBasedFilter($query, $user);

        if ($request->filled('tipo')) {
            $query->where('categoria', $request->tipo);
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        return Excel::download(new ProveedorServiciosExport($query->get()), 'proveedor-servicios.xlsx');
    }

    public function exportProject(ProveedorServicio $proveedorServicio)
    {
        return Excel::download(new ProveedorServiciosExport(collect([$proveedorServicio])), "proveedor-servicio_{$proveedorServicio->id}.xlsx");
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
        return Inertia::render('ProveedorServicios/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'required|string|max:255',
            'clasificacion' => 'nullable|string|max:500',
            'especialidad' => 'nullable|string|max:255',
            'tipo_servicio' => 'nullable|string|max:255',
            'presupuesto' => 'nullable|numeric',
            'estado' => 'nullable|string',
            'modalidad' => 'nullable|string|max:255',
            'duracion' => 'nullable|string|max:255',
        ]);

        $data = $request->except(['contrato_archivo', 'tdr_archivo', 'informes_tecnicos', 'actas_resoluciones', 'conformidad_tecnica', 'panel_fotografico']);
        $cargosInput = $request->input('cargos');
        if (is_string($cargosInput)) {
            $decoded = json_decode($cargosInput, true);
            if (is_array($decoded)) {
                $data['cargos'] = $decoded;
            }
        } elseif (is_array($cargosInput)) {
            $data['cargos'] = $cargosInput;
        }
        $data['user_id'] = auth()->id();
        $data['plantel_tecnico_aplica'] = $request->has('plantel_tecnico_aplica');
        $data['valorizaciones_aplica'] = $request->has('valorizaciones_aplica');
        $data['liquidacion_aplica'] = $request->has('liquidacion_aplica');

        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $folder->load(['parent']);
                $path = $folder->path;
                $data['folder_id'] = $folder->id;
                $pathLabel = is_array($path) ? implode(' / ', array_column($path, 'name')) : $folder->name;
                $data['clasificacion'] = $request->filled('clasificacion') ? $request->clasificacion : $pathLabel;
                $data['categoria'] = (stripos($pathLabel, 'PRIVAD') !== false) ? 'Privada' : 'Publica';
            }
        } else {
            $data['categoria'] = $data['categoria'] ?? 'Publica';
        }

        $servicio = ProveedorServicio::create($data);
        $folderId = $servicio->folder_id;

        return redirect()->route('proveedor-servicios.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro creado.');
    }

    public function edit(ProveedorServicio $proveedor_servicio)
    {
        $user = auth()->user();
        if (!$this->canEdit($proveedor_servicio, $user)) {
            return redirect()->route('proveedor-servicios.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('ProveedorServicios/Edit', [
            'servicio' => $proveedor_servicio
        ]);
    }

    public function update(Request $request, ProveedorServicio $proveedor_servicio)
    {
        $user = auth()->user();
        if (!$this->canEdit($proveedor_servicio, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $data = $request->except(['contrato_archivo', 'tdr_archivo', 'informes_tecnicos', 'actas_resoluciones', 'conformidad_tecnica', 'panel_fotografico']);
        $cargosInput = $request->input('cargos');
        if (is_string($cargosInput)) {
            $decoded = json_decode($cargosInput, true);
            if (is_array($decoded)) {
                $data['cargos'] = $decoded;
            }
        } elseif (is_array($cargosInput)) {
            $data['cargos'] = $cargosInput;
        }

        $data['plantel_tecnico_aplica'] = $request->has('plantel_tecnico_aplica');
        $data['valorizaciones_aplica'] = $request->has('valorizaciones_aplica');
        $data['liquidacion_aplica'] = $request->has('liquidacion_aplica');

        $handleFile = function($field, $path) use ($request, &$data, $proveedor_servicio) {
            if ($request->hasFile($field)) {
                if ($proveedor_servicio->$field) {
                    Storage::disk(storage_disk_for_path($proveedor_servicio->$field))->delete($proveedor_servicio->$field);
                }
                $data[$field] = $request->file($field)->store('expedientes/' . $path, 'r2');
            }
        };

        $handleFile('contrato_archivo', 'proveedor_servicios/contratos');
        $handleFile('tdr_archivo', 'proveedor_servicios/tdrs');
        $handleFile('actas_resoluciones', 'proveedor_servicios/actas');
        $handleFile('conformidad_tecnica', 'proveedor_servicios/conformidades');
        $handleFile('panel_fotografico', 'proveedor_servicios/paneles');

        if ($request->hasFile('informes_tecnicos')) {
            $paths = [];
            foreach($request->file('informes_tecnicos') as $file) {
                $paths[] = $file->store('expedientes/proveedor_servicios/informes', 'r2');
            }
            $existing = is_array($proveedor_servicio->informes_tecnicos) ? $proveedor_servicio->informes_tecnicos : [];
            $data['informes_tecnicos'] = array_merge($existing, $paths);
        }

        $proveedor_servicio->update($data);

        return redirect()->back()->with('success', 'Registro actualizado.');
    }

    public function destroy(ProveedorServicio $proveedor_servicio)
    {
        $user = auth()->user();
        if (!$this->canDelete($proveedor_servicio, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        $proveedor_servicio->delete();
        return redirect()->route('proveedor-servicios.index')->with('success', 'Registro eliminado.');
    }
}
