<?php

namespace App\Http\Controllers;

use App\Models\ProveedorBien;
use App\Models\ProveedorBienDocumento;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProveedorBienesExport;
use App\Traits\HasRoleBasedAccess;
use App\Traits\MovesToFolder;

class ProveedorBienController extends Controller
{
    use HasRoleBasedAccess, MovesToFolder;

    const MODULE = 'proveedor-bienes';

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

        $query = ProveedorBien::query()->active()->with('documentos');
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
                  ->orWhere('entidad', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('tipo')) {
            $query->where('categoria', $request->tipo);
        }

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        $bienes = $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id']));
        $totalsQuery = ProveedorBien::query()->active();
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

        return Inertia::render('ProveedorBienes/Index', [
            'bienes' => $bienes,
            'experienceTotals' => $experienceTotals,
            'filters' => $request->only(['search', 'tipo', 'user_id', 'folder_id']),
            'userRole' => $user->role,
            'operadores' => $operadores,
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'breadcrumb' => $breadcrumb,
        ]);
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;

        $query = ProveedorBien::query()->active();
        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }
        $query = $this->applyExportRoleFilter($query, $user, $request);
        if ($request->filled('search')) {
            $query->where(function ($q) use ($request) {
                $q->where('objeto_del_contrato', 'like', '%' . $request->search . '%')
                    ->orWhere('cliente', 'like', '%' . $request->search . '%')
                    ->orWhere('numero_contrato_oc_comprobante', 'like', '%' . $request->search . '%');
            });
        }

        $bienes = $query->orderBy('id')->get();
        $filename = 'proveedor-bienes_' . date('Y-m-d_H-i-s') . '.xlsx';
        return Excel::download(new ProveedorBienesExport($bienes), $filename);
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
        return Inertia::render('ProveedorBienes/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'cliente' => 'required|string|max:500',
            'objeto_del_contrato' => 'required|string',
            'numero_contrato_oc_comprobante' => 'required|string|max:255',
            'fecha_inicio' => 'required|string',
            'fecha_culminacion' => 'required|string',
            'monto_neto' => 'required|numeric|min:0.01',
            'archivo_contrato' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'tipo_documento_adjunto' => 'nullable|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO,OTROS',
            'documentos' => 'required|array|min:1',
            'documentos.*.tipo_documento_adjunto' => 'required|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO,OTROS',
            'documentos.*.nombre_otro' => 'nullable|string|max:255',
            'documentos.*.archivo' => 'required|file|mimes:pdf,jpg,jpeg,png|max:25600',
        ]);

        $data = $this->prepareExperienciaDataBienes($request, null);
        $data['user_id'] = auth()->id();
        $data['titulo'] = $data['titulo'] ?? ($data['objeto_del_contrato'] ? \Str::limit($data['objeto_del_contrato'], 100) : 'Bien');
        $data['entidad'] = $data['entidad'] ?? $data['cliente'];
        $data['estado'] = $data['estado'] ?? 'En Stock';

        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $folder->load(['parent']);
                $path = $folder->path;
                $pathLabel = is_array($path) ? implode(' / ', array_column($path, 'name')) : $folder->name;
                $data['folder_id'] = $folder->id;
                $data['clasificacion'] = $request->filled('clasificacion') ? $request->clasificacion : $pathLabel;
                $data['categoria'] = (stripos($pathLabel, 'PRIVAD') !== false) ? 'Privada' : 'Publica';
            }
        } else {
            $data['categoria'] = $data['categoria'] ?? 'Publica';
        }

        $bien = ProveedorBien::create($data);
        $this->storeDocumentosFromRequest($request, $bien);
        $this->recalculateMontoAcumuladoBienes($bien->folder_id);
        return redirect()->route('proveedor-bienes.index', $bien->folder_id ? ['folder_id' => $bien->folder_id] : [])->with('success', 'Registro creado.');
    }

    private function prepareExperienciaDataBienes(Request $request, $model): array
    {
        $fechaInicio = parse_fecha_dd_mm_yyyy($request->input('fecha_inicio'));
        $fechaCulminacion = parse_fecha_dd_mm_yyyy($request->input('fecha_culminacion'));
        $totalDias = null;
        if ($fechaInicio && $fechaCulminacion) {
            $totalDias = \Carbon\Carbon::parse($fechaInicio)->diffInDays(\Carbon\Carbon::parse($fechaCulminacion)) + 1;
        }
        $totalMeses = $totalDias !== null ? round($totalDias / 30, 2) : null;
        $traslape = 0;
        $totalDiasSinTraslape = $totalDias !== null ? max(0, (int) round($totalDias - $traslape)) : null;

        $data = [
            'cliente' => $request->input('cliente'),
            'objeto_del_contrato' => $request->input('objeto_del_contrato'),
            'numero_contrato_oc_comprobante' => $request->input('numero_contrato_oc_comprobante'),
            'fecha_inicio' => $fechaInicio,
            'fecha_culminacion' => $fechaCulminacion,
            'total_meses' => $totalMeses,
            'total_dias' => $totalDias,
            'traslape' => $traslape,
            'total_dias_sin_traslape' => $totalDiasSinTraslape,
            'monto_neto' => $request->input('monto_neto') !== null && $request->input('monto_neto') !== '' ? (float) preg_replace('/[^\d.]/', '', $request->input('monto_neto')) : null,
        ];

        $basePath = 'expedientes/proveedor_bienes/experiencia';
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
        return $data;
    }

    private function storeDocumentosFromRequest(Request $request, ProveedorBien $bien): void
    {
        $documentos = $request->input('documentos', []);
        $basePath = 'expedientes/proveedor_bienes/experiencia/adjuntos';
        foreach ($documentos as $index => $doc) {
            if (!$request->hasFile("documentos.$index.archivo")) {
                continue;
            }
            $file = $request->file("documentos.$index.archivo");
            $tipo = $doc['tipo_documento_adjunto'] ?? 'DOCUMENTO';
            $nombreOtro = trim((string) ($doc['nombre_otro'] ?? ''));
            $nombre = $tipo === 'OTROS' && $nombreOtro !== '' ? $nombreOtro : str_replace('_', ' ', $tipo);
            ProveedorBienDocumento::create([
                'proveedor_bien_id' => $bien->id,
                'nombre' => $nombre,
                'file_path' => $file->store($basePath, 'r2'),
            ]);
        }
    }

    private function recalculateMontoAcumuladoBienes($folderId): void
    {
        $query = ProveedorBien::query()->where('anulado', false)->orderBy('id');
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

    public function edit(ProveedorBien $proveedorBien)
    {
        $user = auth()->user();
        if (!$this->canEdit($proveedorBien, $user)) {
            return redirect()->route('proveedor-bienes.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        $b = $proveedorBien;
        $b->load('documentos');
        $bien = [
            'id' => $b->id,
            'folder_id' => $b->folder_id,
            'clasificacion' => $b->clasificacion,
            'cliente' => $b->cliente,
            'objeto_del_contrato' => $b->objeto_del_contrato,
            'numero_contrato_oc_comprobante' => $b->numero_contrato_oc_comprobante,
            'fecha_inicio' => $b->fecha_inicio?->format('Y-m-d'),
            'fecha_culminacion' => $b->fecha_culminacion?->format('Y-m-d'),
            'total_meses' => $b->total_meses,
            'total_dias' => $b->total_dias,
            'traslape' => $b->traslape,
            'total_dias_sin_traslape' => $b->total_dias_sin_traslape,
            'monto_neto' => $b->monto_neto,
            'monto_acumulado' => $b->monto_acumulado,
            'archivo_contrato' => $b->archivo_contrato,
            'tipo_documento_adjunto' => $b->tipo_documento_adjunto,
            'archivo_contrato_url' => $b->archivo_contrato_url,
            'documentos_existentes' => $b->documentos->map(fn ($d) => [
                'id' => $d->id,
                'nombre' => $d->nombre,
                'url' => storage_url_for_path($d->file_path),
            ])->values(),
            'documentos' => [['tipo_documento_adjunto' => '', 'nombre_otro' => '', 'archivo' => null]],
        ];
        return Inertia::render('ProveedorBienes/Edit', [
            'bien' => $bien,
        ]);
    }

    public function update(Request $request, ProveedorBien $proveedorBien)
    {
        $user = auth()->user();
        if (!$this->canEdit($proveedorBien, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $request->validate([
            'cliente' => 'required|string|max:500',
            'objeto_del_contrato' => 'required|string',
            'numero_contrato_oc_comprobante' => 'required|string|max:255',
            'fecha_inicio' => 'required|string',
            'fecha_culminacion' => 'required|string',
            'monto_neto' => 'required|numeric|min:0.01',
            'archivo_contrato' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'tipo_documento_adjunto' => 'nullable|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO,OTROS',
            'documentos' => 'nullable|array',
            'documentos.*.tipo_documento_adjunto' => 'required_with:documentos.*.archivo|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO,OTROS',
            'documentos.*.nombre_otro' => 'nullable|string|max:255',
            'documentos.*.archivo' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
        ]);

        $data = $this->prepareExperienciaDataBienes($request, $proveedorBien);
        $proveedorBien->update($data);
        $this->storeDocumentosFromRequest($request, $proveedorBien);
        $this->recalculateMontoAcumuladoBienes($proveedorBien->folder_id);
        return redirect()->route('proveedor-bienes.index', $proveedorBien->folder_id ? ['folder_id' => $proveedorBien->folder_id] : [])->with('success', 'Registro actualizado.');
    }

    public function destroy(ProveedorBien $proveedorBien)
    {
        $user = auth()->user();
        if (!$this->canDelete($proveedorBien, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        $folderId = $proveedorBien->folder_id;
        foreach (['archivo_contrato'] as $field) {
            $path = $proveedorBien->$field ?? null;
            if ($path) {
                Storage::disk(storage_disk_for_path($path))->delete($path);
            }
        }
        $proveedorBien->delete();
        $this->recalculateMontoAcumuladoBienes($folderId);
        return redirect()->route('proveedor-bienes.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro eliminado.');
    }

    public function move(Request $request, ProveedorBien $proveedorBien)
    {
        return $this->moveItem($request, $proveedorBien, self::MODULE, 'proveedor-bienes.index');
    }

    public function moveBulk(Request $request)
    {
        return $this->moveBulkItems($request, ProveedorBien::class, self::MODULE, 'item_ids', 'proveedor-bienes.index');
    }
}

