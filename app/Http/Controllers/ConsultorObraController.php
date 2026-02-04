<?php

namespace App\Http\Controllers;

use App\Models\ConsultorObra;
use App\Models\ConsultorObraDocumento;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ConsultorObrasExport;
use App\Traits\HasRoleBasedAccess;

class ConsultorObraController extends Controller
{
    use HasRoleBasedAccess;

    const MODULE = 'consultor-obras';

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

        $query = ConsultorObra::query()->active()->with('documentos');
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

        $consultoriasPaginated = $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id']));
        $consultorias = $query->latest()->get();
        $groupedByEspecialidad = $consultorias->groupBy('especialidad');

        $anulados = $user->role === 'Administrador'
            ? ConsultorObra::where('anulado', true)->when($folderId, fn($q) => $q->where('folder_id', $folderId))->when($request->filled('user_id'), fn($q) => $q->where('user_id', $request->user_id))->latest()->get()
            : collect();
        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return Inertia::render('ConsultorObras/Index', [
            'consultorias' => $consultoriasPaginated,
            'groupedByEspecialidad' => $groupedByEspecialidad,
            'filters' => $request->only(['search', 'tipo', 'especialidad', 'user_id', 'folder_id']),
            'userRole' => $user->role,
            'anulados' => $anulados,
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
        Folder::create($validated);
        return redirect()->back()->with('success', 'Carpeta creada.');
    }

    public function export(Request $request)
    {
        $query = ConsultorObra::query();

        if ($request->filled('tipo')) {
            $query->where('categoria', $request->tipo);
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        return Excel::download(new ConsultorObrasExport($query->get()), 'consultor-obras.xlsx');
    }

    public function exportProject(ConsultorObra $consultorObra)
    {
        return Excel::download(new ConsultorObrasExport(collect([$consultorObra])), "consultor-obra_{$consultorObra->id}.xlsx");
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
        return Inertia::render('ConsultorObras/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'required|string|max:255',
            'categoria' => 'required|string',
            'especialidad' => 'nullable|string|max:255',
            'tipo_servicio' => 'nullable|string|max:255',
            'presupuesto' => 'nullable|numeric',
            'estado' => 'nullable|string',
            'duracion' => 'nullable|string|max:255',
            'modalidad' => 'nullable|string|max:255',
            'clasificacion' => 'nullable|string|max:500',
        ]);

        $data = $request->except(['contrato_archivo', 'tdr_archivo', 'personal_clave', 'producto_tecnico', 'actas_resoluciones', 'conformidad_tecnica', 'documentos']);
        $data['user_id'] = auth()->id();
        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $data['folder_id'] = $folder->id;
            }
        }

        $consultorObra = ConsultorObra::create($data);
        $this->storeDocumentos($request, $consultorObra);

        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : ($consultorObra->folder_id ?? null);
        $query = $folderId ? ['folder_id' => $folderId] : [];
        return redirect()->route('consultor-obras.index', $query)->with('success', 'Registro creado.');
    }

    private function storeDocumentos(Request $request, ConsultorObra $consultorObra): void
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
            $path = $file->store('consultor_obras/documentos', 'public');
            ConsultorObraDocumento::create([
                'consultor_obra_id' => $consultorObra->id,
                'nombre' => $nombre ?: $file->getClientOriginalName(),
                'file_path' => $path,
            ]);
        }
    }

    public function edit(ConsultorObra $consultorObra)
    {
        $user = auth()->user();
        if (!$this->canEdit($consultorObra, $user)) {
            return redirect()->route('consultor-obras.index')->with('error', 'No tienes permiso para editar este registro.');
        }
        $consultorObra->load('documentos');
        return Inertia::render('ConsultorObras/Edit', [
            'consultorObra' => $consultorObra,
        ]);
    }

    public function update(Request $request, ConsultorObra $consultor_obra)
    {
        $user = auth()->user();
        if (!$this->canEdit($consultor_obra, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'documento_delete_ids' => 'nullable|array',
            'documento_delete_ids.*' => 'integer|exists:consultor_obra_documentos,id',
        ]);
        $data = $request->except(['contrato_archivo', 'tdr_archivo', 'personal_clave', 'producto_tecnico', 'actas_resoluciones', 'conformidad_tecnica', 'documentos', 'documento_delete_ids']);

        // Helper to store file
        $handleFile = function($field, $path) use ($request, &$data, $consultor_obra) {
            if ($request->hasFile($field)) {
                if ($consultor_obra->$field) {
                    Storage::disk('public')->delete($consultor_obra->$field);
                }
                $data[$field] = $request->file($field)->store($path, 'public');
            }
        };

        $handleFile('contrato_archivo', 'consultor_obras/contratos');
        $handleFile('tdr_archivo', 'consultor_obras/tdrs');
        $handleFile('personal_clave', 'consultor_obras/personal');
        $handleFile('actas_resoluciones', 'consultor_obras/actas');
        $handleFile('conformidad_tecnica', 'consultor_obras/conformidades');

        // Handle multiple files for producto_tecnico
        if ($request->hasFile('producto_tecnico')) {
            $paths = [];
            foreach($request->file('producto_tecnico') as $file) {
                $paths[] = $file->store('consultor_obras/productos', 'public');
            }
            $existing = is_array($consultor_obra->producto_tecnico) ? $consultor_obra->producto_tecnico : [];
            $data['producto_tecnico'] = array_merge($existing, $paths);
        }

        $consultor_obra->update($data);
        $this->syncDocumentosUpdate($request, $consultor_obra);

        return redirect()->back()->with('success', 'Registro actualizado.');
    }

    private function syncDocumentosUpdate(Request $request, ConsultorObra $consultorObra): void
    {
        $deleteIds = $request->input('documento_delete_ids', []);
        if (is_array($deleteIds)) {
            $docs = ConsultorObraDocumento::where('consultor_obra_id', $consultorObra->id)->whereIn('id', $deleteIds)->get();
            foreach ($docs as $doc) {
                Storage::disk('public')->delete($doc->file_path);
                $doc->delete();
            }
        }
        $documentos = $request->input('documentos', []);
        if (!is_array($documentos)) {
            return;
        }
        foreach ($documentos as $index => $doc) {
            if (!$request->hasFile("documentos.{$index}.archivo")) {
                continue;
            }
            $nombre = is_array($doc) ? ($doc['nombre'] ?? '') : '';
            $file = $request->file("documentos.{$index}.archivo");
            $path = $file->store('consultor_obras/documentos', 'public');
            ConsultorObraDocumento::create([
                'consultor_obra_id' => $consultorObra->id,
                'nombre' => $nombre ?: $file->getClientOriginalName(),
                'file_path' => $path,
            ]);
        }
    }

    public function destroy(ConsultorObra $consultor_obra)
    {
        $user = auth()->user();
        if (!$this->canDelete($consultor_obra, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para anular este registro.');
        }

        $consultor_obra->update(['anulado' => true]);
        return redirect()->route('consultor-obras.index')->with('success', 'Registro anulado.');
    }
}
