<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Licitacion;
use App\Models\Curriculum;
use App\Models\Configuration;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

use App\Models\ConsultorObra;
use App\Models\EjecutorObra;
use App\Models\ProveedorServicio;
use App\Models\ProveedorBien;
use App\Models\EspecialistaEjecucion;
use App\Models\EspecialistaConsultoria;
use App\Models\Inmobiliaria;
use App\Models\Topografia;
use App\Models\Tecnologia;
use App\Models\PlantillaIng;
use App\Models\Folder;

class DashboardController extends Controller
{
    /** Operador: solo sus registros. Visualizador: 0 (no inserta). Administrador: todo. */
    private function scopeForRole($query, $model)
    {
        $user = Auth::user();
        if (!$user) {
            return $query;
        }
        if ($user->role === 'Administrador') {
            return $query;
        }
        if ($user->role === 'Visualizador') {
            return $query->whereRaw('1 = 0');
        }
        if ($user->role === 'Operador' && Schema::hasColumn((new $model)->getTable(), 'user_id')) {
            return $query->where('user_id', $user->id);
        }
        return $query;
    }

    /** Cuenta por carpeta (nombre) en un módulo. */
    private function countByFolder($model, string $module, string $folderName, $default = 0)
    {
        try {
            $folder = Folder::where('module', $module)->where('name', $folderName)->first();
            if (!$folder) {
                return $default;
            }
            $query = $model::where('folder_id', $folder->id);
            $query = $this->scopeForRole($query, $model);
            if (Schema::hasColumn((new $model)->getTable(), 'anulado')) {
                $query->where('anulado', false);
            }
            return $query->count();
        } catch (\Exception $e) {
            Log::warning("Error countByFolder {$model} {$module}/{$folderName}: " . $e->getMessage());
            return $default;
        }
    }

    public function index()
    {
        $user = Auth::user();
        $isAdmin = $user && $user->role === 'Administrador';

        $safeCount = function($model, $default = 0) {
            try {
                $query = $model::query();
                $query = $this->scopeForRole($query, $model);
                if (Schema::hasColumn((new $model)->getTable(), 'anulado')) {
                    $query->where('anulado', false);
                }
                return $query->count();
            } catch (\Exception $e) {
                \Log::warning("Error counting {$model}: " . $e->getMessage());
                return $default;
            }
        };

        $safeCountWhere = function($model, $column, $value, $default = 0) {
            try {
                $query = $model::where($column, $value);
                $query = $this->scopeForRole($query, $model);
                if (Schema::hasColumn((new $model)->getTable(), 'anulado')) {
                    $query->where('anulado', false);
                }
                return $query->count();
            } catch (\Exception $e) {
                \Log::warning("Error counting {$model} where {$column}={$value}: " . $e->getMessage());
                return $default;
            }
        };

        // Curriculum: cuenta por carpeta Profesionales/Empresas (folder_id). Folder: solo gestión documental (module null).
        $cvTotal = $isAdmin ? (function() {
            try { return Curriculum::count(); } catch (\Exception $e) { \Log::warning($e->getMessage()); return 0; }
        })() : 0;
        $cvProf = $isAdmin ? $this->countByFolder(Curriculum::class, 'cvs', 'Profesionales', 0) : 0;
        $cvEmp = $isAdmin ? $this->countByFolder(Curriculum::class, 'cvs', 'Empresas', 0) : 0;
        $folderCount = $isAdmin ? (function() {
            try { return Folder::whereNull('module')->count(); } catch (\Exception $e) { return 0; }
        })() : 0;

        $stats = [
            'licitaciones' => [
                'total' => $safeCount(Licitacion::class),
                'publicas' => $safeCountWhere(Licitacion::class, 'tipo', 'Publica'),
                'privadas' => $safeCountWhere(Licitacion::class, 'tipo', 'Privada'),
            ],
            'consultorObras' => [
                'total' => $safeCount(ConsultorObra::class),
                'publicas' => $safeCountWhere(ConsultorObra::class, 'categoria', 'Publica'),
                'privadas' => $safeCountWhere(ConsultorObra::class, 'categoria', 'Privada'),
            ],
            'ejecutorObras' => [
                'total' => $safeCount(EjecutorObra::class),
                'publicas' => $safeCountWhere(EjecutorObra::class, 'categoria', 'Publica'),
                'privadas' => $safeCountWhere(EjecutorObra::class, 'categoria', 'Privada'),
            ],
            'proveedorServicios' => [
                'total' => $safeCount(ProveedorServicio::class),
                'publicas' => $safeCountWhere(ProveedorServicio::class, 'categoria', 'Publica'),
                'privadas' => $safeCountWhere(ProveedorServicio::class, 'categoria', 'Privada'),
            ],
            'proveedorBienes' => [
                'total' => $safeCount(ProveedorBien::class),
                'publicas' => $safeCountWhere(ProveedorBien::class, 'categoria', 'Publica'),
                'privadas' => $safeCountWhere(ProveedorBien::class, 'categoria', 'Privada'),
            ],
            'especialistasEjecucion' => [
                'total' => $safeCount(EspecialistaEjecucion::class),
                'profesionales' => $safeCountWhere(EspecialistaEjecucion::class, 'tipo', 'Profesional'),
                'empresas' => $safeCountWhere(EspecialistaEjecucion::class, 'tipo', 'Empresa'),
            ],
            'especialistasConsultoria' => [
                'total' => $safeCount(EspecialistaConsultoria::class),
                'profesionales' => $safeCountWhere(EspecialistaConsultoria::class, 'tipo', 'Profesional'),
                'empresas' => $safeCountWhere(EspecialistaConsultoria::class, 'tipo', 'Empresa'),
            ],
            'inmobiliaria' => [
                'total' => $safeCount(Inmobiliaria::class),
                'publicas' => $this->countByFolder(Inmobiliaria::class, 'inmobiliaria', 'Públicas', 0),
                'privadas' => $this->countByFolder(Inmobiliaria::class, 'inmobiliaria', 'Privadas', 0),
            ],
            'topografia' => [
                'total' => $safeCount(Topografia::class),
                'publicas' => $this->countByFolder(Topografia::class, 'topografia', 'Públicas', 0),
                'privadas' => $this->countByFolder(Topografia::class, 'topografia', 'Privadas', 0),
            ],
            'tecnologia' => [
                'total' => $safeCount(Tecnologia::class),
                'publicas' => $this->countByFolder(Tecnologia::class, 'tecnologia', 'Públicas', 0),
                'privadas' => $this->countByFolder(Tecnologia::class, 'tecnologia', 'Privadas', 0),
            ],
            'plantillasIng' => [
                'total' => $safeCount(PlantillaIng::class),
                'publicas' => $this->countByFolder(PlantillaIng::class, 'plantillas-ing', 'Públicas', 0),
                'privadas' => $this->countByFolder(PlantillaIng::class, 'plantillas-ing', 'Privadas', 0),
            ],
            'cvsRegistrados' => [
                'total' => $cvTotal,
                'profesionales' => $cvProf,
                'empresas' => $cvEmp,
            ],
            'gestionDocumental' => $folderCount,
        ];

        // Obtener imagen 360° configurada de forma segura
        $image360 = '/images/360/default-panorama.jpg';
        try {
            if (Schema::hasTable('configurations')) {
                $image360 = Configuration::get('dashboard_360_image', '/images/360/default-panorama.jpg');
            }
        } catch (\Exception $e) {
            Log::warning("Error getting Configuration: " . $e->getMessage());
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'image360' => $image360,
        ]);
    }
}

