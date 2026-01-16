<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Models\Contrato;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Exports\ContractsExport;
use Maatwebsite\Excel\Facades\Excel;

class FolderController extends Controller
{
    /**
     * Display a listing of the resource.
     * Muestra las carpetas raíz (sin parent_id)
     */
    public function index()
    {
        $folders = Folder::whereNull('parent_id')
            ->with(['children', 'contratos'])
            ->get();

        $allFolders = Folder::with('children.children.children')
            ->whereNull('parent_id')
            ->get();

        return Inertia::render('Folders/Index', [
            'folders' => $folders,
            'currentFolder' => null,
            'breadcrumb' => [],
            'allFolders' => $allFolders,
        ]);
    }

    /**
     * Muestra el contenido de una carpeta específica
     */
    public function show(Request $request, $id)
    {
        $folder = Folder::with(['children', 'parent'])->findOrFail($id);

        $allFolders = Folder::with('children.children.children')
            ->whereNull('parent_id')
            ->get();

        // Query de contratos con filtros
        $query = $folder->contratos();

        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('project_name', 'like', '%' . $search . '%')
                  ->orWhere('client', 'like', '%' . $search . '%')
                  ->orWhere('contract_number', 'like', '%' . $search . '%')
                  ->orWhere('contract_object', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('contract_date', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('contract_date', '<=', $request->date_end);
        }

        $contracts = $query->latest()->get();

        return Inertia::render('Folders/Index', [
            'folders' => $folder->children,
            'contracts' => $contracts,
            'currentFolder' => $folder,
            'breadcrumb' => $folder->path,
            'allFolders' => $allFolders,
            'filters' => $request->only(['search', 'status', 'currency', 'date_start', 'date_end']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => 'nullable|exists:folders,id',
            'name' => 'required|string|max:255',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'description' => 'nullable|string|max:500',
        ]);

        $folder = Folder::create($validated);

        return redirect()->back()->with('success', 'Carpeta creada exitosamente.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Folder $folder)
    {
        // Verificar si es carpeta del sistema y solo permitir ciertos cambios
        if ($folder->is_system) {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'nullable|string|max:7',
                'description' => 'nullable|string|max:500',
            ]);
        } else {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'color' => 'nullable|string|max:7',
                'icon' => 'nullable|string|max:50',
                'description' => 'nullable|string|max:500',
            ]);
        }

        $folder->update($validated);

        return redirect()->back()->with('success', 'Carpeta actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Folder $folder)
    {
        // No permitir borrar carpetas del sistema
        if ($folder->is_system) {
            return redirect()->back()->with('error', 'No se pueden eliminar carpetas del sistema.');
        }

        $folder->delete();

        return redirect()->back()->with('success', 'Carpeta eliminada exitosamente.');
    }

    /**
     * Obtiene las carpetas para el selector de carpetas
     */
    public function getTree()
    {
        $folders = Folder::with('children.children.children')
            ->whereNull('parent_id')
            ->get();

        return response()->json($folders);
    }

    /**
     * Exporta los contratos de una carpeta a Excel
     */
    public function exportContracts(Request $request, $id)
    {
        $folder = Folder::findOrFail($id);

        // Query de contratos con los mismos filtros que la vista
        $query = $folder->contratos();

        // Aplicar filtros
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('project_name', 'like', '%' . $search . '%')
                  ->orWhere('client', 'like', '%' . $search . '%')
                  ->orWhere('contract_number', 'like', '%' . $search . '%')
                  ->orWhere('contract_object', 'like', '%' . $search . '%');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('contract_date', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('contract_date', '<=', $request->date_end);
        }

        $contracts = $query->latest()->get();

        $filename = 'contratos_' . \Str::slug($folder->name) . '_' . date('Y-m-d_His') . '.xlsx';

        return Excel::download(new ContractsExport($contracts), $filename);
    }
}
