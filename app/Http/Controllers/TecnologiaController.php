<?php

namespace App\Http\Controllers;

use App\Models\Tecnologia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;

class TecnologiaController extends Controller
{
    use HasRoleBasedAccess;

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Tecnologia::query();
        $query = $this->applyRoleBasedFilter($query, $user);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
            });
        }

        return Inertia::render('Tecnologia/Index', [
            'items' => $query->latest()->paginate(10),
            'filters' => $request->only(['search']),
            'userRole' => $user->role,
        ]);
    }

    public function create()
    {
        return Inertia::render('Tecnologia/Create');
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

        if ($request->hasFile('archivo')) {
            $data['archivo'] = $request->file('archivo')->store('tecnologia', 'public');
        }

        Tecnologia::create($data);

        return redirect()->route('tecnologia.index')->with('success', 'Registro creado.');
    }

    public function edit(Tecnologia $tecnologia)
    {
        $user = auth()->user();
        if (!$this->canEdit($tecnologia, $user)) {
            return redirect()->route('tecnologia.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('Tecnologia/Edit', [
            'item' => $tecnologia
        ]);
    }

    public function update(Request $request, Tecnologia $tecnologia)
    {
        $user = auth()->user();
        if (!$this->canEdit($tecnologia, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['archivo']);

        if ($request->hasFile('archivo')) {
            if ($tecnologia->archivo) {
                Storage::disk('public')->delete($tecnologia->archivo);
            }
            $data['archivo'] = $request->file('archivo')->store('tecnologia', 'public');
        }

        $tecnologia->update($data);

        return redirect()->route('tecnologia.index')->with('success', 'Registro actualizado.');
    }

    public function destroy(Tecnologia $tecnologia)
    {
        $user = auth()->user();
        if (!$this->canDelete($tecnologia, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        if ($tecnologia->archivo) {
            Storage::disk('public')->delete($tecnologia->archivo);
        }
        $tecnologia->delete();
        return redirect()->route('tecnologia.index')->with('success', 'Registro eliminado.');
    }
}
