<?php

namespace App\Http\Controllers;

use App\Models\Curriculum;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

class CurriculumController extends Controller
{
    public function index(Request $request)
    {
        $query = Curriculum::query();

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('nombre_candidato', 'like', '%' . $request->search . '%')
                  ->orWhere('especialidad', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        return Inertia::render('Cvs/Index', [
            'cvs' => $query->latest()->paginate(10),
            'filters' => $request->only(['search', 'especialidad', 'date_start', 'date_end']),
        ]);
    }

    public function create()
    {
        return Inertia::render('Cvs/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre_candidato' => 'required|string|max:255',
            'especialidad' => 'required|string|max:255',
            'archivo_cv' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        $path = null;
        if ($request->hasFile('archivo_cv')) {
            $path = $request->file('archivo_cv')->store('cvs', 'public');
        }

        Curriculum::create([
            'nombre_candidato' => $validated['nombre_candidato'],
            'especialidad' => $validated['especialidad'],
            'archivo_cv' => $path,
        ]);

        return redirect()->route('cvs.index')->with('success', 'CV registrado.');
    }

    public function edit(Curriculum $cv)
    {
        return Inertia::render('Cvs/Edit', [
            'cv' => $cv
        ]);
    }

    public function update(Request $request, Curriculum $cv)
    {
         $validated = $request->validate([
            'nombre_candidato' => 'required|string|max:255',
            'especialidad' => 'required|string|max:255',
            'archivo_cv' => 'nullable|file|mimes:pdf|max:10240',
        ]);

        if ($request->hasFile('archivo_cv')) {
            if ($cv->archivo_cv) {
                Storage::disk('public')->delete($cv->archivo_cv);
            }
            $cv->archivo_cv = $request->file('archivo_cv')->store('cvs', 'public');
        }

        $cv->update([
            'nombre_candidato' => $validated['nombre_candidato'],
            'especialidad' => $validated['especialidad'],
            // archivo_cv is updated above if present
        ]);

        return redirect()->route('cvs.index')->with('success', 'CV actualizado.');
    }

    public function destroy(Curriculum $cv)
    {
        if ($cv->archivo_cv) {
             Storage::disk('public')->delete($cv->archivo_cv);
        }
        $cv->delete();
        return redirect()->route('cvs.index')->with('success', 'CV eliminado.');
    }
}
