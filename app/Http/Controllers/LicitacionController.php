<?php

namespace App\Http\Controllers;

use App\Models\Licitacion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LicitacionController extends Controller
{
    public function index(Request $request)
    {
        $query = Licitacion::query();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('entidad', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        return Inertia::render('Licitaciones/Index', [
            'licitaciones' => $query->latest()->paginate(10),
            'filters' => $request->only(['search', 'date_start', 'date_end']),
        ]);
    }

    public function create()
    {
        // Not needed if we use modals, but let's stick to pages or simple create
        // Actually usually Licitacion comes from Contract creation in this flow?
        // But user asked for CRUD.
        return Inertia::render('Licitaciones/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'required|string|max:255',
            'presupuesto' => 'required|numeric',
            'estado' => 'required|string',
        ]);

        Licitacion::create($validated);

        return redirect()->route('licitaciones.index')->with('success', 'Licitación creada.');
    }

    public function edit(Licitacion $licitacion)
    {
         return Inertia::render('Licitaciones/Edit', [
            'licitacion' => $licitacion
        ]);
    }

    public function update(Request $request, Licitacion $licitacion)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'required|string|max:255',
            'presupuesto' => 'required|numeric',
            'estado' => 'required|string',
        ]);

        $licitacion->update($validated);

        return redirect()->route('licitaciones.index')->with('success', 'Licitación actualizada.');
    }

    public function destroy(Licitacion $licitacion)
    {
        $licitacion->delete();
        return redirect()->route('licitaciones.index')->with('success', 'Licitación eliminada.');
    }
}
