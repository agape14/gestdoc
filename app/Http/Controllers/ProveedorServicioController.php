<?php

namespace App\Http\Controllers;

use App\Models\ProveedorServicio;
use App\Models\ProveedorServicioDocumento;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;
use App\Traits\MovesToFolder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProveedorServiciosExport;

class ProveedorServicioController extends Controller
{
    use HasRoleBasedAccess, MovesToFolder;

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

        $totalsQuery = ProveedorServicio::query()->active();
        $this->applyRoleBasedFilterWithShared($totalsQuery, $user, ProveedorServicio::class);
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

        return Inertia::render('ProveedorServicios/Index', [
            'servicios' => $serviciosPaginated,
            'experienceTotals' => $experienceTotals,
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
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;

        $query = ProveedorServicio::query()->active();

        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }
        $query = $this->applyExportRoleFilter($query, $user, $request);
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                    ->orWhere('entidad', 'like', '%' . $request->search . '%')
                    ->orWhere('especialidad', 'like', '%' . $request->search . '%')
                    ->orWhere('cliente', 'like', '%' . $request->search . '%')
                    ->orWhere('objeto_del_contrato', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('tipo')) {
            $query->where('categoria', $request->tipo);
        }
        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        $rows = $query->orderBy('id')->get();
        $filename = 'proveedor-servicios_' . date('Y-m-d_H-i-s') . '.xlsx';

        return Excel::download(new ProveedorServiciosExport($rows), $filename);
    }

    public function exportProject(ProveedorServicio $proveedorServicio)
    {
        $this->assertCanExportOwnedRecord($proveedorServicio, auth()->user());
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
        $request->validate([
            'cliente' => 'required|string|max:500',
            'objeto_del_contrato' => 'required|string',
            'numero_contrato_os_comprobante' => 'required|string|max:255',
            'fecha_inicio' => 'required|string',
            'fecha_suspension' => 'nullable|string',
            'fecha_reinicio' => 'nullable|string',
            'fecha_culminacion' => 'required|string',
            'monto_neto' => 'required|numeric|min:0.01',
            'archivo_contrato' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'tipo_documento_adjunto' => 'nullable|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO,OTROS',
            'archivo_suspension' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'archivo_reinicio' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'documentos' => 'required|array|min:1',
            'documentos.*.tipo_documento_adjunto' => 'required|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO,OTROS',
            'documentos.*.nombre_otro' => 'nullable|string|max:255',
            'documentos.*.archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:25600',
        ]);

        $data = $this->prepareExperienciaDataServicios($request, null);
        $data['user_id'] = auth()->id();
        $data['titulo'] = $data['titulo'] ?? ($data['objeto_del_contrato'] ? \Str::limit($data['objeto_del_contrato'], 100) : 'Servicio');
        $data['entidad'] = $data['entidad'] ?? $data['cliente'];
        $data['estado'] = $data['estado'] ?? 'En Curso';

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
        $this->storeDocumentosFromRequest($request, $servicio);
        $this->recalculateMontoAcumuladoServicios($servicio->folder_id);
        return redirect()->route('proveedor-servicios.index', $servicio->folder_id ? ['folder_id' => $servicio->folder_id] : [])->with('success', 'Registro creado.');
    }

    private function prepareExperienciaDataServicios(Request $request, $model): array
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

        $basePath = 'expedientes/proveedor_servicios/experiencia';
        $firstDoc = $request->input('documentos.0.tipo_documento_adjunto');
        if ($firstDoc) {
            $data['tipo_documento_adjunto'] = $firstDoc;
        } else {
            $data['tipo_documento_adjunto'] = $request->input('tipo_documento_adjunto');
        }
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

    private function storeDocumentosFromRequest(Request $request, ProveedorServicio $servicio): void
    {
        $documentos = $request->input('documentos', []);
        $basePath = 'expedientes/proveedor_servicios/experiencia/adjuntos';
        foreach ($documentos as $index => $doc) {
            if (!$request->hasFile("documentos.$index.archivo")) {
                continue;
            }
            $file = $request->file("documentos.$index.archivo");
            $tipo = $doc['tipo_documento_adjunto'] ?? 'DOCUMENTO';
            $nombreOtro = trim((string) ($doc['nombre_otro'] ?? ''));
            $nombre = $tipo === 'OTROS' && $nombreOtro !== '' ? $nombreOtro : str_replace('_', ' ', $tipo);
            ProveedorServicioDocumento::create([
                'proveedor_servicio_id' => $servicio->id,
                'nombre' => $nombre,
                'file_path' => $file->store($basePath, 'r2'),
            ]);
        }
    }

