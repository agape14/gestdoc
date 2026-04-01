<?php

namespace App\Http\Controllers;

use App\Models\RegistroExpediente;
use App\Models\Folder;
use App\Exports\RegistroExpedientesExport;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\HasRoleBasedAccess;
use App\Traits\MovesToFolder;
use App\Support\GridPagination;

class RegistroExpedienteController extends Controller
{
    use HasRoleBasedAccess, MovesToFolder;

    const MODULE = 'registro-expedientes';

    /** Acciones al crear registro derivado desde el grid */
    public const TIPOS_ACCION = [
        'ADICIONAL',
        'ADICIONAL_CON_DEDUCTIVO',
        'DEDUCTIVO',
        'ACTUALIZACION_PRECIOS',
        'REFORMULACION',
        'VALORIZACION',
        'LIQUIDACION',
    ];

    public function index(Request $request)
    {
        $user = auth()->user();
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;

        if ($folderId) {
            $currentFolder = Folder::visibleForModuleUser(self::MODULE, $user)->findOrFail($folderId);
            $currentFolder->load(['parent']);
            // Cargar cadena completa de padres para path y moveTargetFolders
            $p = $currentFolder->parent;
            while ($p) {
                $p->load(['parent']);
                $p = $p->parent;
            }
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
            $search = trim((string) $request->search);
            $searchUpper = mb_strtoupper($search);
            $query->where(function ($q) use ($search) {
                $q->where('proyecto', 'like', '%' . $search . '%')
                    ->orWhere('cui', 'like', '%' . $search . '%')
                    ->orWhere('etiqueta', 'like', '%' . $search . '%')
                    ->orWhere('descripcion', 'like', '%' . $search . '%')
                    ->orWhere('numero_folio', 'like', '%' . $search . '%')
                    ->orWhere('resolucion', 'like', '%' . $search . '%')
                    ->orWhere('tipo_inversion', 'like', '%' . $search . '%')
                    ->orWhere('tipo_accion', 'like', '%' . $search . '%')
                    ->orWhere('estado', 'like', '%' . $search . '%');
            });

            if (str_contains($searchUpper, 'ARCHIV')) {
                $query->where('estado', 'ARCHIVADO');
            } elseif (str_contains($searchUpper, 'CURSO')) {
                $query->where('estado', 'EN CURSO');
            }
        }

        $sortBy = (string) $request->input('sort_by', 'etiqueta');
        $sortDir = strtolower((string) $request->input('sort_dir', 'asc')) === 'desc' ? 'desc' : 'asc';
        $sortable = ['etiqueta', 'tipo_inversion', 'proyecto', 'cui', 'estado', 'fecha_aprobacion', 'monto_total'];
        if (!in_array($sortBy, $sortable, true)) {
            $sortBy = 'etiqueta';
        }

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        if ($sortBy === 'monto_total') {
            $query->orderByRaw('COALESCE(monto_o,0) + COALESCE(monto_p,0) + COALESCE(monto_s,0) + COALESCE(monto_supervision,0) ' . strtoupper($sortDir));
        } elseif ($sortBy === 'etiqueta') {
            $query->orderByRaw('COALESCE(etiqueta, "") ' . strtoupper($sortDir));
        } else {
            $query->orderBy($sortBy, $sortDir);
        }
        $query->orderBy('id', 'asc');

        $expedientes = GridPagination::paginate($query, $request);

        // Todas las carpetas del módulo visibles para el usuario, como destinos posibles al mover
        $moveTargetFolders = Folder::visibleForModuleUser(self::MODULE, $user)
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn ($f) => ['id' => $f->id, 'name' => $f->name])
            ->values()
            ->all();

        return Inertia::render('RegistroExpedientes/Index', [
            'expedientes' => $expedientes,
            'filters' => array_merge(
                $request->only(['search', 'folder_id', 'user_id', 'sort_by', 'sort_dir']),
                ['per_page' => GridPagination::perPageFilterValue($request)]
            ),
            'userRole' => $user->role,
            'folders' => $folders,
            'moveTargetFolders' => $moveTargetFolders,
            'currentFolder' => $currentFolder,
            'breadcrumb' => $breadcrumb,
            'operadores' => $operadores,
        ]);
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $query = RegistroExpediente::query();

