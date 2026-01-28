<?php

namespace App\Http\Controllers;

use App\Models\ProveedorBien;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Traits\HasRoleBasedAccess;

class ProveedorBienController extends Controller
{
    use HasRoleBasedAccess;

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = ProveedorBien::query();
        $query = $this->applyRoleBasedFilter($query, $user);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('entidad', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('tipo')) {
            $query->where('categoria', $request->tipo);
        }

        return Inertia::render('ProveedorBienes/Index', [
            'bienes' => $query->latest()->paginate(10),
            'filters' => $request->only(['search', 'tipo']),
            'userRole' => $user->role,
        ]);
    }

    public function create()
    {
        return Inertia::render('ProveedorBienes/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'nullable|string|max:255',
            'categoria' => 'required|string|in:Publica,Privada',
            'estado' => 'nullable|string',
            'costo' => 'nullable|numeric',
        ]);

        $data = $validated;
        $data['user_id'] = auth()->id();

        ProveedorBien::create($data);

        return redirect()->route('proveedor-bienes.index')->with('success', 'Registro creado.');
    }

    public function edit(ProveedorBien $proveedor_bien)
    {
        $user = auth()->user();
        if (!$this->canEdit($proveedor_bien, $user)) {
            return redirect()->route('proveedor-bienes.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('ProveedorBienes/Edit', [
            'bien' => $proveedor_bien
        ]);
    }

    public function update(Request $request, ProveedorBien $proveedor_bien)
    {
        $user = auth()->user();
        if (!$this->canEdit($proveedor_bien, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'nullable|string|max:255',
            'categoria' => 'required|string|in:Publica,Privada',
            'estado' => 'nullable|string',
            'costo' => 'nullable|numeric',
        ]);

        $proveedor_bien->update($validated);

        return redirect()->route('proveedor-bienes.index')->with('success', 'Registro actualizado.');
    }

    public function destroy(ProveedorBien $proveedor_bien)
    {
        $user = auth()->user();
        if (!$this->canDelete($proveedor_bien, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        $proveedor_bien->delete();
        return redirect()->route('proveedor-bienes.index')->with('success', 'Registro eliminado.');
    }
}