    private function recalculateMontoAcumuladoServicios($folderId): void
    {
        $query = ProveedorServicio::query()->where('anulado', false)->orderBy('id');
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

    public function edit(ProveedorServicio $proveedorServicio)
    {
        $user = auth()->user();
        if (!$this->canEdit($proveedorServicio, $user)) {
            return redirect()->route('proveedor-servicios.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        $s = $proveedorServicio;
        $s->load('documentos');
        $servicio = [
            'id' => $s->id,
            'folder_id' => $s->folder_id,
            'clasificacion' => $s->clasificacion,
            'cliente' => $s->cliente,
            'objeto_del_contrato' => $s->objeto_del_contrato,
            'numero_contrato_os_comprobante' => $s->numero_contrato_os_comprobante,
            'fecha_inicio' => $s->fecha_inicio?->format('Y-m-d'),
            'fecha_suspension' => $s->fecha_suspension?->format('Y-m-d'),
            'fecha_reinicio' => $s->fecha_reinicio?->format('Y-m-d'),
            'fecha_culminacion' => $s->fecha_culminacion?->format('Y-m-d'),
            'total_meses' => $s->total_meses,
            'total_dias' => $s->total_dias,
            'traslape' => $s->traslape,
            'total_dias_sin_traslape' => $s->total_dias_sin_traslape,
            'monto_neto' => $s->monto_neto,
            'monto_acumulado' => $s->monto_acumulado,
            'archivo_contrato' => $s->archivo_contrato,
            'tipo_documento_adjunto' => $s->tipo_documento_adjunto,
            'archivo_contrato_url' => $s->archivo_contrato_url,
            'archivo_suspension_url' => $s->archivo_suspension_url,
            'archivo_reinicio_url' => $s->archivo_reinicio_url,
            'documentos_existentes' => $s->documentos->map(fn ($d) => [
                'id' => $d->id,
                'nombre' => $d->nombre,
                'url' => $d->url,
            ])->values(),
            'documentos' => [['tipo_documento_adjunto' => '', 'nombre_otro' => '', 'archivo' => null]],
        ];
        return Inertia::render('ProveedorServicios/Edit', [
            'servicio' => $servicio,
        ]);
    }

    public function update(Request $request, ProveedorServicio $proveedorServicio)
    {
        $user = auth()->user();
        if (!$this->canEdit($proveedorServicio, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $request->validate([
            'cliente' => 'required|string|max:500',
            'objeto_del_contrato' => 'required|string',
            'numero_contrato_os_comprobante' => 'required|string|max:255',
            'fecha_inicio' => 'required|string',
            'fecha_suspension' => 'nullable|string',
            'fecha_reinicio' => 'nullable|string',
            'fecha_culminacion' => 'required|string',
            'monto_neto' => 'required|numeric|min:0.01',
            'archivo_contrato' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'tipo_documento_adjunto' => 'nullable|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO,OTROS',
            'archivo_suspension' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'archivo_reinicio' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'documentos' => 'nullable|array',
            'documentos.*.tipo_documento_adjunto' => 'required_with:documentos.*.archivo|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO,OTROS',
            'documentos.*.nombre_otro' => 'nullable|string|max:255',
            'documentos.*.archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
        ]);

        $data = $this->prepareExperienciaDataServicios($request, $proveedorServicio);
        $proveedorServicio->update($data);
        $this->storeDocumentosFromRequest($request, $proveedorServicio);
        $this->recalculateMontoAcumuladoServicios($proveedorServicio->folder_id);
        return redirect()->route('proveedor-servicios.index', $proveedorServicio->folder_id ? ['folder_id' => $proveedorServicio->folder_id] : [])->with('success', 'Registro actualizado.');
    }

    public function destroy(ProveedorServicio $proveedorServicio)
    {
        $user = auth()->user();
        if (!$this->canDelete($proveedorServicio, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        $folderId = $proveedorServicio->folder_id;
        $proveedorServicio->load('documentos');
        foreach ($proveedorServicio->documentos as $doc) {
            if ($doc->file_path) {
                Storage::disk(storage_disk_for_path($doc->file_path))->delete($doc->file_path);
            }
        }
        foreach (['archivo_contrato', 'archivo_suspension', 'archivo_reinicio'] as $field) {
            $path = $proveedorServicio->$field ?? null;
            if ($path) {
                Storage::disk(storage_disk_for_path($path))->delete($path);
            }
        }
        $proveedorServicio->delete();
        $this->recalculateMontoAcumuladoServicios($folderId);
        return redirect()->route('proveedor-servicios.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro eliminado.');
    }

    public function move(Request $request, ProveedorServicio $proveedorServicio)
    {
        return $this->moveItem($request, $proveedorServicio, self::MODULE, 'proveedor-servicios.index');
    }

    public function moveBulk(Request $request)
    {
        return $this->moveBulkItems($request, ProveedorServicio::class, self::MODULE, 'item_ids', 'proveedor-servicios.index');
    }
}
