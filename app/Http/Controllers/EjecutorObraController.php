<?php

namespace App\Http\Controllers;

use App\Models\EjecutorObra;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\EjecutorObrasExport;

class EjecutorObraController extends Controller
{
    use HasRoleBasedAccess;

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = EjecutorObra::query();

        // Aplicar filtro por rol
        $query = $this->applyRoleBasedFilter($query, $user);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('entidad', 'like', '%' . $request->search . '%')
                  ->orWhere('especialidad', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('tipo')) {
            $query->where('categoria', $request->tipo);
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        $obras = $query->latest()->get();
        $groupedByEspecialidad = $obras->groupBy('especialidad');

        return Inertia::render('EjecutorObra/Index', [
            'obras' => $query->latest()->paginate(10),
            'groupedByEspecialidad' => $groupedByEspecialidad,
            'filters' => $request->only(['search', 'tipo', 'especialidad']),
            'userRole' => $user->role,
        ]);
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $query = EjecutorObra::query();
        $query = $this->applyRoleBasedFilter($query, $user);

        if ($request->filled('tipo')) {
            $query->where('categoria', $request->tipo);
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        return Excel::download(new EjecutorObrasExport($query->get()), 'ejecutor-obras.xlsx');
    }

    public function exportProject(EjecutorObra $ejecutorObra)
    {
        return Excel::download(new EjecutorObrasExport(collect([$ejecutorObra])), "ejecutor-obra_{$ejecutorObra->id}.xlsx");
    }

    public function create()
    {
        return Inertia::render('EjecutorObra/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'required|string|max:255',
            'categoria' => 'required|string|in:Publica,Privada',
            'especialidad' => 'nullable|string|max:255',
            'tipo_obra' => 'nullable|string|max:255',
            'presupuesto' => 'nullable|numeric',
            'estado' => 'nullable|string',
            'modalidad' => 'nullable|string|max:255',
        ]);

        $data = $request->except(['contrato_archivo', 'tdr_archivo', 'valorizaciones', 'informes_tecnicos', 'cargos', 'expediente_tecnico', 'actas_resoluciones', 'conformidad_tecnica', 'panel_fotografico', 'liquidacion']);
        $data['user_id'] = auth()->id();

        EjecutorObra::create($data);

        return redirect()->route('ejecutor-obra.index')->with('success', 'Registro creado.');
    }

    public function edit(EjecutorObra $ejecutor_obra)
    {
        $user = auth()->user();
        if (!$this->canEdit($ejecutor_obra, $user)) {
            return redirect()->route('ejecutor-obra.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('EjecutorObra/Edit', [
            'obra' => $ejecutor_obra
        ]);
    }

    public function update(Request $request, EjecutorObra $ejecutor_obra)
    {
        $user = auth()->user();
        if (!$this->canEdit($ejecutor_obra, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $data = $request->except(['contrato_archivo', 'tdr_archivo', 'valorizaciones', 'informes_tecnicos', 'cargos', 'expediente_tecnico', 'actas_resoluciones', 'conformidad_tecnica', 'panel_fotografico', 'liquidacion']);

        $handleFile = function($field, $path) use ($request, &$data, $ejecutor_obra) {
            if ($request->hasFile($field)) {
                if ($ejecutor_obra->$field) {
                    Storage::disk('public')->delete($ejecutor_obra->$field);
                }
                $data[$field] = $request->file($field)->store($path, 'public');
            }
        };

        $handleFile('contrato_archivo', 'ejecutor_obras/contratos');
        $handleFile('tdr_archivo', 'ejecutor_obras/tdrs');
        $handleFile('expediente_tecnico', 'ejecutor_obras/expedientes');
        $handleFile('actas_resoluciones', 'ejecutor_obras/actas');
        $handleFile('conformidad_tecnica', 'ejecutor_obras/conformidades');
        $handleFile('panel_fotografico', 'ejecutor_obras/paneles');
        $handleFile('liquidacion', 'ejecutor_obras/liquidaciones');

        if ($request->hasFile('valorizaciones')) {
            $paths = [];
            foreach($request->file('valorizaciones') as $file) {
                $paths[] = $file->store('ejecutor_obras/valorizaciones', 'public');
            }
            $existing = is_array($ejecutor_obra->valorizaciones) ? $ejecutor_obra->valorizaciones : [];
            $data['valorizaciones'] = array_merge($existing, $paths);
        }

        if ($request->hasFile('informes_tecnicos')) {
            $paths = [];
            foreach($request->file('informes_tecnicos') as $file) {
                $paths[] = $file->store('ejecutor_obras/informes', 'public');
            }
            $existing = is_array($ejecutor_obra->informes_tecnicos) ? $ejecutor_obra->informes_tecnicos : [];
            $data['informes_tecnicos'] = array_merge($existing, $paths);
        }

        $ejecutor_obra->update($data);

        return redirect()->back()->with('success', 'Registro actualizado.');
    }

    public function destroy(EjecutorObra $ejecutor_obra)
    {
        $user = auth()->user();
        if (!$this->canDelete($ejecutor_obra, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        $ejecutor_obra->delete();
        return redirect()->route('ejecutor-obra.index')->with('success', 'Registro eliminado.');
    }
}
