<?php

namespace App\Http\Controllers;

use App\Models\EspecialistaConsultoria;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;

class EspecialistaConsultoriaController extends Controller
{
    use HasRoleBasedAccess;

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = EspecialistaConsultoria::query();
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

        return Inertia::render('EspecialistasConsultoria/Index', [
            'especialistas' => $query->latest()->paginate(10),
            'filters' => $request->only(['search', 'tipo']),
            'userRole' => $user->role,
        ]);
    }

    public function create()
    {
        return Inertia::render('EspecialistasConsultoria/Create');
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
            $data['documento'] = $request->file('documento')->store('especialistas_consultoria', 'public');
        }

        EspecialistaConsultoria::create($data);

        return redirect()->route('especialistas-consultoria.index')->with('success', 'Registro creado.');
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
