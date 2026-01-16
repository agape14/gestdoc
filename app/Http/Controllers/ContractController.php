<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Folder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $query = Contrato::with(['folder']);
        
        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('project_name', 'like', '%' . $request->search . '%')
                  ->orWhere('client', 'like', '%' . $request->search . '%')
                  ->orWhere('contract_number', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('folder_id')) {
            $query->where('folder_id', $request->folder_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('contract_date', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('contract_date', '<=', $request->date_end);
        }

        return Inertia::render('Contracts/Index', [
            'contratos' => $query->latest()->paginate(10),
            'filters' => $request->only(['search', 'folder_id', 'status', 'date_start', 'date_end']),
        ]);
    }

    public function create()
    {
        $folders = Folder::with('children.children.children')
            ->whereNull('parent_id')
            ->get();

        return Inertia::render('Contracts/Create', [
            'folders' => $folders,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'folder_id' => 'required|exists:folders,id',
            'client' => 'required|string|max:255',
            'project_name' => 'required|string|max:255',
            'contract_object' => 'nullable|string',
            'contract_number' => 'nullable|string|max:100',
            'currency' => 'required|in:PEN,USD',
            'amount' => 'required|numeric|min:0',
            'participation_percentage' => 'required|numeric|min:0|max:100',
            'contract_date' => 'required|date',
            'conformity_date' => 'nullable|date',
            'exchange_rate' => 'nullable|numeric|min:0',
            'status' => 'required|in:completo,incompleto',
            'file' => 'required|file|mimes:pdf|max:10240',
        ]);

        $path = null;
        if ($request->hasFile('file')) {
            $path = $request->file('file')->store('contratos', 'public');
        }

        $contrato = Contrato::create([
            'folder_id' => $validated['folder_id'],
            'client' => $validated['client'],
            'project_name' => $validated['project_name'],
            'contract_object' => $validated['contract_object'],
            'contract_number' => $validated['contract_number'],
            'currency' => $validated['currency'],
            'amount' => $validated['amount'],
            'participation_percentage' => $validated['participation_percentage'],
            'contract_date' => $validated['contract_date'],
            'conformity_date' => $validated['conformity_date'],
            'exchange_rate' => $validated['exchange_rate'],
            'status' => $validated['status'],
            'file_path' => $path,
        ]);

        return redirect()->route('folders.show', $validated['folder_id'])
            ->with('success', 'Contrato creado exitosamente.');
    }

    public function edit(Contrato $contract)
    {
        $folders = Folder::with('children.children.children')
            ->whereNull('parent_id')
            ->get();

        return Inertia::render('Contracts/Edit', [
            'contract' => $contract->load('folder'),
            'folders' => $folders,
        ]);
    }

    public function update(Request $request, Contrato $contract)
    {
        $validated = $request->validate([
            'folder_id' => 'required|exists:folders,id',
            'client' => 'required|string|max:255',
            'project_name' => 'required|string|max:255',
            'contract_object' => 'nullable|string',
            'contract_number' => 'nullable|string|max:100',
            'currency' => 'required|in:PEN,USD',
            'amount' => 'required|numeric|min:0',
            'participation_percentage' => 'required|numeric|min:0|max:100',
            'contract_date' => 'required|date',
            'conformity_date' => 'nullable|date',
            'exchange_rate' => 'nullable|numeric|min:0',
            'status' => 'required|in:completo,incompleto',
            'file' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        // Update Contract File if new one uploaded
        if ($request->hasFile('file')) {
            if ($contract->file_path) {
                Storage::disk('public')->delete($contract->file_path);
            }
            $validated['file_path'] = $request->file('file')->store('contratos', 'public');
        }

        $contract->update($validated);

        return redirect()->route('folders.show', $validated['folder_id'])
            ->with('success', 'Contrato actualizado exitosamente.');
    }

    public function destroy(Contrato $contract)
    {
        $folderId = $contract->folder_id;

        if ($contract->file_path) {
            Storage::disk('public')->delete($contract->file_path);
        }

        $contract->delete();

        return redirect()->route('folders.show', $folderId)
            ->with('success', 'Contrato eliminado exitosamente.');
    }

    /**
     * Descarga el archivo PDF del contrato
     */
    public function download(Contrato $contract)
    {
        if (!$contract->file_path || !Storage::disk('public')->exists($contract->file_path)) {
            return redirect()->back()->with('error', 'El archivo no existe.');
        }

        return Storage::disk('public')->download($contract->file_path);
    }

    /**
     * Muestra el PDF en el navegador
     */
    public function viewPdf(Contrato $contract)
    {
        if (!$contract->file_path || !Storage::disk('public')->exists($contract->file_path)) {
            return response()->json(['error' => 'El archivo no existe.'], 404);
        }

        $path = Storage::disk('public')->path($contract->file_path);
        return response()->file($path);
    }
}