        if ($request->filled('folder_id')) {
            $query->where('folder_id', (int) $request->folder_id);
        } else {
            $query->whereNull('folder_id');
        }
        $query = $this->applyExportRoleFilter($query, $user, $request);
        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $searchUpper = mb_strtoupper($search);
            $query->where(function ($q) use ($search) {
                $q->where('proyecto', 'like', '%' . $search . '%')
                    ->orWhere('cui', 'like', '%' . $search . '%')
                    ->orWhere('etiqueta', 'like', '%' . $search . '%')
                    ->orWhere('descripcion', 'like', '%' . $search . '%')
                    ->orWhere('numero_folio', 'like', '%' . $search . '%')
                    ->orWhere('resolucion', 'like', '%' . $search . '%')
                    ->orWhere('tipo_inversion', 'like', '%' . $search . '%')
                    ->orWhere('tipo_accion', 'like', '%' . $search . '%')
                    ->orWhere('estado', 'like', '%' . $search . '%');
            });

            if (str_contains($searchUpper, 'ARCHIV')) {
                $query->where('estado', 'ARCHIVADO');
            } elseif (str_contains($searchUpper, 'CURSO')) {
                $query->where('estado', 'EN CURSO');
            }
        }

        $expedientes = $query->orderByRaw('COALESCE(etiqueta, "") ASC')->orderBy('id')->get();
        $filename = 'registro-expedientes_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new RegistroExpedientesExport($expedientes), $filename);
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
        $queryCount = RegistroExpediente::query();
        if ($folderId) {
            $queryCount->where('folder_id', $folderId);
        } else {
            $queryCount->whereNull('folder_id');
        }
        $nextNumero = (string) ($queryCount->count() + 1);
        $prefillProyecto = $request->get('prefill_proyecto');
        $prefillCui = $request->get('prefill_cui');
        $tipoAccion = in_array($request->get('tipo_accion'), self::TIPOS_ACCION, true) ? $request->get('tipo_accion') : null;

        $prefillTipoInversion = '';
        $prefillEtiqueta = '';
        $prefillDescripcion = '';
        $prefillEstado = 'EN CURSO';
        $lockPrefill = false;

        if ($request->filled('prefill_from')) {
            $q = RegistroExpediente::query();
            $q = $this->applyRoleBasedFilter($q, auth()->user());
            $src = $q->find((int) $request->prefill_from);
            if ($src) {
                $sameFolder = ($folderId && (int) $src->folder_id === $folderId)
                    || (!$folderId && $src->folder_id === null);
                if ($sameFolder) {
                    $prefillTipoInversion = $src->tipo_inversion ?? '';
                    $prefillEtiqueta = $src->etiqueta ?? '';
                    $prefillProyecto = $src->proyecto ?? $prefillProyecto;
                    $prefillCui = $src->cui ?? $prefillCui;
                    $prefillDescripcion = $src->descripcion ?? '';
                    $prefillEstado = $src->estado ?: ($src->tipo_accion === 'LIQUIDACION' ? 'ARCHIVADO' : 'EN CURSO');
                    $lockPrefill = $tipoAccion !== null;
                }
            }
        }

        return Inertia::render('RegistroExpedientes/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
            'opcionesTipoUnidad' => RegistroExpediente::opcionesTipoUnidadConservacion(),
            'opcionesTipoInversion' => RegistroExpediente::opcionesTipoInversion(),
            'nextNumero' => $nextNumero,
            'prefillProyecto' => $prefillProyecto ?? '',
            'prefillCui' => $prefillCui ?? '',
            'prefillTipoInversion' => $prefillTipoInversion,
            'prefillEtiqueta' => $prefillEtiqueta,
            'prefillDescripcion' => $prefillDescripcion,
            'prefillEstado' => $prefillEstado,
            'tipoAccion' => $tipoAccion,
            'lockPrefill' => $lockPrefill,
        ]);
    }

    public function listarPorTipo(Request $request)
    {
        $validated = $request->validate([
            'tipo_inversion' => 'required|string|max:255',
            'folder_id' => 'nullable|integer|exists:folders,id',
        ]);

        $user = auth()->user();
        $query = RegistroExpediente::query();
        $query = $this->applyRoleBasedFilter($query, $user);
        $query->where('tipo_inversion', $validated['tipo_inversion']);

        if (!empty($validated['folder_id'])) {
            $query->where('folder_id', $validated['folder_id']);
        } else {
            $query->whereNull('folder_id');
        }

        $expedientes = $query->orderByRaw('COALESCE(etiqueta, "") ASC')->orderBy('id')->limit(100)->get();

        $list = $expedientes->map(function ($e) {
            return [
                'id' => $e->id,
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
                'estado' => $e->estado ?: ($e->tipo_accion === 'LIQUIDACION' ? 'ARCHIVADO' : 'EN CURSO'),
                'fecha_aprobacion' => $e->fecha_aprobacion?->format('Y-m-d'),
                'tiene_actualizacion_precios' => $e->tiene_actualizacion_precios,
                'tiene_reformulacion' => $e->tiene_reformulacion,
                'monto_o' => $e->monto_o !== null ? (float) $e->monto_o : null,
                'monto_p' => $e->monto_p !== null ? (float) $e->monto_p : null,
                'monto_r' => $e->monto_r !== null ? (float) $e->monto_r : null,
                'monto_s' => $e->monto_s !== null ? (float) $e->monto_s : null,
                'monto_supervision' => $e->monto_supervision !== null ? (float) $e->monto_supervision : null,
            ];
        })->values()->all();

        return response()->json(['expedientes' => $list]);
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
            'tipo_accion' => 'nullable|string|in:' . implode(',', self::TIPOS_ACCION),
            'estado' => 'nullable|string|in:EN CURSO,ARCHIVADO',
            'monto_o' => 'nullable|numeric',
            'monto_p' => 'nullable|numeric',
            'monto_r' => 'nullable|numeric',
            'monto_s' => 'nullable|numeric',
            'monto_supervision' => 'nullable|numeric',
            'contrato' => 'nullable|file|max:25600',
            'resolucion_archivo' => 'nullable|file|max:25600',
        ], [], ['resolucion_archivo' => 'subir resolución', 'contrato' => 'subir contrato']);

        $data = $this->prepareData($validated, $request);
        $data['tiene_actualizacion_precios'] = null;
        $data['tiene_reformulacion'] = null;
        $data['tuvo_suspension'] = null;
        $data['fecha_suspension'] = null;
        $data['fecha_reinicio'] = null;
        $data['acta_suspension'] = null;
        $data['acta_reinicio'] = null;
        $data['user_id'] = auth()->id();

        $folderId = null;
        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $data['folder_id'] = $folder->id;
                $folderId = $folder->id;
            }
        }

        $queryCount = RegistroExpediente::query()->when($data['folder_id'] ?? null, fn ($q) => $q->where('folder_id', $data['folder_id']), fn ($q) => $q->whereNull('folder_id'));
        $data['numero'] = (string) ($queryCount->count() + 1);

        if ($request->hasFile('contrato')) {
            $data['contrato'] = $request->file('contrato')->store('expedientes/registro_expedientes', 'r2');
        }
        if ($request->hasFile('resolucion_archivo')) {
            $data['resolucion_archivo'] = $request->file('resolucion_archivo')->store('expedientes/registro_expedientes', 'r2');
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
                'estado' => $e->estado ?: ($e->tipo_accion === 'LIQUIDACION' ? 'ARCHIVADO' : 'EN CURSO'),
            'fecha_aprobacion' => $e->fecha_aprobacion?->format('Y-m-d'),
            'tiene_actualizacion_precios' => $e->tiene_actualizacion_precios,
            'tiene_reformulacion' => $e->tiene_reformulacion,
            'monto_o' => $e->monto_o !== null ? (float) $e->monto_o : null,
            'monto_p' => $e->monto_p !== null ? (float) $e->monto_p : null,
            'monto_r' => $e->monto_r !== null ? (float) $e->monto_r : null,
            'monto_s' => $e->monto_s !== null ? (float) $e->monto_s : null,
            'monto_supervision' => $e->monto_supervision !== null ? (float) $e->monto_supervision : null,
            'contrato' => $e->contrato,
            'contrato_url' => \storage_url_for_path($e->contrato),
            'resolucion_archivo' => $e->resolucion_archivo,
            'resolucion_archivo_url' => \storage_url_for_path($e->resolucion_archivo),
            'tuvo_suspension' => $e->tuvo_suspension,
            'fecha_suspension' => $e->fecha_suspension?->format('Y-m-d'),
            'acta_suspension' => $e->acta_suspension,
            'fecha_reinicio' => $e->fecha_reinicio?->format('Y-m-d'),
            'acta_reinicio' => $e->acta_reinicio,
            'tipo_accion' => $e->tipo_accion,
        ];

        return Inertia::render('RegistroExpedientes/Edit', [
            'expediente' => $expediente,
            'opcionesTipoUnidad' => RegistroExpediente::opcionesTipoUnidadConservacion(),
            'opcionesTipoInversion' => RegistroExpediente::opcionesTipoInversion(),
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
            'tipo_accion' => 'nullable|string|in:' . implode(',', self::TIPOS_ACCION),
            'estado' => 'nullable|string|in:EN CURSO,ARCHIVADO',
            'monto_o' => 'nullable|numeric',
            'monto_p' => 'nullable|numeric',
            'monto_r' => 'nullable|numeric',
            'monto_s' => 'nullable|numeric',
            'monto_supervision' => 'nullable|numeric',
            'contrato' => 'nullable|file|max:25600',
            'resolucion_archivo' => 'nullable|file|max:25600',
        ], [], ['resolucion_archivo' => 'subir resolución', 'contrato' => 'subir contrato']);

        $data = $this->prepareData($validated, $request);
        $data['tiene_actualizacion_precios'] = null;
        $data['tiene_reformulacion'] = null;
        $data['tuvo_suspension'] = null;
        $data['fecha_suspension'] = null;
        $data['fecha_reinicio'] = null;
        $data['acta_suspension'] = null;
        $data['acta_reinicio'] = null;
        foreach (['acta_suspension', 'acta_reinicio'] as $f) {
            if ($registroExpediente->$f) {
                Storage::disk(storage_disk_for_path($registroExpediente->$f))->delete($registroExpediente->$f);
            }
        }
        if ($request->hasFile('contrato')) {
            if ($registroExpediente->contrato) {
                Storage::disk(storage_disk_for_path($registroExpediente->contrato))->delete($registroExpediente->contrato);
            }
            $data['contrato'] = $request->file('contrato')->store('expedientes/registro_expedientes', 'r2');
        }
        if ($request->hasFile('resolucion_archivo')) {
            if ($registroExpediente->resolucion_archivo) {
                Storage::disk(storage_disk_for_path($registroExpediente->resolucion_archivo))->delete($registroExpediente->resolucion_archivo);
            }
            $data['resolucion_archivo'] = $request->file('resolucion_archivo')->store('expedientes/registro_expedientes', 'r2');
        }
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

    public function move(Request $request, RegistroExpediente $registroExpediente)
    {
        return $this->moveItem($request, $registroExpediente, self::MODULE, 'registro-expedientes.index');
    }

    public function moveBulk(Request $request)
    {
        return $this->moveBulkItems($request, RegistroExpediente::class, self::MODULE, 'item_ids', 'registro-expedientes.index');
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

        foreach (['monto_o', 'monto_p', 'monto_s', 'monto_supervision'] as $key) {
            $val = $request->input($key);
            if ($val !== null && $val !== '') {
                $data[$key] = (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', (string) $val));
            } else {
                $data[$key] = null;
            }
        }
        // monto_r: solo actualizar si el cliente lo envía (ya no está en crear/editar)
        if ($request->exists('monto_r')) {
            $val = $request->input('monto_r');
            $data['monto_r'] = ($val !== null && $val !== '')
                ? (float) preg_replace('/[^\d.]/', '', str_replace(',', '.', (string) $val))
                : null;
        } else {
            unset($data['monto_r']);
        }

        $estado = strtoupper(trim((string) ($request->input('estado') ?? '')));
        if ($estado !== 'EN CURSO' && $estado !== 'ARCHIVADO') {
            $estado = (($data['tipo_accion'] ?? null) === 'LIQUIDACION') ? 'ARCHIVADO' : 'EN CURSO';
        }
        $data['estado'] = $estado;

        return $data;
    }
}
