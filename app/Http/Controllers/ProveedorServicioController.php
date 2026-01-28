<?php

namespace App\Http\Controllers;

use App\Models\ProveedorServicio;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;
use App\Traits\HasRoleBasedAccess;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ProveedorServiciosExport;

class ProveedorServicioController extends Controller
{
    use HasRoleBasedAccess;

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = ProveedorServicio::query();
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

        $servicios = $query->latest()->get();
        $groupedByEspecialidad = $servicios->groupBy('especialidad');

        return Inertia::render('ProveedorServicios/Index', [
            'servicios' => $query->latest()->paginate(10),
            'groupedByEspecialidad' => $groupedByEspecialidad,
            'filters' => $request->only(['search', 'tipo', 'especialidad']),
            'userRole' => $user->role,
        ]);
    }

    public function export(Request $request)
    {
        $user = auth()->user();
        $query = ProveedorServicio::query();
        $query = $this->applyRoleBasedFilter($query, $user);

        if ($request->filled('tipo')) {
            $query->where('categoria', $request->tipo);
        }

        if ($request->filled('especialidad')) {
            $query->where('especialidad', $request->especialidad);
        }

        return Excel::download(new ProveedorServiciosExport($query->get()), 'proveedor-servicios.xlsx');
    }

    public function exportProject(ProveedorServicio $proveedorServicio)
    {
        return Excel::download(new ProveedorServiciosExport(collect([$proveedorServicio])), "proveedor-servicio_{$proveedorServicio->id}.xlsx");
    }

    public function create()
    {
        return Inertia::render('ProveedorServicios/Create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'titulo' => 'required|string|max:255',
            'entidad' => 'required|string|max:255',
            'categoria' => 'required|string|in:Publica,Privada',
            'especialidad' => 'nullable|string|max:255',
            'tipo_servicio' => 'nullable|string|max:255',
            'presupuesto' => 'nullable|numeric',
            'estado' => 'nullable|string',
            'modalidad' => 'nullable|string|max:255',
            'duracion' => 'nullable|string|max:255',
        ]);

        $data = $request->except(['contrato_archivo', 'tdr_archivo', 'informes_tecnicos', 'cargos', 'actas_resoluciones', 'conformidad_tecnica', 'panel_fotografico']);
        $data['user_id'] = auth()->id();
        $data['plantel_tecnico_aplica'] = $request->has('plantel_tecnico_aplica');
        $data['valorizaciones_aplica'] = $request->has('valorizaciones_aplica');
        $data['liquidacion_aplica'] = $request->has('liquidacion_aplica');

        ProveedorServicio::create($data);

        return redirect()->route('proveedor-servicios.index')->with('success', 'Registro creado.');
    }

    public function edit(ProveedorServicio $proveedor_servicio)
    {
        $user = auth()->user();
        if (!$this->canEdit($proveedor_servicio, $user)) {
            return redirect()->route('proveedor-servicios.index')->with('error', 'No tienes permiso para editar este registro.');
        }

        return Inertia::render('ProveedorServicios/Edit', [
            'servicio' => $proveedor_servicio
        ]);
    }

    public function update(Request $request, ProveedorServicio $proveedor_servicio)
    {
        $user = auth()->user();
        if (!$this->canEdit($proveedor_servicio, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para editar este registro.');
        }

        $data = $request->except(['contrato_archivo', 'tdr_archivo', 'informes_tecnicos', 'cargos', 'actas_resoluciones', 'conformidad_tecnica', 'panel_fotografico']);

        $data['plantel_tecnico_aplica'] = $request->has('plantel_tecnico_aplica');
        $data['valorizaciones_aplica'] = $request->has('valorizaciones_aplica');
        $data['liquidacion_aplica'] = $request->has('liquidacion_aplica');

        $handleFile = function($field, $path) use ($request, &$data, $proveedor_servicio) {
            if ($request->hasFile($field)) {
                if ($proveedor_servicio->$field) {
                    Storage::disk('public')->delete($proveedor_servicio->$field);
                }
                $data[$field] = $request->file($field)->store($path, 'public');
            }
        };

        $handleFile('contrato_archivo', 'proveedor_servicios/contratos');
        $handleFile('tdr_archivo', 'proveedor_servicios/tdrs');
        $handleFile('actas_resoluciones', 'proveedor_servicios/actas');
        $handleFile('conformidad_tecnica', 'proveedor_servicios/conformidades');
        $handleFile('panel_fotografico', 'proveedor_servicios/paneles');

        if ($request->hasFile('informes_tecnicos')) {
            $paths = [];
            foreach($request->file('informes_tecnicos') as $file) {
                $paths[] = $file->store('proveedor_servicios/informes', 'public');
            }
            $existing = is_array($proveedor_servicio->informes_tecnicos) ? $proveedor_servicio->informes_tecnicos : [];
            $data['informes_tecnicos'] = array_merge($existing, $paths);
        }

        $proveedor_servicio->update($data);

        return redirect()->back()->with('success', 'Registro actualizado.');
    }

    public function destroy(ProveedorServicio $proveedor_servicio)
    {
        $user = auth()->user();
        if (!$this->canDelete($proveedor_servicio, $user)) {
            return redirect()->back()->with('error', 'No tienes permiso para eliminar este registro.');
        }

        $proveedor_servicio->delete();
        return redirect()->route('proveedor-servicios.index')->with('success', 'Registro eliminado.');
    }
}
