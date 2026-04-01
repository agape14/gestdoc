<?php

namespace App\Http\Controllers;

use App\Exports\EspecialistasConsultoriaExport;
use App\Models\EspecialistaConsultoria;
use App\Models\EspecialistaConsultoriaDocumento;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Traits\HasRoleBasedAccess;
use App\Traits\MovesToFolder;
use App\Support\GridPagination;

class EspecialistaConsultoriaController extends Controller
{
    use HasRoleBasedAccess, MovesToFolder;

    const MODULE = 'especialistas-consultoria';

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

        $query = EspecialistaConsultoria::query()->active()->with('documentos');
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

        $sortable = ['id', 'cliente', 'objeto_del_contrato', 'cui', 'fecha_inicio', 'fecha_culminacion', 'total_dias', 'monto_neto', 'created_at'];
        $sort = (string) $request->input('sort', 'created_at');
        if (!in_array($sort, $sortable, true)) {
            $sort = 'created_at';
        }
        $direction = strtolower((string) $request->input('direction', 'desc')) === 'asc' ? 'asc' : 'desc';
        $query->orderBy($sort, $direction)->orderBy('id', 'desc');

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        $especialistas = GridPagination::paginate(clone $query, $request);
        $totalsQuery = EspecialistaConsultoria::query()->active();
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

