<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
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
            $query->where(function($q) use ($request) {
                $q->where('nombre_candidato', 'like', '%' . $request->search . '%')
                  ->orWhere('especialidad', 'like', '%' . $request->search . '%');
            });
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
            ? Curriculum::where('anulado', true)
                ->when($folderId, fn ($q) => $q->where('folder_id', $folderId))
                ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
                ->latest()->get()
            : collect();

        return Inertia::render('Cvs/Index', [
            'cvs' => $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id'])),
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
        return Inertia::render('Cvs/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_candidato' => 'required|string|max:255',
            'especialidad' => 'required|string|max:255',
            'archivo_cv' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $path = null;
        if ($request->hasFile('archivo_cv')) {
            $path = $request->file('archivo_cv')->store('cvs', 'public');
        }

        $data = [
            'user_id' => auth()->id(),
            'nombre_candidato' => $validated['nombre_candidato'],
            'especialidad' => $validated['especialidad'],
            'archivo_cv' => $path,
        ];
        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $data['folder_id'] = $folder->id;
            }
        }

        $cv = Curriculum::create($data);
        $folderId = $cv->folder_id;
        return redirect()->route('cvs.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'CV registrado.');
    }

    public function edit(Curriculum $cv)
    {
        return Inertia::render('Cvs/Edit', [
            'cv' => $cv
        ]);
    }

    public function update(Request $request, Curriculum $cv)
    {
         $validated = $request->validate([
            'nombre_candidato' => 'required|string|max:255',
            'especialidad' => 'required|string|max:255',
            'archivo_cv' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        if ($request->hasFile('archivo_cv')) {
            if ($cv->archivo_cv) {
                Storage::disk('public')->delete($cv->archivo_cv);
            }
            $cv->archivo_cv = $request->file('archivo_cv')->store('cvs', 'public');
        }

        $cv->update([
            'nombre_candidato' => $validated['nombre_candidato'],
            'especialidad' => $validated['especialidad'],
        ]);

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
     * Descarga el PDF de un CV (individual).
     */
    public function download(Curriculum $cv)
    {
        $user = auth()->user();
        if (!$cv->archivo_cv || !Storage::disk('public')->exists($cv->archivo_cv)) {
            return redirect()->back()->with('error', 'El archivo no existe.');
        }
        if ($user->role === 'Visualizador') {
            $allowedIds = $user->allowed_folders['cvs'] ?? [];
            if (!in_array($cv->folder_id, $allowedIds) && $cv->folder_id !== null) {
                abort(403, 'No tienes permiso para descargar este CV.');
            }
        } else {
            $query = Curriculum::query()->activo()->where('id', $cv->id);
            $query = $this->applyRoleBasedFilter($query, $user);
            if ($query->doesntExist()) {
                abort(403, 'No tienes permiso para descargar este CV.');
            }
        }
        $name = \Str::slug($cv->nombre_candidato) . '-cv.pdf';
        return Storage::disk('public')->download($cv->archivo_cv, $name);
    }

    /**
     * Descarga uno o más CV en ZIP (seleccionados o todos en la carpeta/lista).
     */
    public function downloadZip(Request $request)
    {
        $user = auth()->user();
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;
        $ids = $request->input('ids', []);
        $all = $request->boolean('all');

        $query = Curriculum::query()->activo()->whereNotNull('archivo_cv');
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
            $cvs = $query->get();
        } else {
            if (!is_array($ids) || empty($ids)) {
                return redirect()->back()->with('error', 'Seleccione al menos un CV.');
            }
            $cvs = $query->whereIn('id', $ids)->get();
        }

        $filesToZip = [];
        foreach ($cvs as $cv) {
            if (Storage::disk('public')->exists($cv->archivo_cv)) {
                $filesToZip[] = [
                    'path' => Storage::disk('public')->path($cv->archivo_cv),
                    'name' => \Str::slug($cv->nombre_candidato) . '-cv.pdf',
                ];
            }
        }

        if (empty($filesToZip)) {
            return redirect()->back()->with('error', 'No hay archivos PDF para descargar.');
        }

        $usedNames = [];
        $zip = new ZipArchive();
        $zipPath = storage_path('app/public/cvs_temp_' . uniqid() . '.zip');
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return redirect()->back()->with('error', 'No se pudo crear el archivo ZIP.');
        }
        foreach ($filesToZip as $item) {
            $base = pathinfo($item['name'], PATHINFO_FILENAME);
            $ext = pathinfo($item['name'], PATHINFO_EXTENSION) ?: 'pdf';
            if (!isset($usedNames[$base])) {
                $usedNames[$base] = 0;
            }
            $usedNames[$base]++;
            $fileName = $usedNames[$base] === 1 ? $item['name'] : $base . '-' . $usedNames[$base] . '.' . $ext;
            $zip->addFile($item['path'], $fileName);
        }
        $zip->close();

        $downloadName = 'cvs-' . ($folderId ? 'carpeta-' . $folderId . '-' : '') . now()->format('Y-m-d-His') . '.zip';
        return response()->download($zipPath, $downloadName, ['Content-Type' => 'application/zip'])->deleteFileAfterSend(true);
    }
}
