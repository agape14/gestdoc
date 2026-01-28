<?php

namespace App\Http\Controllers;

use App\Models\Inmobiliaria;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;

class InmobiliariaController extends Controller
{
    use HasRoleBasedAccess;

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Inmobiliaria::query();
        $query = $this->applyRoleBasedFilter($query, $user);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('ubicacion', 'like', '%' . $request->search . '%');
            });
        }

        return Inertia::render('Inmobiliaria/Index', [
            'items' => $query->latest()->paginate(10),
            'filters' => $request->only(['search']),
            'userRole' => $user->role,
        ]);
    }

    public function create()
    {
        return Inertia::render('Inmobiliaria/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'precio' => 'nullable|numeric',
            'estado' => 'nullable|string',
            'imagen' => 'nullable|image|max:2048',
        ]);

        $data = $request->except(['imagen']);
        $data['user_id'] = auth()->id();

        if ($request->hasFile('imagen')) {
            $data['imagen'] = $request->file('imagen')->store('inmobiliaria', 'public');
        }

        Inmobiliaria::create($data);

        return redirect()->route('inmobiliaria.index')->with('success', 'Registro creado.');
    }

    public function edit(Inmobiliaria $inmobiliaria)
    {
        $user = auth()->user();
        if (!$this->canEdit($inmobiliaria, $user)) {
            return redirect()->route('inmobiliaria.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('Inmobiliaria/Edit', [
            'item' => $inmobiliaria
        ]);
    }

    public function update(Request $request, Inmobiliaria $inmobiliaria)
    {
        $user = auth()->user();
        if (!$this->canEdit($inmobiliaria, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'ubicacion' => 'nullable|string|max:255',
            'precio' => 'nullable|numeric',
            'estado' => 'nullable|string',
            'imagen' => 'nullable|image|max:2048',
        ]);

        $data = $request->except(['imagen']);

        if ($request->hasFile('imagen')) {
            if ($inmobiliaria->imagen) {
                Storage::disk('public')->delete($inmobiliaria->imagen);
            }
            $data['imagen'] = $request->file('imagen')->store('inmobiliaria', 'public');
        }

        $inmobiliaria->update($data);

        return redirect()->route('inmobiliaria.index')->with('success', 'Registro actualizado.');
    }

    public function destroy(Inmobiliaria $inmobiliaria)
    {
        $user = auth()->user();
        if (!$this->canDelete($inmobiliaria, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        if ($inmobiliaria->imagen) {
            Storage::disk('public')->delete($inmobiliaria->imagen);
        }
        $inmobiliaria->delete();
        return redirect()->route('inmobiliaria.index')->with('success', 'Registro eliminado.');
    }
}
