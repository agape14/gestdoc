<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class CurriculumController extends Controller
{
    const MODULE = 'cvs';

    public function index(Request $request)
    {
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

        $query = Curriculum::query();
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

        return Inertia::render('Cvs/Index', [
            'cvs' => $query->latest()->paginate(10)->withQueryString()->appends($request->only(['folder_id'])),
            'filters' => $request->only(['search', 'especialidad', 'date_start', 'date_end', 'folder_id']),
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

    public function destroy(Curriculum $cv)
    {
        $folderId = $cv->folder_id;
        if ($cv->archivo_cv) {
             Storage::disk('public')->delete($cv->archivo_cv);
        }
        $cv->delete();
        return redirect()->route('cvs.index', $folderId ? ['folder_id' => $folderId] : [])->with('success', 'CV eliminado.');
    }
}
