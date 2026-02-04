<?php

namespace App\Http\Controllers;

use App\Models\Topografia;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;

class TopografiaController extends Controller
{
    use HasRoleBasedAccess;

    const MODULE = 'topografia';

    public function index(Request $request)
    {
        $user = auth()->user();
        $folderId = $request->filled('folder_id') ? (int) $request->folder_id : null;
        if ($folderId) {
            $currentFolder = Folder::where('module', self::MODULE)->findOrFail($folderId);
            $currentFolder->load(['parent']);
            $folders = $currentFolder->children()->orderBy('name')->get();
            $breadcrumb = $currentFolder->path;
        } else {
            $currentFolder = null;
            $folders = Folder::whereNull('parent_id')->where('module', self::MODULE)->orderBy('name')->get();
            $breadcrumb = [];
        }

        $query = Topografia::query();
        $query = $this->applyRoleBasedFilter($query, $user);
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

        return Inertia::render('Topografia/Index', [
            'items' => $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id'])),
            'filters' => $request->only(['search', 'folder_id']),
            'userRole' => $user->role,
            'folders' => $folders,
            'currentFolder' => $currentFolder,
            'breadcrumb' => $breadcrumb,
        ]);
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

    public function destroy(Topografia $topografia)
    {
        $user = auth()->user();
        if (!$this->canDelete($topografia, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        if ($topografia->archivo) {
            Storage::disk('public')->delete($topografia->archivo);
        }
        $folderId = $topografia->folder_id;
        $topografia->delete();
        return redirect()->route('topografia.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'Registro eliminado.');
    }
}