        return Inertia::render('EspecialistasConsultoria/Index', [
            'especialistas' => $especialistas,
            'experienceTotals' => $experienceTotals,
            'filters' => array_merge(
                $request->only(['search', 'tipo', 'user_id', 'folder_id']),
                ['sort' => $sort, 'direction' => $direction, 'per_page' => GridPagination::perPageFilterValue($request)]
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
        return Inertia::render('EspecialistasConsultoria/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
        ]);
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;

        $query = EspecialistaConsultoria::query()->active();
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
        $filename = 'especialistas-consultoria_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new EspecialistasConsultoriaExport($rows), $filename);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente' => 'required|string|max:500',
            'objeto_del_contrato' => 'required|string',
            'cui' => 'nullable|string|max:100',
            'numero_contrato_os_comprobante' => 'required|string|max:255',
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
        ]);
        foreach ($request->input('documentos', []) as $i => $doc) {
            if (($doc['tipo_documento_adjunto'] ?? '') === 'OTROS' && empty(trim($doc['nombre_otro'] ?? ''))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "documentos.{$i}.nombre_otro" => ['El nombre del documento es obligatorio cuando el tipo es Otros.'],
                ]);
            }
        }

        $data = $this->prepareExperienciaData($request, null);
        $data['user_id'] = auth()->id();
        $data['nombre'] = $data['nombre'] ?? $data['cliente'];
        $data['tipo'] = $data['tipo'] ?? 'Profesional';
        $data['estado'] = $data['estado'] ?? 'Activo';

        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $folder->load(['parent']);
                $data['folder_id'] = $folder->id;
                $path = $folder->path;
                $data['clasificacion'] = $request->filled('clasificacion') ? $request->clasificacion : (is_array($path) ? implode(' / ', array_column($path, 'name')) : $folder->name);
            }
        }

        if ($request->hasFile('documento')) {
            $data['documento'] = $request->file('documento')->store('expedientes/especialistas_consultoria', 'r2');
        }

        $especialista = EspecialistaConsultoria::create($data);
        $this->storeDocumentosConsultoriaFromRequest($request, $especialista);
        $this->recalculateMontoAcumulado(EspecialistaConsultoria::class, $especialista->folder_id);
        return redirect()->route('especialistas-consultoria.index', $especialista->folder_id ? ['folder_id' => $especialista->folder_id] : [])->with('success', 'Registro creado.');
    }

    private function prepareExperienciaData(Request $request, $model): array
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
            'fecha_inicio' => $fechaInicio,
            'fecha_suspension' => $fechaSuspension,
            'fecha_reinicio' => $fechaReinicio,
            'fecha_culminacion' => $fechaCulminacion,
            'total_meses' => $totalMeses,
            'total_dias' => $totalDias,
            'traslape' => $traslape,
            'total_dias_sin_traslape' => $totalDiasSinTraslape,
            'monto_neto' => $request->input('monto_neto') !== null && $request->input('monto_neto') !== '' ? (float) preg_replace('/[^\d.]/', '', $request->input('monto_neto')) : null,
        ];

        $basePath = 'expedientes/especialistas_consultoria';
        $firstTipo = $request->input('documentos.0.tipo_documento_adjunto');
        if ($firstTipo) {
            $data['tipo_documento_adjunto'] = $firstTipo;
        } else {
            $data['tipo_documento_adjunto'] = $request->input('tipo_documento_adjunto');
        }

        if ($request->hasFile('documentos.0.archivo')) {
            $data['archivo_contrato'] = $request->file('documentos.0.archivo')->store($basePath . '/contratos', 'r2');
        } elseif ($request->hasFile('archivo_contrato')) {
            $data['archivo_contrato'] = $request->file('archivo_contrato')->store($basePath . '/contratos', 'r2');
        } elseif ($model) {
            $data['archivo_contrato'] = $model->archivo_contrato;
        }

        if ($request->hasFile('archivo_comprobante_pago')) {
            $data['archivo_comprobante_pago'] = $request->file('archivo_comprobante_pago')->store($basePath . '/comprobantes', 'r2');
        } elseif ($model) {
            $data['archivo_comprobante_pago'] = $model->archivo_comprobante_pago;
        }
        if ($request->hasFile('archivo_conformidad_servicio')) {
            $data['archivo_conformidad_servicio'] = $request->file('archivo_conformidad_servicio')->store($basePath . '/conformidades', 'r2');
        } elseif ($model) {
            $data['archivo_conformidad_servicio'] = $model->archivo_conformidad_servicio;
        }

        if ($request->hasFile('archivo_suspension')) {
            if ($model && $model->archivo_suspension) {
                Storage::disk(storage_disk_for_path($model->archivo_suspension))->delete($model->archivo_suspension);
            }
            $data['archivo_suspension'] = $request->file('archivo_suspension')->store($basePath . '/suspension', 'r2');
        } elseif ($model) {
            if (! $fechaSuspension) {
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
            if (! $fechaReinicio) {
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

    private function storeDocumentosConsultoriaFromRequest(Request $request, EspecialistaConsultoria $especialista): void
    {
        $basePath = 'expedientes/especialistas_consultoria/adjuntos';
        $tiposLabels = [
            'CONTRATO' => 'CONTRATO',
            'COMPROBANTE_DE_PAGO' => 'COMPROBANTE DE PAGO',
            'CONFORMIDAD_DE_SERVICIO' => 'CONFORMIDAD DE SERVICIO',
            'OTROS' => null,
        ];
        $documentosList = array_values($request->input('documentos', []));
        foreach ($documentosList as $index => $doc) {
            $tipo = $doc['tipo_documento_adjunto'] ?? '';
            $nombreDoc = ($tipo === 'OTROS' && ! empty(trim($doc['nombre_otro'] ?? '')))
                ? trim($doc['nombre_otro'])
                : ($tiposLabels[$tipo] ?? $tipo);
            $file = $request->file("documentos.{$index}.archivo");
            if ($file) {
                $path = $file->store($basePath, 'r2');
                EspecialistaConsultoriaDocumento::create([
                    'especialista_consultoria_id' => $especialista->id,
                    'nombre' => $nombreDoc,
                    'file_path' => $path,
                ]);
                if ($index === 0) {
                    $especialista->update(['archivo_contrato' => $path, 'tipo_documento_adjunto' => $tipo]);
                }
            }
        }
    }

    private function recalculateMontoAcumulado(string $modelClass, $folderId): void
    {
        $query = $modelClass::query()->where('anulado', false)->orderBy('id');
        if ($folderId !== null) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }
        $acum = 0;
        foreach ($query->get() as $item) {
            $acum += (float) ($item->monto_neto ?? 0);
            $item->update(['monto_acumulado' => round($acum, 2)]);
        }
    }

    public function edit(EspecialistaConsultoria $especialistaConsultoria)
    {
        $user = auth()->user();
        if (!$this->canEdit($especialistaConsultoria, $user)) {
            return redirect()->route('especialistas-consultoria.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        $e = $especialistaConsultoria;
        $e->load('documentos');
        $especialista = [
            'id' => $e->id,
            'folder_id' => $e->folder_id,
            'clasificacion' => $e->clasificacion,
            'cliente' => $e->cliente,
            'objeto_del_contrato' => $e->objeto_del_contrato,
            'cui' => $e->cui,
            'numero_contrato_os_comprobante' => $e->numero_contrato_os_comprobante,
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
            'documentos_existentes' => $e->documentos->map(fn ($d) => [
                'id' => $d->id,
                'nombre' => $d->nombre,
                'url' => $d->url,
            ])->values(),
            'documentos' => [['tipo_documento_adjunto' => '', 'nombre_otro' => '', 'archivo' => null]],
        ];
        return Inertia::render('EspecialistasConsultoria/Edit', [
            'especialista' => $especialista,
        ]);
    }

    public function update(Request $request, EspecialistaConsultoria $especialistaConsultoria)
    {
        $user = auth()->user();
        if (!$this->canEdit($especialistaConsultoria, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $request->validate([
            'cliente' => 'required|string|max:500',
            'objeto_del_contrato' => 'required|string',
            'cui' => 'nullable|string|max:100',
            'numero_contrato_os_comprobante' => 'required|string|max:255',
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
            'documentos.*.tipo_documento_adjunto' => 'required_with:documentos.*.archivo|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO,OTROS',
            'documentos.*.nombre_otro' => 'nullable|string|max:255',
            'documentos.*.archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
        ]);
        foreach ($request->input('documentos', []) as $i => $doc) {
            if (($doc['tipo_documento_adjunto'] ?? '') === 'OTROS' && $request->hasFile("documentos.{$i}.archivo") && empty(trim($doc['nombre_otro'] ?? ''))) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "documentos.{$i}.nombre_otro" => ['El nombre del documento es obligatorio cuando el tipo es Otros.'],
                ]);
            }
        }

        $data = $this->prepareExperienciaData($request, $especialistaConsultoria);
        if ($request->hasFile('documento')) {
            if ($especialistaConsultoria->documento) {
                Storage::disk(storage_disk_for_path($especialistaConsultoria->documento))->delete($especialistaConsultoria->documento);
            }
            $data['documento'] = $request->file('documento')->store('expedientes/especialistas_consultoria', 'r2');
        }
        $especialistaConsultoria->update($data);
        $this->storeDocumentosConsultoriaFromRequest($request, $especialistaConsultoria);
        $this->recalculateMontoAcumulado(EspecialistaConsultoria::class, $especialistaConsultoria->folder_id);
        return redirect()->route('especialistas-consultoria.index', $especialistaConsultoria->folder_id ? ['folder_id' => $especialistaConsultoria->folder_id] : [])->with('success', 'Registro actualizado.');
    }

    public function destroy(EspecialistaConsultoria $especialistaConsultoria)
    {
        $user = auth()->user();
        if (!$this->canDelete($especialistaConsultoria, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        $folderId = $especialistaConsultoria->folder_id;
        $especialistaConsultoria->load('documentos');
        foreach ($especialistaConsultoria->documentos as $doc) {
            if ($doc->file_path) {
                Storage::disk(storage_disk_for_path($doc->file_path))->delete($doc->file_path);
            }
        }
        if ($especialistaConsultoria->documento) {
            Storage::disk(storage_disk_for_path($especialistaConsultoria->documento))->delete($especialistaConsultoria->documento);
        }
        foreach (['archivo_contrato', 'archivo_comprobante_pago', 'archivo_conformidad_servicio', 'archivo_suspension', 'archivo_reinicio'] as $field) {
            $path = $especialistaConsultoria->$field ?? null;
            if ($path) {
                Storage::disk(storage_disk_for_path($path))->delete($path);
            }
        }
        $especialistaConsultoria->delete();
        $this->recalculateMontoAcumulado(EspecialistaConsultoria::class, $folderId);
        return redirect()->route('especialistas-consultoria.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro eliminado.');
    }

    public function move(Request $request, EspecialistaConsultoria $especialistaConsultoria)
    {
        return $this->moveItem($request, $especialistaConsultoria, self::MODULE, 'especialistas-consultoria.index');
    }

    public function moveBulk(Request $request)
    {
        return $this->moveBulkItems($request, EspecialistaConsultoria::class, self::MODULE, 'item_ids', 'especialistas-consultoria.index');
    }
}
