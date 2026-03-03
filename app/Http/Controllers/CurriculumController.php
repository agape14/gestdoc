<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use App\Models\CurriculumFile;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Traits\HasRoleBasedAccess;
use ZipArchive;

class CurriculumController extends Controller
{
    use HasRoleBasedAccess;

    const MODULE = 'cvs';

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

        $query = Curriculum::query()->activo();
        $query = $this->applyRoleBasedFilter($query, $user);
        if ($user->role === 'Visualizador') {
            $allowedIds = $user->allowed_folders['cvs'] ?? [];
            if (empty($allowedIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($allowedIds) {
                    $q->whereIn('folder_id', $allowedIds)->orWhereNull('folder_id');
                });
            }
        }
        if ($request->filled('user_id') && $user->role === 'Administrador') {
            $query->where('user_id', $request->user_id);
        }
        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }

        if ($request->filled('search')) {
            $query->where('nombre_candidato', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        $anulados = $user->role === 'Administrador'
            ? Curriculum::with('files')->where('anulado', true)
                ->when($folderId, fn ($q) => $q->where('folder_id', $folderId))
                ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
                ->latest()->get()
            : collect();

        return Inertia::render('Cvs/Index', [
            'cvs' => $query->with('files')->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id'])),
            'filters' => $request->only(['search', 'especialidad', 'date_start', 'date_end', 'folder_id', 'user_id']),
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'breadcrumb' => $breadcrumb,
            'userRole' => $user->role,
            'operadores' => $operadores,
            'anulados' => $anulados,
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
        $operadores = auth()->user()->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name'])
            : [];

        return Inertia::render('Cvs/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
            'operadores' => $operadores,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_candidato' => 'required|string|max:255',
            'folder_id' => 'nullable|exists:folders,id',
        ]);

        $archivos = $this->parseArchivosFromRequest($request);
        if (empty($archivos)) {
            return redirect()->back()->withErrors(['archivos' => 'Debe adjuntar al menos un archivo PDF con su nombre.'])->withInput();
        }

        $data = [
            'user_id' => auth()->id(),
            'nombre_candidato' => $validated['nombre_candidato'],
            'especialidad' => null,
        ];
        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $data['folder_id'] = $folder->id;
            }
        }

        $cv = Curriculum::create($data);

        foreach ($archivos as $index => $item) {
            $request->validate([
                "archivos.{$index}.file" => 'required|file|mimes:pdf|max:10240',
            ], [], ["archivos.{$index}.file" => 'archivo PDF']);
            $nombre = \Str::limit($item['nombre_archivo'], 255);
            $path = $item['file']->store('expedientes/cvs', 'r2');
            CurriculumFile::create([
                'curriculum_id' => $cv->id,
                'nombre_archivo' => $nombre,
                'path' => $path,
                'orden' => $index,
            ]);
        }

        $folderId = $cv->folder_id;
        return redirect()->route('cvs.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'CV registrado.');
    }

    public function edit(Curriculum $cv)
    {
        $cv->load('files');
        $operadores = auth()->user()->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name'])
            : [];

        return Inertia::render('Cvs/Edit', [
            'cv' => $cv,
            'operadores' => $operadores,
        ]);
    }

    public function update(Request $request, Curriculum $cv)
    {
        $validated = $request->validate([
            'nombre_candidato' => 'required|string|max:255',
            'archivos_existentes' => 'nullable|array',
            'archivos_existentes.*.id' => 'required|exists:curriculum_files,id',
            'archivos_existentes.*.nombre_archivo' => 'required|string|max:255',
            'archivos' => 'nullable|array',
            'archivos.*.nombre_archivo' => 'required_with:archivos.*.file|string|max:255',
            'archivos.*.file' => 'nullable|file|mimes:pdf|max:10240',
        ], [], [
            'archivos.*.nombre_archivo' => 'nombre del archivo',
            'archivos.*.file' => 'archivo PDF',
        ]);

        $cv->update(['nombre_candidato' => $validated['nombre_candidato']]);

        if (!empty($validated['archivos_existentes'])) {
            foreach ($validated['archivos_existentes'] as $item) {
                $f = CurriculumFile::where('curriculum_id', $cv->id)->find($item['id']);
                if ($f) {
                    $f->update(['nombre_archivo' => $item['nombre_archivo']]);
                }
            }
        }

        $archivos = $request->input('archivos', []);
        $orden = $cv->files()->max('orden') ?? -1;
        foreach ($archivos as $index => $item) {
            $file = $request->file("archivos.{$index}.file");
            if (!$file) {
                continue;
            }
            $orden++;
            $path = $file->store('expedientes/cvs', 'r2');
            CurriculumFile::create([
                'curriculum_id' => $cv->id,
                'nombre_archivo' => $item['nombre_archivo'] ?? $file->getClientOriginalName(),
                'path' => $path,
                'orden' => $orden,
            ]);
        }

        $folderId = $cv->folder_id;
        return redirect()->route('cvs.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'CV actualizado.');
    }

    public function destroy(Request $request)
    {
        $id = (int) ($request->route('cv') ?? $request->segment(2));
        if ($id < 1) {
            return redirect()->back()->with('error', 'Registro no válido.');
        }
        $registro = Curriculum::find($id);
        if (!$registro) {
            return redirect()->back()->with('error', 'Registro no encontrado.');
        }
        $user = auth()->user();
        if (!$this->canDelete($registro, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para anular este registro.');
        }
        DB::table('curricula')->where('id', $id)->update(['anulado' => 1, 'updated_at' => now()]);
        return redirect()->back()->with('success', 'CV anulado.');
    }

    /**
     * Descarga el primer PDF del CV (compatibilidad con enlace antiguo).
     */
    public function download(Curriculum $cv)
    {
        $cv->load('files');
        $file = $cv->files->first();
        if ($file) {
            return $this->downloadFile($cv, $file);
        }
        if ($cv->archivo_cv) {
            $disk = storage_disk_for_path($cv->archivo_cv);
            if (Storage::disk($disk)->exists($cv->archivo_cv)) {
                $this->authorizeDownload($cv);
                return Storage::disk($disk)->download($cv->archivo_cv, \Str::slug($cv->nombre_candidato) . '-cv.pdf');
            }
        }
        return redirect()->back()->with('error', 'El archivo no existe.');
    }

    /**
     * Descarga un archivo PDF específico del CV (por nombre indicado).
     */
    public function downloadFile(Curriculum $cv, CurriculumFile $file)
    {
        if ($file->curriculum_id !== $cv->id) {
            abort(404);
        }
        $disk = storage_disk_for_path($file->path);
        if (!Storage::disk($disk)->exists($file->path)) {
            return redirect()->back()->with('error', 'El archivo no existe.');
        }
        $this->authorizeDownload($cv);
        $name = \Str::slug($file->nombre_archivo) . '.pdf';
        return Storage::disk($disk)->download($file->path, $name);
    }

    private function authorizeDownload(Curriculum $cv): void
    {
        $user = auth()->user();
        if ($user->role === 'Visualizador') {
            $allowedIds = $user->allowed_folders['cvs'] ?? [];
            if (!in_array($cv->folder_id, $allowedIds) && $cv->folder_id !== null) {
                abort(403, 'No tienes permiso para descargar este CV.');
            }
            return;
        }
        $query = Curriculum::query()->activo()->where('id', $cv->id);
        $query = $this->applyRoleBasedFilter($query, $user);
        if ($query->doesntExist()) {
            abort(403, 'No tienes permiso para descargar este CV.');
        }
    }

    /**
     * Descarga uno o más CV en ZIP (seleccionados o todos). Los archivos se nombran con el nombre indicado en el formulario.
     */
    public function downloadZip(Request $request)
    {
        $user = auth()->user();
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;
        $ids = $request->input('ids', []);
        $all = $request->boolean('all');

        $query = Curriculum::query()->activo();
        $query = $this->applyRoleBasedFilter($query, $user);
        if ($user->role === 'Visualizador') {
            $allowedIds = $user->allowed_folders['cvs'] ?? [];
            if (empty($allowedIds)) {
                $query->whereRaw('1 = 0');
            } else {
                $query->where(function ($q) use ($allowedIds) {
                    $q->whereIn('folder_id', $allowedIds)->orWhereNull('folder_id');
                });
            }
        }
        if ($folderId) {
            $query->where('folder_id', $folderId);
        } else {
            $query->whereNull('folder_id');
        }

        if ($all) {
            $cvs = $query->with('files')->get();
        } else {
            if (!is_array($ids) || empty($ids)) {
                return redirect()->back()->with('error', 'Seleccione al menos un CV.');
            }
            $cvs = $query->with('files')->whereIn('id', $ids)->get();
        }

        $filesToZip = [];
        foreach ($cvs as $cv) {
            foreach ($cv->files as $file) {
                $disk = storage_disk_for_path($file->path);
                if (!Storage::disk($disk)->exists($file->path)) {
                    continue;
                }
                if ($disk === 'public') {
                    $filesToZip[] = [
                        'path' => Storage::disk('public')->path($file->path),
                        'nombre_archivo' => $file->nombre_archivo,
                    ];
                } else {
                    $content = Storage::disk($disk)->get($file->path);
                    $tmpPath = storage_path('app/cvs_temp_' . uniqid() . '.pdf');
                    file_put_contents($tmpPath, $content);
                    $filesToZip[] = [
                        'path' => $tmpPath,
                        'nombre_archivo' => $file->nombre_archivo,
                        'temp' => true,
                    ];
                }
            }
            if ($cv->files->isEmpty() && $cv->archivo_cv) {
                $disk = storage_disk_for_path($cv->archivo_cv);
                if (Storage::disk($disk)->exists($cv->archivo_cv)) {
                    if ($disk === 'public') {
                        $filesToZip[] = [
                            'path' => Storage::disk('public')->path($cv->archivo_cv),
                            'nombre_archivo' => $cv->nombre_candidato ?: 'CV',
                        ];
                    } else {
                        $content = Storage::disk($disk)->get($cv->archivo_cv);
                        $tmpPath = storage_path('app/cvs_temp_' . uniqid() . '.pdf');
                        file_put_contents($tmpPath, $content);
                        $filesToZip[] = [
                            'path' => $tmpPath,
                            'nombre_archivo' => $cv->nombre_candidato ?: 'CV',
                            'temp' => true,
                        ];
                    }
                }
            }
        }

        if (empty($filesToZip)) {
            return redirect()->back()->with('error', 'No hay archivos PDF para descargar.');
        }

        $usedBaseNames = [];
        $zip = new ZipArchive();
        $zipPath = storage_path('app/public/cvs_temp_' . uniqid() . '.zip');
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'No se pudo crear el archivo ZIP.');
        }
        foreach ($filesToZip as $item) {
            $baseName = \Str::slug($item['nombre_archivo']);
            if (empty($baseName)) {
                $baseName = 'documento';
            }
            $ext = 'pdf';
            if (!isset($usedBaseNames[$baseName])) {
                $usedBaseNames[$baseName] = 0;
            }
            $usedBaseNames[$baseName]++;
            $fileName = $usedBaseNames[$baseName] === 1 ? $baseName . '.' . $ext : $baseName . '-' . $usedBaseNames[$baseName] . '.' . $ext;
            $zip->addFile($item['path'], $fileName);
        }
        $zip->close();

        foreach ($filesToZip as $item) {
            if (!empty($item['temp']) && file_exists($item['path'])) {
                @unlink($item['path']);
            }
        }

        $downloadName = 'cvs-' . ($folderId ? 'carpeta-' . $folderId . '-' : '') . now()->format('Y-m-d-His') . '.zip';
        return response()->download($zipPath, $downloadName, ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
    }

    private function parseArchivosFromRequest(Request $request): array
    {
        $out = [];
        $input = $request->input('archivos');
        if (is_array($input)) {
            foreach ($input as $index => $item) {
                $nombre = is_array($item) ? ($item['nombre_archivo'] ?? '') : '';
                $file = $request->file("archivos.{$index}.file") ?? $request->file("archivos.{$index}");
                if ($nombre !== '' && $file) {
                    $out[$index] = ['nombre_archivo' => $nombre, 'file' => $file];
                }
            }
        }
        if (empty($out)) {
            for ($i = 0; $i < 20; $i++) {
                $nombre = $request->input("archivos.{$i}.nombre_archivo");
                $file = $request->file("archivos.{$i}.file") ?? $request->file("archivos.{$i}");
                if ($nombre !== null && $nombre !== '' && $file) {
                    $out[$i] = ['nombre_archivo' => $nombre, 'file' => $file];
                }
            }
        }
        return $out;
    }
}
