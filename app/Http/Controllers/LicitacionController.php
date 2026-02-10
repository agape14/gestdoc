<?php

namespace App\Http\Controllers;

use App\Models\Licitacion;
use App\Models\LicitacionDocumento;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LicitacionesExport;
use App\Traits\HasRoleBasedAccess;

class LicitacionController extends Controller
{
    use HasRoleBasedAccess;

    const MODULE = 'licitaciones';

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
                ->with(['children' => fn($q) => $q->forEffectiveUser($effectiveUserId)->withCount('children')])
                ->orderBy('name')
                ->get();
            $breadcrumb = [];
        }

        $query = Licitacion::query()->active()->with('documentos');
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

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        $licitacionesPaginated = $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id']));
        $licitaciones = $query->latest()->get();
        $groupedByEspecialidad = $licitaciones->groupBy('especialidad');

        $anulados = collect();
        if ($user->role === 'Administrador') {
            $anuladosQuery = Licitacion::where('anulado', true);
            if ($folderId) {
                $anuladosQuery->where('folder_id', $folderId);
            } else {
                $anuladosQuery->whereNull('folder_id');
            }
            if ($request->filled('user_id')) {
                $anuladosQuery->where('user_id', $request->user_id);
            }
            $anulados = $anuladosQuery->latest()->get();
        }

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return Inertia::render('Licitaciones/Index', [
            'licitaciones' => $licitacionesPaginated,
            'groupedByEspecialidad' => $groupedByEspecialidad,
            'filters' => $request->only(['search', 'date_start', 'date_end', 'tipo', 'especialidad', 'user_id', 'folder_id']),
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
        $validated['user_id'] = auth()->id();
        Folder::create($validated);
        return redirect()->back()->with('success', 'Carpeta creada.');
    }

    public function export(Request $request)
    {
        $query = Licitacion::query();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        return Excel::download(new LicitacionesExport($query->get()), 'licitaciones.xlsx');
    }

    public function exportProject(Licitacion $licitacion)
    {
        return Excel::download(new LicitacionesExport(collect([$licitacion])), "licitacion_{$licitacion->id}.xlsx");
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
        return Inertia::render('Licitaciones/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'required|string|max:255',
            'presupuesto' => 'required|numeric',
            'estado' => 'required|string',
            'clasificacion' => 'nullable|string|max:500',
            'especialidad' => 'nullable|string|max:255',
            'modalidad' => 'nullable|string|max:255',
            'consorcio' => 'boolean',
            'nombre_rc' => 'nullable|string|max:255',
            'nombre_consorcio' => 'nullable|string|max:255',
            'consorciados' => 'nullable|array',
            'promesa_consorcio' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['promesa_consorcio', 'documentos']);

        if ($request->hasFile('promesa_consorcio')) {
            $data['promesa_consorcio'] = $request->file('promesa_consorcio')->store('licitaciones/consorcios', 'public');
        }

        $data['user_id'] = auth()->id();
        // Derivar tipo (Pública/Privada) desde clasificación (ej. PUBLICAS / ... -> Publica, PRIVADAS / ... -> Privada)
        $clasif = $data['clasificacion'] ?? '';
        $data['tipo'] = (stripos($clasif, 'PRIVADAS') !== false) ? 'Privada' : 'Publica';

        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $data['folder_id'] = $folder->id;
            }
        }

        $licitacion = Licitacion::create($data);

        $this->storeDocumentos($request, $licitacion);

        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : ($licitacion->folder_id ?? null);
        $query = $folderId ? ['folder_id' => $folderId] : [];

        return redirect()->route('licitaciones.index', $query)->with('success', 'Licitación creada.');
    }

    private function storeDocumentos(Request $request, Licitacion $licitacion): void
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
            $path = $file->store('licitaciones/documentos', 'public');
            LicitacionDocumento::create([
                'licitacion_id' => $licitacion->id,
                'nombre' => $nombre ?: $file->getClientOriginalName(),
                'file_path' => $path,
            ]);
        }
    }

    public function edit(Licitacion $licitacion)
    {
        $user = auth()->user();
        if (!$this->canEdit($licitacion, $user)) {
            return redirect()->route('licitaciones.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        $licitacion->load('documentos');

        return Inertia::render('Licitaciones/Edit', [
            'licitacion' => $licitacion,
        ]);
    }

    public function update(Request $request, Licitacion $licitacion)
    {
        $user = auth()->user();
        if (!$this->canEdit($licitacion, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'required|string|max:255',
            'presupuesto' => 'required|numeric',
            'estado' => 'required|string',
            'clasificacion' => 'nullable|string|max:500',
            'especialidad' => 'nullable|string|max:255',
            'modalidad' => 'nullable|string|max:255',
            'consorcio' => 'boolean',
            'nombre_rc' => 'nullable|string|max:255',
            'nombre_consorcio' => 'nullable|string|max:255',
            'consorciados' => 'nullable|array',
            'documento_delete_ids' => 'nullable|array',
            'documento_delete_ids.*' => 'integer|exists:licitacion_documentos,id',
            'promesa_consorcio' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['promesa_consorcio', 'documentos', 'documento_delete_ids']);

        // Derivar tipo desde clasificación al actualizar
        if (array_key_exists('clasificacion', $data) && (string) $data['clasificacion'] !== '') {
            $data['tipo'] = (stripos($data['clasificacion'], 'PRIVADAS') !== false) ? 'Privada' : 'Publica';
        }

        if ($request->hasFile('promesa_consorcio')) {
            if ($licitacion->promesa_consorcio) {
                Storage::disk('public')->delete($licitacion->promesa_consorcio);
            }
            $data['promesa_consorcio'] = $request->file('promesa_consorcio')->store('licitaciones/consorcios', 'public');
        }

        $licitacion->update($data);

        $this->syncDocumentosUpdate($request, $licitacion);

        return redirect()->back()->with('success', 'Licitación actualizada.');
    }

    private function syncDocumentosUpdate(Request $request, Licitacion $licitacion): void
    {
        $deleteIds = $request->input('documento_delete_ids', []);
        if (is_array($deleteIds)) {
            $docs = LicitacionDocumento::where('licitacion_id', $licitacion->id)->whereIn('id', $deleteIds)->get();
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
            $path = $file->store('licitaciones/documentos', 'public');
            LicitacionDocumento::create([
                'licitacion_id' => $licitacion->id,
                'nombre' => $nombre ?: $file->getClientOriginalName(),
                'file_path' => $path,
            ]);
        }
    }

    public function destroy(Licitacion $licitacion)
    {
        $user = auth()->user();
        if (!$this->canDelete($licitacion, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para anular este registro.');
        }

        $licitacion->update(['anulado' => true]);
        return redirect()->route('licitaciones.index')->with('success', 'Licitación anulada.');
    }
}
