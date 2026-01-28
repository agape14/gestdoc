<?php

namespace App\Http\Controllers;

use App\Models\EspecialistaEjecucion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;

class EspecialistaEjecucionController extends Controller
{
    use HasRoleBasedAccess;

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = EspecialistaEjecucion::query();
        $query = $this->applyRoleBasedFilter($query, $user);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('nombre', 'like', '%' . $request->search . '%')
                  ->orWhere('especialidad', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        return Inertia::render('EspecialistasEjecucion/Index', [
            'especialistas' => $query->latest()->paginate(10),
            'filters' => $request->only(['search', 'tipo']),
            'userRole' => $user->role,
        ]);
    }

    public function create()
    {
        return Inertia::render('EspecialistasEjecucion/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'especialidad' => 'nullable|string|max:255',
            'tipo' => 'required|string|in:Profesional,Empresa',
            'estado' => 'nullable|string',
            'documento' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['documento']);
        $data['user_id'] = auth()->id();

        if ($request->hasFile('documento')) {
            $data['documento'] = $request->file('documento')->store('especialistas_ejecucion', 'public');
        }

        EspecialistaEjecucion::create($data);

        return redirect()->route('especialistas-ejecucion.index')->with('success', 'Registro creado.');
    }

    public function edit(EspecialistaEjecucion $especialista_ejecucion)
    {
        $user = auth()->user();
        if (!$this->canEdit($especialista_ejecucion, $user)) {
            return redirect()->route('especialistas-ejecucion.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('EspecialistasEjecucion/Edit', [
            'especialista' => $especialista_ejecucion
        ]);
    }

    public function update(Request $request, EspecialistaEjecucion $especialista_ejecucion)
    {
        $user = auth()->user();
        if (!$this->canEdit($especialista_ejecucion, $user)) {
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
            if ($especialista_ejecucion->documento) {
                Storage::disk('public')->delete($especialista_ejecucion->documento);
            }
            $data['documento'] = $request->file('documento')->store('especialistas_ejecucion', 'public');
        }

        $especialista_ejecucion->update($data);

        return redirect()->route('especialistas-ejecucion.index')->with('success', 'Registro actualizado.');
    }

    public function destroy(EspecialistaEjecucion $especialista_ejecucion)
    {
        $user = auth()->user();
        if (!$this->canDelete($especialista_ejecucion, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        if ($especialista_ejecucion->documento) {
            Storage::disk('public')->delete($especialista_ejecucion->documento);
        }
        $especialista_ejecucion->delete();
        return redirect()->route('especialistas-ejecucion.index')->with('success', 'Registro eliminado.');
    }
}
