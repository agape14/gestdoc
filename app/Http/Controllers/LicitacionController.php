<?php

namespace App\Http\Controllers;

use App\Models\Licitacion;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\LicitacionesExport;
use App\Traits\HasRoleBasedAccess;

class LicitacionController extends Controller
{
    use HasRoleBasedAccess;

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Licitacion::query();
        $query = $this->applyRoleBasedFilter($query, $user);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('entidad', 'like', '%' . $request->search . '%')
                  ->orWhere('especialidad', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('date_start')) {
            $query->whereDate('created_at', '>=', $request->date_start);
        }

        if ($request->filled('date_end')) {
            $query->whereDate('created_at', '<=', $request->date_end);
        }

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        // Agrupar por especialidad para mostrar en la vista
        $licitaciones = $query->latest()->get();
        $groupedByEspecialidad = $licitaciones->groupBy('especialidad');

        return Inertia::render('Licitaciones/Index', [
            'licitaciones' => $query->latest()->paginate(10),
            'groupedByEspecialidad' => $groupedByEspecialidad,
            'filters' => $request->only(['search', 'date_start', 'date_end', 'tipo', 'especialidad']),
            'userRole' => $user->role,
        ]);
    }

    public function export(Request $request)
    {
        $query = Licitacion::query();

        if ($request->filled('tipo')) {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        return Excel::download(new LicitacionesExport($query->get()), 'licitaciones.xlsx');
    }

    public function exportProject(Licitacion $licitacion)
    {
        return Excel::download(new LicitacionesExport(collect([$licitacion])), "licitacion_{$licitacion->id}.xlsx");
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
            'tipo' => 'required|string|in:Publica,Privada',
            'especialidad' => 'nullable|string|max:255',
            'modalidad' => 'nullable|string|max:255',
            'consorcio' => 'boolean',
            'nombre_rc' => 'nullable|string|max:255',
            'nombre_consorcio' => 'nullable|string|max:255',
            'consorciados' => 'nullable|array',
            'bases_integradas' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'propuesta_economica' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'propuesta_tecnica' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'contrato_archivo' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'promesa_consorcio' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['bases_integradas', 'propuesta_economica', 'propuesta_tecnica', 'contrato_archivo', 'promesa_consorcio']);

        // Manejar archivos
        if ($request->hasFile('bases_integradas')) {
            $data['bases_integradas'] = $request->file('bases_integradas')->store('licitaciones/bases', 'public');
        }
        if ($request->hasFile('propuesta_economica')) {
            $data['propuesta_economica'] = $request->file('propuesta_economica')->store('licitaciones/propuestas', 'public');
        }
        if ($request->hasFile('propuesta_tecnica')) {
            $data['propuesta_tecnica'] = $request->file('propuesta_tecnica')->store('licitaciones/propuestas', 'public');
        }
        if ($request->hasFile('contrato_archivo')) {
            $data['contrato_archivo'] = $request->file('contrato_archivo')->store('licitaciones/contratos', 'public');
        }
        if ($request->hasFile('promesa_consorcio')) {
            $data['promesa_consorcio'] = $request->file('promesa_consorcio')->store('licitaciones/consorcios', 'public');
        }

        $data['user_id'] = auth()->id();
        Licitacion::create($data);

        return redirect()->route('licitaciones.index')->with('success', 'Licitación creada.');
    }

    public function edit(Licitacion $licitacion)
    {
        $user = auth()->user();
        if (!$this->canEdit($licitacion, $user)) {
            return redirect()->route('licitaciones.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('Licitaciones/Edit', [
            'licitacion' => $licitacion
        ]);
    }

    public function update(Request $request, Licitacion $licitacion)
    {
        $user = auth()->user();
        if (!$this->canEdit($licitacion, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'required|string|max:255',
            'presupuesto' => 'required|numeric',
            'estado' => 'required|string',
            'tipo' => 'nullable|string|in:Publica,Privada',
            'especialidad' => 'nullable|string|max:255',
            'modalidad' => 'nullable|string|max:255',
            'consorcio' => 'boolean',
            'nombre_rc' => 'nullable|string|max:255',
            'nombre_consorcio' => 'nullable|string|max:255',
            'consorciados' => 'nullable|array',
            'bases_integradas' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'propuesta_economica' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'propuesta_tecnica' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'contrato_archivo' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
            'promesa_consorcio' => 'nullable|file|mimes:pdf,doc,docx|max:10240',
        ]);

        $data = $request->except(['bases_integradas', 'propuesta_economica', 'propuesta_tecnica', 'contrato_archivo', 'promesa_consorcio']);

        // Manejar archivos (solo actualizar si se envía nuevo archivo)
        if ($request->hasFile('bases_integradas')) {
            if ($licitacion->bases_integradas) {
                Storage::disk('public')->delete($licitacion->bases_integradas);
            }
            $data['bases_integradas'] = $request->file('bases_integradas')->store('licitaciones/bases', 'public');
        }
        if ($request->hasFile('propuesta_economica')) {
            if ($licitacion->propuesta_economica) {
                Storage::disk('public')->delete($licitacion->propuesta_economica);
            }
            $data['propuesta_economica'] = $request->file('propuesta_economica')->store('licitaciones/propuestas', 'public');
        }
        if ($request->hasFile('propuesta_tecnica')) {
            if ($licitacion->propuesta_tecnica) {
                Storage::disk('public')->delete($licitacion->propuesta_tecnica);
            }
            $data['propuesta_tecnica'] = $request->file('propuesta_tecnica')->store('licitaciones/propuestas', 'public');
        }
        if ($request->hasFile('contrato_archivo')) {
            if ($licitacion->contrato_archivo) {
                Storage::disk('public')->delete($licitacion->contrato_archivo);
            }
            $data['contrato_archivo'] = $request->file('contrato_archivo')->store('licitaciones/contratos', 'public');
        }
        if ($request->hasFile('promesa_consorcio')) {
            if ($licitacion->promesa_consorcio) {
                Storage::disk('public')->delete($licitacion->promesa_consorcio);
            }
            $data['promesa_consorcio'] = $request->file('promesa_consorcio')->store('licitaciones/consorcios', 'public');
        }

        $licitacion->update($data);

        return redirect()->back()->with('success', 'Licitación actualizada.');
    }

    public function destroy(Licitacion $licitacion)
    {
        $user = auth()->user();
        if (!$this->canDelete($licitacion, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        $licitacion->delete();
        return redirect()->route('licitaciones.index')->with('success', 'Licitación eliminada.');
    }
}
