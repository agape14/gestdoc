<?php

namespace App\Http\Controllers;

use App\Models\Contrato;
use App\Models\Licitacion;
use App\Models\Curriculum;
use App\Models\Configuration;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index()
    {
        // Obtener contadores reales de la base de datos
        $stats = [
            'totalContratos' => Contrato::count(),
            'licitacionesEnCurso' => Licitacion::where('estado', 'En Curso')->count(),
            'cvsRegistrados' => Curriculum::count(),
        ];

        // Obtener imagen 360° configurada
        $image360 = Configuration::get('dashboard_360_image', '/images/360/default-panorama.jpg');

        return Inertia::render('Dashboard', [
            'stats' => $stats,
            'image360' => $image360,
        ]);
    }
}

