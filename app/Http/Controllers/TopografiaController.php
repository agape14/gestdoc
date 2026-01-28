<?php

namespace App\Http\Controllers;

use App\Models\Topografia;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;

class TopografiaController extends Controller
{
    use HasRoleBasedAccess;

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Topografia::query();
        $query = $this->applyRoleBasedFilter($query, $user);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('descripcion', 'like', '%' . $request->search . '%');
            });
        }

        return Inertia::render('Topografia/Index', [
            'items' => $query->latest()->paginate(10),
            'filters' => $request->only(['search']),
            'userRole' => $user->role,
        ]);
    }

    public function create()
    {
        return Inertia::render('Topografia/Create');
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
            $data['archivo'] = $request->file('archivo')->store('topografia', 'public');
        }

        Topografia::create($data);

        return redirect()->route('topografia.index')->with('success', 'Registro creado.');
    }

    public function edit(Topografia $topografia)
    {
        $user = auth()->user();
        if (!$this->canEdit($topografia, $user)) {
            return redirect()->route('topografia.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('Topografia/Edit', [
            'item' => $topografia
        ]);
    }

    public function update(Request $request, Topografia $topografia)
    {
        $user = auth()->user();
        if (!$this->canEdit($topografia, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'archivo' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['archivo']);

        if ($request->hasFile('archivo')) {
            if ($topografia->archivo) {
                Storage::disk('public')->delete($topografia->archivo);
            }
            $data['archivo'] = $request->file('archivo')->store('topografia', 'public');
        }

        $topografia->update($data);

        return redirect()->route('topografia.index')->with('success', 'Registro actualizado.');
    }

    public function destroy(Topografia $topografia)
    {
        $user = auth()->user();
        if (!$this->canDelete($topografia, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        if ($topografia->archivo) {
            Storage::disk('public')->delete($topografia->archivo);
        }
        $topografia->delete();
        return redirect()->route('topografia.index')->with('success', 'Registro eliminado.');
    }
}
