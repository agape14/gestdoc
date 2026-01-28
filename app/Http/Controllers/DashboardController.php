<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Licitacion;
use App\Models\Curriculum;
use App\Models\Configuration;
use Inertia\Inertia;

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
        // Obtener contadores reales de la base de datos
        // Nota: Agrupamos por tipo (Publica/Privada) donde aplica para mostrar detalles si es necesario en el futuro
        $stats = [
            'licitaciones' => [
                'total' => Licitacion::count(),
                'publicas' => Licitacion::where('tipo', 'Publica')->count(),
                'privadas' => Licitacion::where('tipo', 'Privada')->count(),
            ],
            'consultorObras' => [
                'total' => ConsultorObra::count(),
                'publicas' => ConsultorObra::where('categoria', 'Publica')->count(),
                'privadas' => ConsultorObra::where('categoria', 'Privada')->count(),
            ],
            'ejecutorObras' => [
                'total' => EjecutorObra::count(),
                'publicas' => EjecutorObra::where('categoria', 'Publica')->count(),
                'privadas' => EjecutorObra::where('categoria', 'Privada')->count(),
            ],
            'proveedorServicios' => [
                'total' => ProveedorServicio::count(),
                'publicas' => ProveedorServicio::where('categoria', 'Publica')->count(),
                'privadas' => ProveedorServicio::where('categoria', 'Privada')->count(),
            ],
            'proveedorBienes' => [
                'total' => ProveedorBien::count(),
                'publicas' => ProveedorBien::where('categoria', 'Publica')->count(),
                'privadas' => ProveedorBien::where('categoria', 'Privada')->count(),
            ],
            'especialistasEjecucion' => [
                'total' => EspecialistaEjecucion::count(),
                'profesionales' => EspecialistaEjecucion::where('tipo', 'Profesional')->count(),
                'empresas' => EspecialistaEjecucion::where('tipo', 'Empresa')->count(),
            ],
            'especialistasConsultoria' => [
                'total' => EspecialistaConsultoria::count(),
                'profesionales' => EspecialistaConsultoria::where('tipo', 'Profesional')->count(),
                'empresas' => EspecialistaConsultoria::where('tipo', 'Empresa')->count(),
            ],
            'inmobiliaria' => Inmobiliaria::count(),
            'topografia' => Topografia::count(),
            'tecnologia' => Tecnologia::count(),
            'plantillasIng' => PlantillaIng::count(),
            'cvsRegistrados' => [
                'total' => Curriculum::count(),
                'profesionales' => Curriculum::where('tipo', 'Profesional')->orWhereNull('tipo')->count(),
                'empresas' => Curriculum::where('tipo', 'Empresa')->count(),
            ],
            'gestionDocumental' => Folder::count(),
        ];

        // Obtener imagen 360° configurada (Mantener si se usa, o si se desea reemplazar por video en frontend)
        $image360 = Configuration::get('dashboard_360_image', '/images/360/default-panorama.jpg');

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'image360' => $image360,
        ]);
    }
}

