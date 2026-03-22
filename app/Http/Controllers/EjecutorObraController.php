<?php

namespace App\Http\Controllers;

use App\Models\EjecutorObra;
use App\Models\EjecutorObraDocumento;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;
use App\Traits\MovesToFolder;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EjecutorObrasExport;
use App\Http\Requests\StoreEjecutorObraRequest;
use App\Http\Requests\UpdateEjecutorObraRequest;

class EjecutorObraController extends Controller
{
    use HasRoleBasedAccess, MovesToFolder;

    const MODULE = 'ejecutor-obra';

    /** Carpetas base en R2 para archivos del módulo */
    const R2_BASE = 'ejecutor_obras';

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

        $query = EjecutorObra::query()->active();
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
                $q->where('nombre_sigla_entidad', 'like', '%' . $search . '%')
                    ->orWhere('nomenclatura', 'like', '%' . $search . '%')
                    ->orWhere('descripcion_objeto', 'like', '%' . $search . '%')
                    ->orWhere('cui', 'like', '%' . $search . '%')
                    ->orWhere('numero_contrato', 'like', '%' . $search . '%');
            });
        }

        $obrasPaginated = $query->with('documentosLiquidacion')->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id']));
        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        return Inertia::render('EjecutorObra/Index', [
            'obras' => $obrasPaginated,
            'filters' => $request->only(['search', 'user_id', 'folder_id']),
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
        $query = EjecutorObra::query()->active();
        $query = $this->applyExportRoleFilter($query, $user, $request);
        return Excel::download(new EjecutorObrasExport($query->get()), 'ejecutor-obras_' . date('Y-m-d_H-i-s') . '.xlsx');
    }

    public function exportProject(EjecutorObra $ejecutorObra)
    {
        $this->assertCanExportOwnedRecord($ejecutorObra, auth()->user());
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

    public function store(StoreEjecutorObraRequest $request)
    {
        $data = $request->validated();
        unset(
            $data['archivo_contrato'],
            $data['archivo_acta_recepcion'],
            $data['archivo_acta_inicio'],
            $data['archivo_acta_suspension'],
            $data['archivo_acta_reinicio'],
            $data['archivo_acta_entrega_terreno'],
            $data['archivo_acta_adicional'],
            $data['archivo_acta_deductivo'],
            $data['archivo_aprobacion_acto_resolutivo'],
            $data['tiene_suspension'],
        );
        unset($data['documentos']);

        $tieneSuspension = $this->isTieneSuspension($request);
        if (!$tieneSuspension) {
            $data['fecha_suspension'] = null;
            $data['fecha_reinicio'] = null;
            $data['archivo_acta_suspension'] = null;
            $data['archivo_acta_reinicio'] = null;
        }

        $this->applyEjecutorObraCondicionales($request, $data);

        $data['user_id'] = auth()->id();
        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $data['folder_id'] = $folder->id;
            }
        }
        $data['liquidado_recepcionado'] = filter_var($request->input('liquidado_recepcionado'), FILTER_VALIDATE_BOOLEAN);
        $this->computeMontoNeto($data);

        $obra = EjecutorObra::create($data);
        $this->storeArchivos($request, $obra, $tieneSuspension);
        $this->syncDocumentosLiquidacion($request, $obra);

        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : ($obra->folder_id ?? null);
        return redirect()->route('ejecutor-obra.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro creado.');
    }

    public function edit(EjecutorObra $ejecutorObra)
    {
        $user = auth()->user();
        if (!$this->canEdit($ejecutorObra, $user)) {
            return redirect()->route('ejecutor-obra.index')->with('error', 'No tienes permiso para editar este registro.');
        }
        $ejecutorObra->load('documentosLiquidacion');

        return Inertia::render('EjecutorObra/Edit', [
            'obra' => $ejecutorObra,
            'folderId' => $ejecutorObra->folder_id,
            'canDelete' => $this->canDelete($ejecutorObra, $user),
        ]);
    }

    public function update(UpdateEjecutorObraRequest $request, EjecutorObra $ejecutorObra)
    {
        $user = auth()->user();
        if (!$this->canEdit($ejecutorObra, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $data = $request->validated();
        unset(
            $data['archivo_contrato'],
            $data['archivo_acta_recepcion'],
            $data['archivo_acta_inicio'],
            $data['archivo_acta_suspension'],
            $data['archivo_acta_reinicio'],
            $data['archivo_acta_entrega_terreno'],
            $data['archivo_acta_adicional'],
            $data['archivo_acta_deductivo'],
            $data['archivo_aprobacion_acto_resolutivo'],
            $data['tiene_suspension'],
        );
        unset($data['documentos']);
        if (isset($data['documento_delete_ids'])) {
            unset($data['documento_delete_ids']);
        }

        $tieneSuspension = $this->isTieneSuspension($request);
        if (!$tieneSuspension) {
            $data['fecha_suspension'] = null;
            $data['fecha_reinicio'] = null;
            $data['archivo_acta_suspension'] = null;
            $data['archivo_acta_reinicio'] = null;
        }

        $this->applyEjecutorObraCondicionales($request, $data);

        $data['liquidado_recepcionado'] = filter_var($request->input('liquidado_recepcionado'), FILTER_VALIDATE_BOOLEAN);
        $this->computeMontoNeto($data);

        $pathsAntes = [
            'archivo_acta_adicional' => $ejecutorObra->archivo_acta_adicional,
            'archivo_acta_deductivo' => $ejecutorObra->archivo_acta_deductivo,
            'archivo_aprobacion_acto_resolutivo' => $ejecutorObra->archivo_aprobacion_acto_resolutivo,
        ];

        $ejecutorObra->fill($data);
        $ejecutorObra->save();

        $this->deleteArchivosSiDesactivaFlags($request, $pathsAntes);

        $ejecutorObra->refresh();
        $this->storeArchivos($request, $ejecutorObra, $tieneSuspension);
        $this->syncDocumentosLiquidacion($request, $ejecutorObra);

        return redirect()->back()->with('success', 'Registro actualizado.');
    }

    private function computeMontoNeto(array &$data): void
    {
        $monto = isset($data['monto_total']) ? (float) $data['monto_total'] : 0;
        $pct = isset($data['porcentaje_participacion']) ? (float) $data['porcentaje_participacion'] : 0;
        $data['monto_neto'] = $monto > 0 && $pct >= 0 ? round($monto * $pct / 100, 2) : null;
    }

    private function isTieneSuspension(Request $request): bool
    {
        $v = $request->input('tiene_suspension');
        return $v === 'SI' || $v === '1' || $v === true;
    }

    private function isRespuestaSi(?string $value): bool
    {
        return $value === 'SI' || $value === '1';
    }

    /**
     * Limpia fechas/montos/archivos en BD cuando la respuesta condicional es NO.
     */
    private function applyEjecutorObraCondicionales(Request $request, array &$data): void
    {
        if (!$this->isRespuestaSi($request->input('tiene_adicional_obra'))) {
            $data['fecha_adicional_obra'] = null;
            $data['monto_adicional'] = null;
            $data['plazo_adicional'] = null;
            $data['archivo_acta_adicional'] = null;
        }
        if (!$this->isRespuestaSi($request->input('tiene_deductivo_obra'))) {
            $data['fecha_deductivo_obra'] = null;
            $data['monto_deductivo'] = null;
            $data['plazo_deductivo'] = null;
            $data['archivo_acta_deductivo'] = null;
        }
        if (!$this->isRespuestaSi($request->input('tiene_aprobacion_acto_resolutivo'))) {
            $data['fecha_aprobacion_acto_resolutivo'] = null;
            $data['monto_aprobacion_acto_resolutivo'] = null;
            $data['plazo_aprobacion_acto_resolutivo'] = null;
            $data['archivo_aprobacion_acto_resolutivo'] = null;
        }
    }

    /**
     * Elimina del almacenamiento los PDFs de bloques que pasaron a NO (tras guardar null en BD).
     */
    private function deleteArchivosSiDesactivaFlags(Request $request, array $pathsAntes): void
    {
        $pairs = [
            'tiene_adicional_obra' => 'archivo_acta_adicional',
            'tiene_deductivo_obra' => 'archivo_acta_deductivo',
            'tiene_aprobacion_acto_resolutivo' => 'archivo_aprobacion_acto_resolutivo',
        ];
        foreach ($pairs as $flag => $field) {
            if (!$this->isRespuestaSi($request->input($flag)) && !empty($pathsAntes[$field])) {
                try {
                    Storage::disk(storage_disk_for_path($pathsAntes[$field]))->delete($pathsAntes[$field]);
                } catch (\Throwable $e) {
                    Log::warning('EjecutorObra deleteArchivosSiDesactivaFlags', ['field' => $field, 'message' => $e->getMessage()]);
                }
            }
        }
    }

    private function syncDocumentosLiquidacion(Request $request, EjecutorObra $obra): void
    {
        $deleteIds = $request->input('documento_delete_ids', []);
        if (is_array($deleteIds) && $deleteIds !== []) {
            $docs = EjecutorObraDocumento::query()
                ->where('ejecutor_obra_id', $obra->id)
                ->where('tipo', 'liquidacion')
                ->whereIn('id', $deleteIds)
                ->get();
            foreach ($docs as $doc) {
                try {
                    Storage::disk(storage_disk_for_path($doc->file_path))->delete($doc->file_path);
                } catch (\Throwable $e) {
                    Log::warning('EjecutorObra syncDocumentosLiquidacion delete', ['id' => $doc->id, 'message' => $e->getMessage()]);
                }
                $doc->delete();
            }
        }

        $documentos = $request->input('documentos', []);
        if (!is_array($documentos)) {
            return;
        }
        $basePath = 'expedientes/' . self::R2_BASE . '/resoluciones_liquidacion';
        foreach ($documentos as $index => $doc) {
            if (!$request->hasFile("documentos.{$index}.archivo")) {
                continue;
            }
            $nombre = is_array($doc) ? ($doc['nombre'] ?? '') : '';
            try {
                $file = $request->file("documentos.{$index}.archivo");
                $path = $file->store($basePath, 'r2');
                EjecutorObraDocumento::create([
                    'ejecutor_obra_id' => $obra->id,
                    'nombre' => $nombre !== '' ? $nombre : ($file->getClientOriginalName() ?: 'Documento'),
                    'file_path' => $path,
                    'tipo' => 'liquidacion',
                ]);
            } catch (\Throwable $e) {
                Log::error('EjecutorObra syncDocumentosLiquidacion upload', [
                    'ejecutor_obra_id' => $obra->id,
                    'index' => $index,
                    'message' => $e->getMessage(),
                ]);
                throw $e;
            }
        }
    }

    private function storeArchivos(Request $request, EjecutorObra $obra, bool $tieneSuspension): void
    {
        $base = 'expedientes/' . self::R2_BASE;
        $fileMap = [
            'archivo_contrato' => $base . '/contratos',
            'archivo_acta_recepcion' => $base . '/actas_recepcion',
            'archivo_acta_inicio' => $base . '/actas_inicio',
            'archivo_acta_suspension' => $base . '/actas_suspension',
            'archivo_acta_reinicio' => $base . '/actas_reinicio',
            'archivo_acta_entrega_terreno' => $base . '/actas_entrega_terreno',
            'archivo_acta_adicional' => $base . '/actas_adicional',
            'archivo_acta_deductivo' => $base . '/actas_deductivo',
            'archivo_aprobacion_acto_resolutivo' => $base . '/aprobaciones_acto_resolutivo',
        ];

        $flagPorArchivo = [
            'archivo_acta_adicional' => 'tiene_adicional_obra',
            'archivo_acta_deductivo' => 'tiene_deductivo_obra',
            'archivo_aprobacion_acto_resolutivo' => 'tiene_aprobacion_acto_resolutivo',
        ];

        $updates = [];
        foreach ($fileMap as $field => $pathPrefix) {
            if ($field === 'archivo_acta_suspension' || $field === 'archivo_acta_reinicio') {
                if (!$tieneSuspension && $obra->exists) {
                    if ($obra->$field) {
                        Storage::disk(storage_disk_for_path($obra->$field))->delete($obra->$field);
                        $updates[$field] = null;
                    }
                    continue;
                }
                if (!$tieneSuspension) {
                    continue;
                }
            }

            if (isset($flagPorArchivo[$field])) {
                if (!$this->isRespuestaSi($request->input($flagPorArchivo[$field]))) {
                    continue;
                }
            }

            if (!$request->hasFile($field)) {
                continue;
            }
            if ($obra->$field) {
                Storage::disk(storage_disk_for_path($obra->$field))->delete($obra->$field);
            }
            $path = $request->file($field)->store($pathPrefix, 'r2');
            $updates[$field] = $path;
        }
        if (!empty($updates)) {
            $obra->update($updates);
        }
    }

    public function destroy(EjecutorObra $ejecutorObra)
    {
        $user = auth()->user();
        if (!$this->canDelete($ejecutorObra, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para anular este registro.');
        }
        $ejecutorObra->update(['anulado' => true]);
        return redirect()->route('ejecutor-obra.index')->with('success', 'Registro anulado.');
    }

    public function move(Request $request, EjecutorObra $ejecutorObra)
    {
        return $this->moveItem($request, $ejecutorObra, self::MODULE, 'ejecutor-obra.index');
    }

    public function moveBulk(Request $request)
    {
        return $this->moveBulkItems($request, EjecutorObra::class, self::MODULE, 'item_ids', 'ejecutor-obra.index');
    }
}
