<?php

namespace App\Http\Controllers;

use App\Models\EjecutorObra;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EjecutorObrasExport;

class EjecutorObraController extends Controller
{
    use HasRoleBasedAccess;

    const MODULE = 'ejecutor-obra';

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

        $query = EjecutorObra::query()->active()->with('documentos');
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

        $obrasPaginated = $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id']));
        $obras = $query->latest()->get();
        $groupedByEspecialidad = $obras->groupBy('especialidad');
        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return Inertia::render('EjecutorObra/Index', [
            'obras' => $obrasPaginated,
            'groupedByEspecialidad' => $groupedByEspecialidad,
            'filters' => $request->only(['search', 'tipo', 'especialidad', 'user_id', 'folder_id']),
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

    public function export(Request $request)
    {
        $user = auth()->user();
        $query = EjecutorObra::query();
        $query = $this->applyRoleBasedFilter($query, $user);

        if ($request->filled('tipo')) {
            $query->where('categoria', $request->tipo);
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        return Excel::download(new EjecutorObrasExport($query->get()), 'ejecutor-obras.xlsx');
    }

    public function exportProject(EjecutorObra $ejecutorObra)
    {
        return Excel::download(new EjecutorObrasExport(collect([$ejecutorObra])), "ejecutor-obra_{$ejecutorObra->id}.xlsx");
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
        return Inertia::render('EjecutorObra/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'required|string|max:255',
            'categoria' => 'required|string|in:Publica,Privada',
            'especialidad' => 'nullable|string|max:255',
            'tipo_obra' => 'nullable|string|max:255',
            'presupuesto' => 'nullable|numeric',
            'estado' => 'nullable|string',
            'modalidad' => 'nullable|string|max:255',
            'clasificacion' => 'nullable|string|max:500',
        ]);

        $data = $request->except(['contrato_archivo', 'tdr_archivo', 'valorizaciones', 'informes_tecnicos', 'expediente_tecnico', 'actas_resoluciones', 'conformidad_tecnica', 'panel_fotografico', 'liquidacion', 'documentos']);
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
        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $data['folder_id'] = $folder->id;
            }
        }

        $obra = EjecutorObra::create($data);
        $this->storeDocumentos($request, $obra);

        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : ($obra->folder_id ?? null);
        return redirect()->route('ejecutor-obra.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro creado.');
    }

    private function storeDocumentos(Request $request, EjecutorObra $obra): void
    {
        $documentos = $request->input('documentos', []);
        if (!is_array($documentos)) {
            return;
        }
        foreach ($documentos as $index => $doc) {
            $nombre = is_array($doc) ? ($doc['nombre'] ?? '') : '';
            if ($nombre === '' && !$request->hasFile("documentos.{$index}.archivo")) {
                continue;
            }
            if (!$request->hasFile("documentos.{$index}.archivo")) {
                continue;
            }
            $file = $request->file("documentos.{$index}.archivo");
            $path = $file->store('expedientes/ejecutor_obras/documentos', 'r2');
            \App\Models\EjecutorObraDocumento::create([
                'ejecutor_obra_id' => $obra->id,
                'nombre' => $nombre ?: $file->getClientOriginalName(),
                'file_path' => $path,
            ]);
        }
    }

    public function edit(EjecutorObra $ejecutor_obra)
    {
        $user = auth()->user();
        if (!$this->canEdit($ejecutor_obra, $user)) {
            return redirect()->route('ejecutor-obra.index')->with('error', 'No tienes permiso para editar este registro.');
        }
        $ejecutor_obra->load('documentos');
        return Inertia::render('EjecutorObra/Edit', [
            'obra' => $ejecutor_obra
        ]);
    }

    public function update(Request $request, EjecutorObra $ejecutor_obra)
    {
        $user = auth()->user();
        if (!$this->canEdit($ejecutor_obra, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $data = $request->except(['contrato_archivo', 'tdr_archivo', 'valorizaciones', 'informes_tecnicos', 'expediente_tecnico', 'actas_resoluciones', 'conformidad_tecnica', 'panel_fotografico', 'liquidacion']);
        $cargosInput = $request->input('cargos');
        if (is_string($cargosInput)) {
            $decoded = json_decode($cargosInput, true);
            if (is_array($decoded)) {
                $data['cargos'] = $decoded;
            }
        } elseif (is_array($cargosInput)) {
            $data['cargos'] = $cargosInput;
        }

        $handleFile = function($field, $path) use ($request, &$data, $ejecutor_obra) {
            if ($request->hasFile($field)) {
                if ($ejecutor_obra->$field) {
                    Storage::disk(storage_disk_for_path($ejecutor_obra->$field))->delete($ejecutor_obra->$field);
                }
                $data[$field] = $request->file($field)->store('expedientes/' . $path, 'r2');
            }
        };

        $handleFile('contrato_archivo', 'ejecutor_obras/contratos');
        $handleFile('tdr_archivo', 'ejecutor_obras/tdrs');
        $handleFile('expediente_tecnico', 'ejecutor_obras/expedientes');
        $handleFile('actas_resoluciones', 'ejecutor_obras/actas');
        $handleFile('conformidad_tecnica', 'ejecutor_obras/conformidades');
        $handleFile('panel_fotografico', 'ejecutor_obras/paneles');
        $handleFile('liquidacion', 'ejecutor_obras/liquidaciones');

        if ($request->hasFile('valorizaciones')) {
            $paths = [];
            foreach($request->file('valorizaciones') as $file) {
                $paths[] = $file->store('expedientes/ejecutor_obras/valorizaciones', 'r2');
            }
            $existing = is_array($ejecutor_obra->valorizaciones) ? $ejecutor_obra->valorizaciones : [];
            $data['valorizaciones'] = array_merge($existing, $paths);
        }

        if ($request->hasFile('informes_tecnicos')) {
            $paths = [];
            foreach($request->file('informes_tecnicos') as $file) {
                $paths[] = $file->store('expedientes/ejecutor_obras/informes', 'r2');
            }
            $existing = is_array($ejecutor_obra->informes_tecnicos) ? $ejecutor_obra->informes_tecnicos : [];
            $data['informes_tecnicos'] = array_merge($existing, $paths);
        }

        $ejecutor_obra->update($data);

        return redirect()->back()->with('success', 'Registro actualizado.');
    }

    public function destroy(EjecutorObra $ejecutor_obra)
    {
        $user = auth()->user();
        if (!$this->canDelete($ejecutor_obra, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para anular este registro.');
        }
        $ejecutor_obra->update(['anulado' => true]);
        return redirect()->route('ejecutor-obra.index')->with('success', 'Registro anulado.');
    }
}
