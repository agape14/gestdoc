<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Licitacion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class ContractController extends Controller
{
    public function index(Request $request)
    {
        $query = Contrato::with('licitacion');
        
        if ($request->filled('search')) {
            $query->whereHas('licitacion', function($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('entidad', 'like', '%' . $request->search . '%');
            });
        }

         if ($request->filled('date_start')) {
            $query->whereDate('fecha_firma', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('fecha_firma', '<=', $request->date_end);
        }

        return Inertia::render('Contracts/Index', [
            'contratos' => $query->latest()->paginate(10),
            'filters' => $request->only(['search', 'date_start', 'date_end']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Contracts/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_proyecto' => 'required|string|max:255',
            'cliente' => 'required|string|max:255',
            'monto' => 'required|numeric',
            'fecha_inicio' => 'required|date', 
            'archivo' => 'required|file|mimes:pdf|max:10240',
        ]);

        $licitacion = Licitacion::create([
            'titulo' => $validated['nombre_proyecto'],
            'entidad' => $validated['cliente'],
            'presupuesto' => $validated['monto'],
            'estado' => 'En Curso',
        ]);

        $path = null;
        if ($request->hasFile('archivo')) {
            $path = $request->file('archivo')->store('contratos', 'public');
        }

        Contrato::create([
            'licitacion_id' => $licitacion->id,
            'fecha_firma' => $validated['fecha_inicio'],
            'archivo_path' => $path,
        ]);

        return redirect()->route('contracts.index')->with('success', 'Contrato creado exitosamente.');
    }

    public function edit(Contrato $contract)
    {
        return Inertia::render('Contracts/Edit', [
            'contract' => $contract->load('licitacion')
        ]);
    }

    public function update(Request $request, Contrato $contract)
    {
         $validated = $request->validate([
            'nombre_proyecto' => 'required|string|max:255',
            'cliente' => 'required|string|max:255',
            'monto' => 'required|numeric',
            'fecha_inicio' => 'required|date',
            'archivo' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        // Update Associated Licitacion
        $contract->licitacion->update([
            'titulo' => $validated['nombre_proyecto'],
            'entidad' => $validated['cliente'],
            'presupuesto' => $validated['monto'],
        ]);

        // Update Contract File if new one uploaded
         if ($request->hasFile('archivo')) {
            if ($contract->archivo_path) {
                Storage::disk('public')->delete($contract->archivo_path);
            }
             $contract->archivo_path = $request->file('archivo')->store('contratos', 'public');
        }

        $contract->update([
            'fecha_firma' => $validated['fecha_inicio']
        ]);

        return redirect()->route('contracts.index')->with('success', 'Contrato actualizado.');
    }

    public function destroy(Contrato $contract)
    {
        if ($contract->archivo_path) {
            Storage::disk('public')->delete($contract->archivo_path);
        }
        // Also delete licitacion? Usually yes if 1-to-1 strict owner.
        // But schema says Licitacion 1->N Contratos. 
        // If we delete contract, maybe we keep licitacion?
        // User requirements were general. I'll just delete contract.
        // Actually schema says Licitacion -> Contratos. Foreign key is on Contratos.
        $contract->delete();
        return redirect()->route('contracts.index')->with('success', 'Contrato eliminado.');
    }
}
