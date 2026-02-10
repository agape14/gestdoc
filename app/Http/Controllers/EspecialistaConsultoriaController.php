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

        return Inertia::render('EspecialistasConsultoria/Index', [
            'especialistas' => $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id', 'user_id'])),
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
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'especialidad' => 'nullable|string|max:255',
            'tipo' => 'required|string|in:Profesional,Empresa',
            'estado' => 'nullable|string',
            'documento' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'clasificacion' => 'nullable|string|max:500',
        ]);

        $data = $request->except(['documento']);
        $data['user_id'] = auth()->id();

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
            $data['documento'] = $request->file('documento')->store('especialistas_consultoria', 'public');
        }

        $especialista = EspecialistaConsultoria::create($data);
        $folderId = $especialista->folder_id;

        return redirect()->route('especialistas-consultoria.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro creado.');
    }

    public function edit(EspecialistaConsultoria $especialista_consultoria)
    {
        $user = auth()->user();
        if (!$this->canEdit($especialista_consultoria, $user)) {
            return redirect()->route('especialistas-consultoria.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('EspecialistasConsultoria/Edit', [
            'especialista' => $especialista_consultoria
        ]);
    }

    public function update(Request $request, EspecialistaConsultoria $especialista_consultoria)
    {
        $user = auth()->user();
        if (!$this->canEdit($especialista_consultoria, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'especialidad' => 'nullable|string|max:255',
            'tipo' => 'required|string|in:Profesional,Empresa',
            'estado' => 'nullable|string',
            'documento' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['documento']);

        if ($request->hasFile('documento')) {
            if ($especialista_consultoria->documento) {
                Storage::disk('public')->delete($especialista_consultoria->documento);
            }
            $data['documento'] = $request->file('documento')->store('especialistas_consultoria', 'public');
        }

        $especialista_consultoria->update($data);

        return redirect()->route('especialistas-consultoria.index')->with('success', 'Registro actualizado.');
    }

    public function destroy(EspecialistaConsultoria $especialista_consultoria)
    {
        $user = auth()->user();
        if (!$this->canDelete($especialista_consultoria, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        if ($especialista_consultoria->documento) {
            Storage::disk('public')->delete($especialista_consultoria->documento);
        }
        $especialista_consultoria->delete();
        return redirect()->route('especialistas-consultoria.index')->with('success', 'Registro eliminado.');
    }
}
