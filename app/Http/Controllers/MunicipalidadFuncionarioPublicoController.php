<?php

namespace App\Http\Controllers;

use App\Exports\MunicipalidadesFuncionarioPublicoExport;
use App\Models\Folder;
use App\Models\MunicipalidadFuncionarioPublico;
use App\Models\MunicipalidadFuncionarioPublicoDocumento;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\HasRoleBasedAccess;
use App\Traits\MovesToFolder;

class MunicipalidadFuncionarioPublicoController extends Controller
{
    use HasRoleBasedAccess, MovesToFolder;

    const MODULE = 'municipalidades-funcionario-publico';
    private const ESTADOS_VALIDOS = ['COMPLETO', 'INCOMPLETO', 'EN CURSO', 'ARCHIVADO'];

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
            $folders = Folder::whereNull('parent_id')->visibleForModuleUser(self::MODULE, $user)->orderBy('name')->get();
            $breadcrumb = [];
        }

        $query = MunicipalidadFuncionarioPublico::query()->active()->with('documentos');
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
            $query->where(function ($q) use ($request) {
                $s = '%' . $request->search . '%';
                $q->where('nombre', 'like', $s)
                    ->orWhere('especialidad', 'like', $s)
                    ->orWhere('cliente', 'like', $s)
                    ->orWhere('objeto_del_contrato', 'like', $s);
            });
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $sortable = ['id', 'cliente', 'objeto_del_contrato', 'cui', 'fecha_contrato_cp', 'fecha_inicio', 'fecha_culminacion', 'estado', 'total_dias', 'monto_neto'];
        $sort = $request->input('sort', 'id');
        if (!in_array($sort, $sortable, true)) {
            $sort = 'id';
        }
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction);

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        $items = $query->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id', 'sort', 'direction', 'search', 'tipo']));
        $totalsQuery = MunicipalidadFuncionarioPublico::query()->active();
        $this->applyRoleBasedFilter($totalsQuery, $user);
        if ($folderId) {
            $totalsQuery->where('folder_id', $folderId);
        } else {
            $totalsQuery->whereNull('folder_id');
        }
        if ($request->filled('user_id') && $user->role === 'Administrador') {
            $totalsQuery->where('user_id', $request->user_id);
        }
        $experienceTotals = [
            'total_dias_sin_traslape' => (int) $totalsQuery->sum('total_dias_sin_traslape'),
            'total_monto_acumulado' => (float) $totalsQuery->orderBy('id')->get()->last()?->monto_acumulado ?? 0,
        ];

        return Inertia::render('MunicipalidadesFuncionarioPublico/Index', [
            'especialistas' => $items,
            'experienceTotals' => $experienceTotals,
            'filters' => array_merge(
                $request->only(['search', 'tipo', 'user_id', 'folder_id']),
                ['sort' => $sort, 'direction' => $direction]
            ),
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

        return Inertia::render('MunicipalidadesFuncionarioPublico/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
        ]);
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;

        $query = MunicipalidadFuncionarioPublico::query()->active();
        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }
        $query = $this->applyExportRoleFilter($query, $user, $request);
        if ($request->filled('search')) {
            $s = '%' . $request->search . '%';
            $query->where(function ($q) use ($s) {
                $q->where('nombre', 'like', $s)
                    ->orWhere('especialidad', 'like', $s)
                    ->orWhere('cliente', 'like', $s)
                    ->orWhere('objeto_del_contrato', 'like', $s);
            });
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }
        $rows = $query->orderBy('id')->get();
        $filename = 'municipalidades-funcionario-publico_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new MunicipalidadesFuncionarioPublicoExport($rows), $filename);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente' => 'required|string|max:500',
            'objeto_del_contrato' => 'required|string',
            'cui' => 'nullable|string|max:100',
            'numero_contrato_os_comprobante' => 'required|string|max:255',
            'fecha_contrato_cp' => 'nullable|string',
            'fecha_inicio' => 'required|string',
            'fecha_suspension' => 'nullable|string',
            'fecha_reinicio' => 'nullable|string',
            'fecha_culminacion' => 'required|string',
            'monto_neto' => 'required|numeric|min:0.01',
            'archivo_contrato' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'tipo_documento_adjunto' => 'nullable|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO,OTROS',
            'archivo_suspension' => 'nullable|file|mimes:pdf|max:25600',
            'archivo_reinicio' => 'nullable|file|mimes:pdf|max:25600',
            'documentos' => 'required|array|min:1',
            'documentos.*.tipo_documento_adjunto' => 'required|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO,OTROS',
            'documentos.*.nombre_otro' => 'nullable|string|max:255',
            'documentos.*.archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'estado' => 'nullable|string|in:COMPLETO,INCOMPLETO,EN CURSO,ARCHIVADO',
        ], $this->mensajesValidacion());
        foreach ($request->input('documentos', []) as $i => $doc) {
            if (($doc['tipo_documento_adjunto'] ?? '') === 'OTROS' && empty(trim($doc['nombre_otro'] ?? ''))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "documentos.{$i}.nombre_otro" => ['El nombre del documento es obligatorio cuando el tipo es Otros.'],
                ]);
            }
        }

        $data = $this->prepareData($request, null);
        $data['user_id'] = auth()->id();
        $data['nombre'] = $data['nombre'] ?? $data['cliente'];
        $data['tipo'] = $data['tipo'] ?? 'Profesional';
        $data['estado'] = $data['estado'] ?? 'EN CURSO';

        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $folder->load(['parent']);
                $data['folder_id'] = $folder->id;
                $path = $folder->path;
                $data['clasificacion'] = $request->filled('clasificacion')
                    ? $request->clasificacion
                    : (is_array($path) ? implode(' / ', array_column($path, 'name')) : $folder->name);
            }
        }

        $item = MunicipalidadFuncionarioPublico::create($data);
        $this->storeDocumentosFromRequest($request, $item);
        $this->recalculateMontoAcumulado($item->folder_id);

        return redirect()->route('municipalidades-funcionario-publico.index', $item->folder_id ? ['folder_id' => $item->folder_id] : [])->with('success', 'Registro creado.');
    }

    public function edit(MunicipalidadFuncionarioPublico $municipalidadFuncionarioPublico)
    {
        $user = auth()->user();
        if (!$this->canEdit($municipalidadFuncionarioPublico, $user)) {
            return redirect()->route('municipalidades-funcionario-publico.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        $e = $municipalidadFuncionarioPublico;
        $e->load('documentos');
        $especialista = [
            'id' => $e->id,
            'folder_id' => $e->folder_id,
            'clasificacion' => $e->clasificacion,
            'cliente' => $e->cliente,
            'objeto_del_contrato' => $e->objeto_del_contrato,
            'cui' => $e->cui,
            'numero_contrato_os_comprobante' => $e->numero_contrato_os_comprobante,
            'fecha_contrato_cp' => $e->fecha_contrato_cp?->format('Y-m-d'),
            'fecha_inicio' => $e->fecha_inicio?->format('Y-m-d'),
            'fecha_suspension' => $e->fecha_suspension?->format('Y-m-d'),
            'fecha_reinicio' => $e->fecha_reinicio?->format('Y-m-d'),
            'fecha_culminacion' => $e->fecha_culminacion?->format('Y-m-d'),
            'total_meses' => $e->total_meses,
            'total_dias' => $e->total_dias,
            'traslape' => $e->traslape,
            'total_dias_sin_traslape' => $e->total_dias_sin_traslape,
            'monto_neto' => $e->monto_neto,
            'monto_acumulado' => $e->monto_acumulado,
            'archivo_contrato' => $e->archivo_contrato,
            'tipo_documento_adjunto' => $e->tipo_documento_adjunto,
            'archivo_contrato_url' => $e->archivo_contrato_url,
            'archivo_suspension_url' => $e->archivo_suspension_url,
            'archivo_reinicio_url' => $e->archivo_reinicio_url,
            'estado' => in_array((string) $e->estado, self::ESTADOS_VALIDOS, true) ? $e->estado : 'EN CURSO',
            'documentos_existentes' => $e->documentos->map(fn ($d) => [
                'id' => $d->id,
                'nombre' => $d->nombre,
                'url' => $d->url,
            ])->values(),
            'documentos' => [['tipo_documento_adjunto' => '', 'nombre_otro' => '', 'archivo' => null]],
        ];

        return Inertia::render('MunicipalidadesFuncionarioPublico/Edit', [
            'especialista' => $especialista,
        ]);
    }

    public function update(Request $request, MunicipalidadFuncionarioPublico $municipalidadFuncionarioPublico)
    {
        $user = auth()->user();
        if (!$this->canEdit($municipalidadFuncionarioPublico, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }
        $request->validate([
            'cliente' => 'required|string|max:500',
            'objeto_del_contrato' => 'required|string',
            'cui' => 'nullable|string|max:100',
            'numero_contrato_os_comprobante' => 'required|string|max:255',
            'fecha_contrato_cp' => 'nullable|string',
            'fecha_inicio' => 'required|string',
            'fecha_suspension' => 'nullable|string',
            'fecha_reinicio' => 'nullable|string',
            'fecha_culminacion' => 'required|string',
            'monto_neto' => 'required|numeric|min:0.01',
            'archivo_contrato' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'tipo_documento_adjunto' => 'nullable|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO,OTROS',
            'archivo_suspension' => 'nullable|file|mimes:pdf|max:25600',
            'archivo_reinicio' => 'nullable|file|mimes:pdf|max:25600',
            'documentos' => 'nullable|array',
            'documentos.*.tipo_documento_adjunto' => [
                'nullable',
                'string',
                Rule::in(['', 'CONTRATO', 'COMPROBANTE_DE_PAGO', 'CONFORMIDAD_DE_SERVICIO', 'OTROS']),
            ],
            'documentos.*.nombre_otro' => 'nullable|string|max:255',
            'documentos.*.archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'documentos_eliminar_ids' => 'nullable|array',
            'documentos_eliminar_ids.*' => 'integer|exists:municipalidad_funcionario_publico_documentos,id',
            'estado' => 'nullable|string|in:COMPLETO,INCOMPLETO,EN CURSO,ARCHIVADO',
        ], $this->mensajesValidacion());
        $tiposDoc = ['CONTRATO', 'COMPROBANTE_DE_PAGO', 'CONFORMIDAD_DE_SERVICIO', 'OTROS'];
        foreach ($request->input('documentos', []) as $i => $doc) {
            if ($request->hasFile("documentos.{$i}.archivo")) {
                $tipo = $doc['tipo_documento_adjunto'] ?? '';
                if ($tipo === '' || ! in_array($tipo, $tiposDoc, true)) {
                    throw ValidationException::withMessages([
                        "documentos.{$i}.tipo_documento_adjunto" => ['Seleccione el tipo de documento cuando adjunta un archivo.'],
                    ]);
                }
            }
            if (($doc['tipo_documento_adjunto'] ?? '') === 'OTROS' && $request->hasFile("documentos.{$i}.archivo") && empty(trim($doc['nombre_otro'] ?? ''))) {
                throw ValidationException::withMessages([
                    "documentos.{$i}.nombre_otro" => ['El nombre del documento es obligatorio cuando el tipo es Otros.'],
                ]);
            }
        }

        $this->eliminarDocumentosMarcados($request, $municipalidadFuncionarioPublico);
        $municipalidadFuncionarioPublico->refresh();
        $this->syncPrimaryAdjuntoFromDocumentos($municipalidadFuncionarioPublico);

        $data = $this->prepareData($request, $municipalidadFuncionarioPublico);
        $municipalidadFuncionarioPublico->update($data);
        $this->storeDocumentosFromRequest($request, $municipalidadFuncionarioPublico);
        $this->recalculateMontoAcumulado($municipalidadFuncionarioPublico->folder_id);

        return redirect()->route('municipalidades-funcionario-publico.index', $municipalidadFuncionarioPublico->folder_id ? ['folder_id' => $municipalidadFuncionarioPublico->folder_id] : [])->with('success', 'Registro actualizado.');
    }

    public function destroy(MunicipalidadFuncionarioPublico $municipalidadFuncionarioPublico)
    {
        $user = auth()->user();
        if (!$this->canDelete($municipalidadFuncionarioPublico, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }
        $folderId = $municipalidadFuncionarioPublico->folder_id;
        $municipalidadFuncionarioPublico->load('documentos');
        foreach ($municipalidadFuncionarioPublico->documentos as $doc) {
            if ($doc->file_path) {
                Storage::disk(storage_disk_for_path($doc->file_path))->delete($doc->file_path);
            }
        }
        foreach (['documento', 'archivo_contrato', 'archivo_suspension', 'archivo_reinicio'] as $field) {
            $path = $municipalidadFuncionarioPublico->$field ?? null;
            if ($path) {
                Storage::disk(storage_disk_for_path($path))->delete($path);
            }
        }
        $municipalidadFuncionarioPublico->delete();
        $this->recalculateMontoAcumulado($folderId);
        return redirect()->route('municipalidades-funcionario-publico.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro eliminado.');
    }

    public function move(Request $request, MunicipalidadFuncionarioPublico $municipalidadFuncionarioPublico)
    {
        return $this->moveItem($request, $municipalidadFuncionarioPublico, self::MODULE, 'municipalidades-funcionario-publico.index');
    }

    public function moveBulk(Request $request)
    {
        return $this->moveBulkItems($request, MunicipalidadFuncionarioPublico::class, self::MODULE, 'item_ids', 'municipalidades-funcionario-publico.index');
    }

    private function prepareData(Request $request, $model): array
    {
        $fechaInicio = parse_fecha_dd_mm_yyyy($request->input('fecha_inicio'));
        $fechaCulminacion = parse_fecha_dd_mm_yyyy($request->input('fecha_culminacion'));
        $totalDias = null;
        if ($fechaInicio && $fechaCulminacion) {
            $totalDias = \Carbon\Carbon::parse($fechaInicio)->diffInDays(\Carbon\Carbon::parse($fechaCulminacion)) + 1;
        }
        $totalMeses = $totalDias !== null ? round($totalDias / 30, 2) : null;
        $traslape = 0.0;
        $totalDiasSinTraslape = $totalDias !== null ? max(0, (int) round($totalDias - $traslape)) : null;
        $fechaSuspension = parse_fecha_dd_mm_yyyy($request->input('fecha_suspension'));
        $fechaReinicio = parse_fecha_dd_mm_yyyy($request->input('fecha_reinicio'));

        $data = [
            'cliente' => $request->input('cliente'),
            'objeto_del_contrato' => $request->input('objeto_del_contrato'),
            'cui' => $request->input('cui'),
            'numero_contrato_os_comprobante' => $request->input('numero_contrato_os_comprobante'),
            'fecha_contrato_cp' => parse_fecha_dd_mm_yyyy($request->input('fecha_contrato_cp')),
            'fecha_inicio' => $fechaInicio,
            'fecha_suspension' => $fechaSuspension,
            'fecha_reinicio' => $fechaReinicio,
            'fecha_culminacion' => $fechaCulminacion,
            'total_meses' => $totalMeses,
            'total_dias' => $totalDias,
            'traslape' => $traslape,
            'total_dias_sin_traslape' => $totalDiasSinTraslape,
            'monto_neto' => $request->input('monto_neto') !== null && $request->input('monto_neto') !== '' ? (float) preg_replace('/[^\d.]/', '', $request->input('monto_neto')) : null,
            'estado' => in_array((string) $request->input('estado'), self::ESTADOS_VALIDOS, true) ? $request->input('estado') : 'EN CURSO',
        ];

        $basePath = 'expedientes/municipalidades_funcionario_publico';
        $firstTipo = $request->input('documentos.0.tipo_documento_adjunto');
        $data['tipo_documento_adjunto'] = $firstTipo ?: $request->input('tipo_documento_adjunto');

        if ($request->hasFile('documentos.0.archivo')) {
            $data['archivo_contrato'] = $request->file('documentos.0.archivo')->store($basePath . '/adjuntos', 'r2');
        } elseif ($request->hasFile('archivo_contrato')) {
            $data['archivo_contrato'] = $request->file('archivo_contrato')->store($basePath . '/adjuntos', 'r2');
        } elseif ($model) {
            $data['archivo_contrato'] = $model->archivo_contrato;
        }

        if ($request->hasFile('archivo_suspension')) {
            if ($model && $model->archivo_suspension) {
                Storage::disk(storage_disk_for_path($model->archivo_suspension))->delete($model->archivo_suspension);
            }
            $data['archivo_suspension'] = $request->file('archivo_suspension')->store($basePath . '/suspension', 'r2');
        } elseif ($model) {
            if (!$fechaSuspension) {
                if ($model->archivo_suspension) {
                    Storage::disk(storage_disk_for_path($model->archivo_suspension))->delete($model->archivo_suspension);
                }
                $data['archivo_suspension'] = null;
            } else {
                $data['archivo_suspension'] = $model->archivo_suspension;
            }
        } else {
            $data['archivo_suspension'] = null;
        }

        if ($request->hasFile('archivo_reinicio')) {
            if ($model && $model->archivo_reinicio) {
                Storage::disk(storage_disk_for_path($model->archivo_reinicio))->delete($model->archivo_reinicio);
            }
            $data['archivo_reinicio'] = $request->file('archivo_reinicio')->store($basePath . '/reinicio', 'r2');
        } elseif ($model) {
            if (!$fechaReinicio) {
                if ($model->archivo_reinicio) {
                    Storage::disk(storage_disk_for_path($model->archivo_reinicio))->delete($model->archivo_reinicio);
                }
                $data['archivo_reinicio'] = null;
            } else {
                $data['archivo_reinicio'] = $model->archivo_reinicio;
            }
        } else {
            $data['archivo_reinicio'] = null;
        }

        return $data;
    }

    private function eliminarDocumentosMarcados(Request $request, MunicipalidadFuncionarioPublico $item): void
    {
        $ids = $request->input('documentos_eliminar_ids', []);
        if (!is_array($ids) || $ids === []) {
            return;
        }
        foreach (array_unique(array_map('intval', $ids)) as $docId) {
            $doc = MunicipalidadFuncionarioPublicoDocumento::query()
                ->where('id', $docId)
                ->where('municipalidad_funcionario_publico_id', $item->id)
                ->first();
            if (!$doc) {
                continue;
            }
            if ($doc->file_path) {
                Storage::disk(storage_disk_for_path($doc->file_path))->delete($doc->file_path);
            }
            $doc->delete();
        }
    }

    /**
     * Alinea archivo_contrato y tipo_documento_adjunto con el primer documento en tabla (o null si no hay).
     */
    private function syncPrimaryAdjuntoFromDocumentos(MunicipalidadFuncionarioPublico $item): void
    {
        $item->load('documentos');
        $first = $item->documentos->sortBy('id')->first();
        if (!$first) {
            $item->update(['archivo_contrato' => null, 'tipo_documento_adjunto' => null]);

            return;
        }
        $nombreToTipo = [
            'CONTRATO' => 'CONTRATO',
            'COMPROBANTE DE PAGO' => 'COMPROBANTE_DE_PAGO',
            'CONFORMIDAD DE SERVICIO' => 'CONFORMIDAD_DE_SERVICIO',
        ];
        $tipo = $nombreToTipo[$first->nombre] ?? 'OTROS';
        $item->update([
            'archivo_contrato' => $first->file_path,
            'tipo_documento_adjunto' => $tipo,
        ]);
    }

    private function storeDocumentosFromRequest(Request $request, MunicipalidadFuncionarioPublico $item): void
    {
        $basePath = 'expedientes/municipalidades_funcionario_publico/adjuntos';
        $tiposLabels = [
            'CONTRATO' => 'CONTRATO',
            'COMPROBANTE_DE_PAGO' => 'COMPROBANTE DE PAGO',
            'CONFORMIDAD_DE_SERVICIO' => 'CONFORMIDAD DE SERVICIO',
            'OTROS' => null,
        ];
        $documentosList = array_values($request->input('documentos', []));
        foreach ($documentosList as $index => $doc) {
            $tipo = $doc['tipo_documento_adjunto'] ?? '';
            $nombreDoc = ($tipo === 'OTROS' && !empty(trim($doc['nombre_otro'] ?? '')))
                ? trim($doc['nombre_otro'])
                : ($tiposLabels[$tipo] ?? $tipo);
            $file = $request->file("documentos.{$index}.archivo");
            if ($file) {
                $path = $file->store($basePath, 'r2');
                MunicipalidadFuncionarioPublicoDocumento::create([
                    'municipalidad_funcionario_publico_id' => $item->id,
                    'nombre' => $nombreDoc,
                    'file_path' => $path,
                ]);
                if ($index === 0) {
                    $item->update(['archivo_contrato' => $path, 'tipo_documento_adjunto' => $tipo]);
                }
            }
        }
    }

    private function recalculateMontoAcumulado($folderId): void
    {
        $query = MunicipalidadFuncionarioPublico::query()->where('anulado', false)->orderBy('id');
        if ($folderId !== null) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }
        $items = $query->get();
        $acum = 0;
        foreach ($items as $row) {
            $acum += (float) ($row->monto_neto ?? 0);
            $row->update(['monto_acumulado' => round($acum, 2)]);
        }
    }

    private function mensajesValidacion(): array
    {
        return [
            'archivo_contrato.max' => 'El archivo supera el peso máximo permitido de 25 MB.',
            'archivo_suspension.max' => 'El archivo de suspensión supera el peso máximo permitido de 25 MB.',
            'archivo_reinicio.max' => 'El archivo de reinicio supera el peso máximo permitido de 25 MB.',
            'documentos.*.archivo.max' => 'El archivo adjunto supera el peso máximo permitido de 25 MB.',
            'archivo_contrato.mimes' => 'El archivo de contrato debe ser PDF, JPG, JPEG o PNG.',
            'documentos.*.archivo.mimes' => 'Cada archivo adjunto debe ser PDF, JPG, JPEG o PNG.',
        ];
    }
}
