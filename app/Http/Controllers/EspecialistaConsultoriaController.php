<?php

namespace App\Http\Controllers;

use App\Models\EspecialistaConsultoria;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;

class EspecialistaConsultoriaController extends Controller
{
    use HasRoleBasedAccess;

    const MODULE = 'especialistas-consultoria';

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
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('especialidad', 'like', '%' . $request->search . '%');
            });
        }
        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        $especialistas = $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id']));
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
            'filters' => $request->only(['search', 'tipo', 'user_id', 'folder_id']),
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
            'traslape' => 'nullable|numeric|min:0',
            'monto_neto' => 'required|numeric|min:0.01',
            'archivo_contrato' => 'required|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'tipo_documento_adjunto' => 'required|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO',
        ]);

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
        $traslape = (float) ($request->input('traslape') ?? 0);
        $totalDiasSinTraslape = $totalDias !== null ? max(0, (int) round($totalDias - $traslape)) : null;

        $data = [
            'cliente' => $request->input('cliente'),
            'objeto_del_contrato' => $request->input('objeto_del_contrato'),
            'cui' => $request->input('cui'),
            'numero_contrato_os_comprobante' => $request->input('numero_contrato_os_comprobante'),
            'fecha_inicio' => $fechaInicio,
            'fecha_suspension' => parse_fecha_dd_mm_yyyy($request->input('fecha_suspension')),
            'fecha_reinicio' => parse_fecha_dd_mm_yyyy($request->input('fecha_reinicio')),
            'fecha_culminacion' => $fechaCulminacion,
            'total_meses' => $totalMeses,
            'total_dias' => $totalDias,
            'traslape' => $traslape,
            'total_dias_sin_traslape' => $totalDiasSinTraslape,
            'monto_neto' => $request->input('monto_neto') !== null && $request->input('monto_neto') !== '' ? (float) preg_replace('/[^\d.]/', '', $request->input('monto_neto')) : null,
        ];

        $basePath = 'expedientes/especialistas_consultoria';
        if ($request->hasFile('archivo_contrato')) {
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
        return $data;
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
            'traslape' => 'nullable|numeric|min:0',
            'monto_neto' => 'required|numeric|min:0.01',
            'archivo_contrato' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:25600',
            'tipo_documento_adjunto' => 'nullable|string|in:CONTRATO,COMPROBANTE_DE_PAGO,CONFORMIDAD_DE_SERVICIO',
        ]);

        $data = $this->prepareExperienciaData($request, $especialistaConsultoria);
        if ($request->hasFile('documento')) {
            if ($especialistaConsultoria->documento) {
                Storage::disk(storage_disk_for_path($especialistaConsultoria->documento))->delete($especialistaConsultoria->documento);
            }
            $data['documento'] = $request->file('documento')->store('expedientes/especialistas_consultoria', 'r2');
        }
        $especialistaConsultoria->update($data);
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
        if ($especialistaConsultoria->documento) {
            Storage::disk(storage_disk_for_path($especialistaConsultoria->documento))->delete($especialistaConsultoria->documento);
        }
        foreach (['archivo_contrato'] as $field) {
            $path = $especialistaConsultoria->$field ?? null;
            if ($path) {
                Storage::disk(storage_disk_for_path($path))->delete($path);
            }
        }
        $especialistaConsultoria->delete();
        $this->recalculateMontoAcumulado(EspecialistaConsultoria::class, $folderId);
        return redirect()->route('especialistas-consultoria.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro eliminado.');
    }
}
