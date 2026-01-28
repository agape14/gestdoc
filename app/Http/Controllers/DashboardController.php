<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Licitacion;
use App\Models\Curriculum;
use App\Models\Configuration;
use Inertia\Inertia;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

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
    public function index()
    {
        // Función helper para contar de forma segura
        $safeCount = function($model, $default = 0) {
            try {
                return $model::count();
            } catch (\Exception $e) {
                \Log::warning("Error counting {$model}: " . $e->getMessage());
                return $default;
            }
        };

        $safeCountWhere = function($model, $column, $value, $default = 0) {
            try {
                return $model::where($column, $value)->count();
            } catch (\Exception $e) {
                \Log::warning("Error counting {$model} where {$column}={$value}: " . $e->getMessage());
                return $default;
            }
        };

        // Obtener contadores reales de la base de datos con manejo de errores
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
            'inmobiliaria' => $safeCount(Inmobiliaria::class),
            'topografia' => $safeCount(Topografia::class),
            'tecnologia' => $safeCount(Tecnologia::class),
            'plantillasIng' => $safeCount(PlantillaIng::class),
            'cvsRegistrados' => [
                'total' => $safeCount(Curriculum::class),
                'profesionales' => function() use ($safeCountWhere) {
                    try {
                        return Curriculum::where('tipo', 'Profesional')->orWhereNull('tipo')->count();
                    } catch (\Exception $e) {
                        \Log::warning("Error counting Curriculum profesionales: " . $e->getMessage());
                        return 0;
                    }
                }(),
                'empresas' => $safeCountWhere(Curriculum::class, 'tipo', 'Empresa'),
            ],
            'gestionDocumental' => $safeCount(Folder::class),
        ];

        // Obtener imagen 360° configurada de forma segura
        $image360 = '/images/360/default-panorama.jpg';
        try {
            if (\Schema::hasTable('configurations')) {
                $image360 = Configuration::get('dashboard_360_image', '/images/360/default-panorama.jpg');
            }
        } catch (\Exception $e) {
            \Log::warning("Error getting Configuration: " . $e->getMessage());
        }

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'image360' => $image360,
        ]);
    }
}

