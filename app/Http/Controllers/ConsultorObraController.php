<?php

namespace App\Http\Controllers;

use App\Models\ConsultorObra;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ConsultorObrasExport;
use App\Traits\HasRoleBasedAccess;

class ConsultorObraController extends Controller
{
    use HasRoleBasedAccess;

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = ConsultorObra::query();
        $query = $this->applyRoleBasedFilter($query, $user);

        if ($request->filled('search')) {
            $query->where(function($q) use ($request) {
                $q->where('titulo', 'like', '%' . $request->search . '%')
                  ->orWhere('entidad', 'like', '%' . $request->search . '%')
                  ->orWhere('especialidad', 'like', '%' . $request->search . '%');
            });
        }

        if ($request->filled('tipo')) {
            $query->where('categoria', $request->tipo); // Publica / Privada mapped to categoria
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        // Agrupar por especialidad
        $consultorias = $query->latest()->get();
        $groupedByEspecialidad = $consultorias->groupBy('especialidad');

        return Inertia::render('ConsultorObras/Index', [
            'consultorias' => $query->latest()->paginate(10),
            'groupedByEspecialidad' => $groupedByEspecialidad,
            'filters' => $request->only(['search', 'tipo', 'especialidad']),
            'userRole' => $user->role,
        ]);
    }

    public function export(Request $request)
    {
        $query = ConsultorObra::query();

        if ($request->filled('tipo')) {
            $query->where('categoria', $request->tipo);
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        return Excel::download(new ConsultorObrasExport($query->get()), 'consultor-obras.xlsx');
    }

    public function exportProject(ConsultorObra $consultorObra)
    {
        return Excel::download(new ConsultorObrasExport(collect([$consultorObra])), "consultor-obra_{$consultorObra->id}.xlsx");
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'required|string|max:255',
            'categoria' => 'required|string',
            'especialidad' => 'nullable|string|max:255',
            'tipo_servicio' => 'nullable|string|max:255',
            'presupuesto' => 'nullable|numeric',
            'estado' => 'nullable|string',
            'duracion' => 'nullable|string|max:255',
            'modalidad' => 'nullable|string|max:255',
        ]);

        $data = $request->except(['contrato_archivo', 'tdr_archivo', 'personal_clave', 'producto_tecnico', 'actas_resoluciones', 'conformidad_tecnica']);
        $data['user_id'] = auth()->id();

        $consultorObra = ConsultorObra::create($data);

        return redirect()->route('consultor-obras.index')->with('success', 'Registro creado.');
    }

    public function update(Request $request, ConsultorObra $consultor_obra)
    {
        $user = auth()->user();
        if (!$this->canEdit($consultor_obra, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $data = $request->except(['contrato_archivo', 'tdr_archivo', 'personal_clave', 'producto_tecnico', 'actas_resoluciones', 'conformidad_tecnica']);

        // Helper to store file
        $handleFile = function($field, $path) use ($request, &$data, $consultor_obra) {
            if ($request->hasFile($field)) {
                if ($consultor_obra->$field) {
                    Storage::disk('public')->delete($consultor_obra->$field);
                }
                $data[$field] = $request->file($field)->store($path, 'public');
            }
        };

        $handleFile('contrato_archivo', 'consultor_obras/contratos');
        $handleFile('tdr_archivo', 'consultor_obras/tdrs');
        $handleFile('personal_clave', 'consultor_obras/personal');
        $handleFile('actas_resoluciones', 'consultor_obras/actas');
        $handleFile('conformidad_tecnica', 'consultor_obras/conformidades');

        // Handle multiple files for producto_tecnico
        if ($request->hasFile('producto_tecnico')) {
            $paths = [];
            foreach($request->file('producto_tecnico') as $file) {
                $paths[] = $file->store('consultor_obras/productos', 'public');
            }
            $existing = is_array($consultor_obra->producto_tecnico) ? $consultor_obra->producto_tecnico : [];
            $data['producto_tecnico'] = array_merge($existing, $paths);
        }

        $consultor_obra->update($data);

        return redirect()->back()->with('success', 'Registro actualizado.');
    }

    public function destroy(ConsultorObra $consultor_obra)
    {
        $user = auth()->user();
        if (!$this->canDelete($consultor_obra, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        $consultor_obra->delete();
        return redirect()->route('consultor-obras.index')->with('success', 'Registro eliminado.');
    }
}
