<?php

namespace App\Http\Controllers;

use App\Models\RegistroExpediente;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Traits\HasRoleBasedAccess;

class RegistroExpedienteController extends Controller
{
    use HasRoleBasedAccess;

    const MODULE = 'registro-expedientes';

    public function index(Request $request)
    {
        $user = auth()->user();
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;

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

        $query = RegistroExpediente::query();
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
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('proyecto', 'like', '%' . $search . '%')
                    ->orWhere('cui', 'like', '%' . $search . '%')
                    ->orWhere('numero_folio', 'like', '%' . $search . '%')
                    ->orWhere('resolucion', 'like', '%' . $search . '%')
                    ->orWhere('tipo_inversion', 'like', '%' . $search . '%');
            });
        }

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        $expedientes = $query->latest()->paginate(15)->withQueryString()->appends($request->only(['folder_id', 'user_id']));

        return Inertia::render('RegistroExpedientes/Index', [
            'expedientes' => $expedientes,
            'filters' => $request->only(['search', 'folder_id', 'user_id']),
            'userRole' => $user->role,
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'breadcrumb' => $breadcrumb,
            'operadores' => $operadores,
        ]);
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
        return Inertia::render('RegistroExpedientes/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
            'opcionesTipoUnidad' => RegistroExpediente::opcionesTipoUnidadConservacion(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'tipo_inversion' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:50',
            'etiqueta' => 'nullable|string|max:50',
            'proyecto' => 'nullable|string',
            'cui' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string|max:500',
            'numero_folio' => 'nullable|string|max:100',
            'tomos' => 'nullable|string|max:100',
            'anio' => 'nullable|integer|min:1900|max:2100',
            'tipo_unidad_conservacion' => 'nullable|string|max:255',
            'resolucion' => 'nullable|string|max:100',
            'fecha_aprobacion' => 'nullable|string',
            'monto_o' => 'nullable|numeric|min:0',
            'monto_p' => 'nullable|numeric|min:0',
            'monto_r' => 'nullable|numeric|min:0',
            'monto_s' => 'nullable|numeric|min:0',
        ]);

        $data = $this->prepareData($validated, $request);
        $data['user_id'] = auth()->id();

        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $data['folder_id'] = $folder->id;
            }
        }

        $expediente = RegistroExpediente::create($data);
        $folderId = $expediente->folder_id;

        return redirect()->route('registro-expedientes.index', $folderId ? ['folder_id' => $folderId] : [])
            ->with('success', 'Expediente registrado correctamente.');
    }

    public function edit(RegistroExpediente $registroExpediente)
    {
        $user = auth()->user();
        if (!$this->canEdit($registroExpediente, $user)) {
            return redirect()->route('registro-expedientes.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        $e = $registroExpediente;
        $expediente = [
            'id' => $e->id,
            'folder_id' => $e->folder_id,
            'tipo_inversion' => $e->tipo_inversion,
            'numero' => $e->numero,
            'etiqueta' => $e->etiqueta,
            'proyecto' => $e->proyecto,
            'cui' => $e->cui,
            'descripcion' => $e->descripcion,
            'numero_folio' => $e->numero_folio,
            'tomos' => $e->tomos,
            'anio' => $e->anio,
            'tipo_unidad_conservacion' => $e->tipo_unidad_conservacion,
            'resolucion' => $e->resolucion,
            'fecha_aprobacion' => $e->fecha_aprobacion?->format('Y-m-d'),
            'monto_o' => $e->monto_o !== null ? (float) $e->monto_o : null,
            'monto_p' => $e->monto_p !== null ? (float) $e->monto_p : null,
            'monto_r' => $e->monto_r !== null ? (float) $e->monto_r : null,
            'monto_s' => $e->monto_s !== null ? (float) $e->monto_s : null,
        ];

        return Inertia::render('RegistroExpedientes/Edit', [
            'expediente' => $expediente,
            'opcionesTipoUnidad' => RegistroExpediente::opcionesTipoUnidadConservacion(),
        ]);
    }

    public function update(Request $request, RegistroExpediente $registroExpediente)
    {
        $user = auth()->user();
        if (!$this->canEdit($registroExpediente, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'tipo_inversion' => 'nullable|string|max:255',
            'numero' => 'nullable|string|max:50',
            'etiqueta' => 'nullable|string|max:50',
            'proyecto' => 'nullable|string',
            'cui' => 'nullable|string|max:50',
            'descripcion' => 'nullable|string|max:500',
            'numero_folio' => 'nullable|string|max:100',
            'tomos' => 'nullable|string|max:100',
            'anio' => 'nullable|integer|min:1900|max:2100',
            'tipo_unidad_conservacion' => 'nullable|string|max:255',
            'resolucion' => 'nullable|string|max:100',
            'fecha_aprobacion' => 'nullable|string',
            'monto_o' => 'nullable|numeric|min:0',
            'monto_p' => 'nullable|numeric|min:0',
            'monto_r' => 'nullable|numeric|min:0',
            'monto_s' => 'nullable|numeric|min:0',
        ]);

        $data = $this->prepareData($validated, $request);
        $registroExpediente->update($data);

        $folderId = $registroExpediente->folder_id;
        return redirect()->route('registro-expedientes.index', $folderId ? ['folder_id' => $folderId] : [])
            ->with('success', 'Expediente actualizado correctamente.');
    }

    public function destroy(RegistroExpediente $registroExpediente)
    {
        $user = auth()->user();
        if (!$this->canDelete($registroExpediente, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        $folderId = $registroExpediente->folder_id;
        $registroExpediente->delete();

        return redirect()->route('registro-expedientes.index', $folderId ? ['folder_id' => $folderId] : [])
            ->with('success', 'Expediente eliminado.');
    }

    private function prepareData(array $validated, Request $request): array
    {
        $data = $validated;

        $fecha = $request->input('fecha_aprobacion');
        if ($fecha && is_string($fecha)) {
            $data['fecha_aprobacion'] = preg_match('/^\d{4}-\d{2}-\d{2}/', trim($fecha))
                ? trim(substr($fecha, 0, 10))
                : parse_fecha_dd_mm_yyyy($fecha);
        } else {
            $data['fecha_aprobacion'] = null;
        }

        foreach (['monto_o', 'monto_p', 'monto_r', 'monto_s'] as $key) {
            $val = $request->input($key);
            if ($val !== null && $val !== '') {
                $data[$key] = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', (string) $val));
            } else {
                $data[$key] = null;
            }
        }

        return $data;
    }
}
