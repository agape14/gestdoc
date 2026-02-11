<?php

namespace App\Http\Controllers;

use App\Models\Topografia;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use App\Traits\HasRoleBasedAccess;

class TopografiaController extends Controller
{
    use HasRoleBasedAccess;

    const MODULE = 'topografia';

    public function index(Request $request)
    {
        $user = auth()->user();
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;
        $effectiveUserId = $user->role === 'Administrador'
            ? ($request->filled('user_id') ? (int) $request->user_id : null)
            : $user->id;

        if ($folderId) {
            $currentFolder = Folder::where('module', self::MODULE)
                ->forEffectiveUser($effectiveUserId)
                ->findOrFail($folderId);
            $currentFolder->load(['parent']);
            $folders = $currentFolder->children()->forEffectiveUser($effectiveUserId)->orderBy('name')->get();
            $breadcrumb = $currentFolder->path;
        } else {
            $currentFolder = null;
            $folders = Folder::whereNull('parent_id')
                ->visibleForUser(self::MODULE, $effectiveUserId)
                ->orderBy('name')
                ->get();
            $breadcrumb = [];
        }

        $query = Topografia::query()->activo();
        $query = $this->applyRoleBasedFilter($query, $user);
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
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
            });
        }

        $operadores = $user->role === 'Administrador'
            ? \App\Models\User::where('role', 'Operador')->orderBy('name')->get(['id', 'name', 'email'])
            : collect();

        $anulados = $user->role === 'Administrador'
            ? Topografia::where('anulado', true)
                ->when($folderId, fn ($q) => $q->where('folder_id', $folderId))
                ->when($request->filled('user_id'), fn ($q) => $q->where('user_id', $request->user_id))
                ->latest()->get()
            : collect();

        return Inertia::render('Topografia/Index', [
            'items' => $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id'])),
            'filters' => $request->only(['search', 'folder_id', 'user_id']),
            'userRole' => $user->role,
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'breadcrumb' => $breadcrumb,
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
        return Inertia::render('Topografia/Create', [
            'folderId' => $folderId,
            'breadcrumbLabel' => $breadcrumbLabel,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['archivo']);
        $data['user_id'] = auth()->id();
        if ($request->filled('folder_id')) {
            $folder = Folder::where('module', self::MODULE)->find($request->folder_id);
            if ($folder) {
                $data['folder_id'] = $folder->id;
            }
        }

        if ($request->hasFile('archivo')) {
            $data['archivo'] = $request->file('archivo')->store('topografia', 'public');
        }

        $item = Topografia::create($data);
        $folderId = $item->folder_id;
        return redirect()->route('topografia.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro creado.');
    }

    public function edit(Topografia $topografia)
    {
        $user = auth()->user();
        if (!$this->canEdit($topografia, $user)) {
            return redirect()->route('topografia.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('Topografia/Edit', [
            'item' => $topografia
        ]);
    }

    public function update(Request $request, Topografia $topografia)
    {
        $user = auth()->user();
        if (!$this->canEdit($topografia, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['archivo']);

        if ($request->hasFile('archivo')) {
            if ($topografia->archivo) {
                Storage::disk('public')->delete($topografia->archivo);
            }
            $data['archivo'] = $request->file('archivo')->store('topografia', 'public');
        }

        $topografia->update($data);

        return redirect()->route('topografia.index')->with('success', 'Registro actualizado.');
    }

    public function destroy(Request $request)
    {
        $id = (int) ($request->route('topografium') ?? $request->segment(2));
        if ($id < 1) {
            return redirect()->back()->with('error', 'Registro no válido.');
        }
        $registro = Topografia::find($id);
        if (!$registro) {
            return redirect()->back()->with('error', 'Registro no encontrado.');
        }
        $user = auth()->user();
        if (!$this->canDelete($registro, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para anular este registro.');
        }
        DB::table('topografias')->where('id', $id)->update(['anulado' => 1, 'updated_at' => now()]);
        return redirect()->back()->with('success', 'Registro anulado.');
    }
}
