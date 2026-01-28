<?php

namespace App\Http\Controllers;

use App\Models\PlantillaIng;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;

class PlantillaIngController extends Controller
{
    use HasRoleBasedAccess;

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = PlantillaIng::query();
        $query = $this->applyRoleBasedFilter($query, $user);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('especialidad', 'like', '%' . $request->search . '%');
            });
        }

        return Inertia::render('PlantillaIng/Index', [
            'items' => $query->latest()->paginate(10),
            'filters' => $request->only(['search']),
            'userRole' => $user->role,
        ]);
    }

    public function create()
    {
        return Inertia::render('PlantillaIng/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'especialidad' => 'nullable|string|max:255',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['archivo']);
        $data['user_id'] = auth()->id();

        if ($request->hasFile('archivo')) {
            $data['archivo'] = $request->file('archivo')->store('plantillas_ing', 'public');
        }

        PlantillaIng::create($data);

        return redirect()->route('plantillas-ing.index')->with('success', 'Registro creado.');
    }

    public function edit(PlantillaIng $plantilla_ing)
    {
        $user = auth()->user();
        if (!$this->canEdit($plantilla_ing, $user)) {
            return redirect()->route('plantillas-ing.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('PlantillaIng/Edit', [
            'item' => $plantilla_ing
        ]);
    }

    public function update(Request $request, PlantillaIng $plantilla_ing)
    {
        $user = auth()->user();
        if (!$this->canEdit($plantilla_ing, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'especialidad' => 'nullable|string|max:255',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['archivo']);

        if ($request->hasFile('archivo')) {
            if ($plantilla_ing->archivo) {
                Storage::disk('public')->delete($plantilla_ing->archivo);
            }
            $data['archivo'] = $request->file('archivo')->store('plantillas_ing', 'public');
        }

        $plantilla_ing->update($data);

        return redirect()->route('plantillas-ing.index')->with('success', 'Registro actualizado.');
    }

    public function destroy(PlantillaIng $plantilla_ing)
    {
        $user = auth()->user();
        if (!$this->canDelete($plantilla_ing, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        if ($plantilla_ing->archivo) {
            Storage::disk('public')->delete($plantilla_ing->archivo);
        }
        $plantilla_ing->delete();
        return redirect()->route('plantillas-ing.index')->with('success', 'Registro eliminado.');
    }
}
