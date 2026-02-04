<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use App\Models\Contrato;
use App\Models\Licitacion;
use App\Models\Curriculum;
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
use App\Models\Document;
use App\Models\RecordShare;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ConfigurationController extends Controller
{
    /**
     * Mostrar la página de configuración de imagen 360
     */
    public function image360()
    {
        $currentImage = Configuration::get('dashboard_360_image', '/images/360/default-panorama.jpg');

        return Inertia::render('Configuration/Image360', [
            'currentImage' => $currentImage,
        ]);
    }

    /**
     * Actualizar la imagen 360
     */
    public function updateImage360(Request $request)
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png|max:51200', // max 50MB
        ], [
            'image.required' => 'Debes seleccionar una imagen.',
            'image.image' => 'El archivo debe ser una imagen.',
            'image.mimes' => 'La imagen debe ser JPG, JPEG o PNG.',
            'image.max' => 'La imagen no debe superar los 50MB.',
        ]);

        try {
            // Eliminar imagen anterior si existe y no es la predeterminada
            $oldImage = Configuration::get('dashboard_360_image');
            if ($oldImage && $oldImage !== '/images/360/default-panorama.jpg') {
                $oldPath = public_path($oldImage);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Guardar nueva imagen
            $image = $request->file('image');
            $filename = 'panorama-' . time() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('images/360'), $filename);

            $imagePath = '/images/360/' . $filename;

            // Actualizar configuración
            Configuration::set('dashboard_360_image', $imagePath, 'image', 'Imagen panorámica 360° del dashboard');

            return redirect()->back()->with('success', 'Imagen 360° actualizada correctamente.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al subir la imagen: ' . $e->getMessage());
        }
    }

    /**
     * Restaurar imagen predeterminada
     */
    public function restoreDefault360()
    {
        try {
            // Eliminar imagen actual si existe y no es la predeterminada
            $currentImage = Configuration::get('dashboard_360_image');
            if ($currentImage && $currentImage !== '/images/360/default-panorama.jpg') {
                $oldPath = public_path($currentImage);
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }

            // Restaurar imagen predeterminada
            Configuration::set('dashboard_360_image', '/images/360/default-panorama.jpg', 'image', 'Imagen panorámica 360° del dashboard');

            return redirect()->back()->with('success', 'Imagen restaurada a la predeterminada.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error al restaurar la imagen: ' . $e->getMessage());
        }
    }

    /**
     * Página de reseteo de datos (solo Administrador)
     */
    public function resetData()
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'Administrador') {
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para acceder.');
        }
        return Inertia::render('Configuration/ResetData');
    }

    /**
     * Ejecutar borrado de todos los datos ingresados para dejar contadores en 0 (solo Administrador)
     */
    public function executeResetData(Request $request)
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'Administrador') {
            return redirect()->route('dashboard')->with('error', 'No tienes permiso para esta acción.');
        }

        $request->validate([
            'confirmacion' => 'required|string|in:BORRAR TODO',
        ], [
            'confirmacion.in' => 'Debes escribir exactamente BORRAR TODO para confirmar.',
        ]);

        try {
            DB::beginTransaction();

            // Orden: document_files -> documents -> contratos (dependen de folders), módulos, carpetas
            DB::table('document_files')->delete();
            Document::query()->delete();
            Contrato::query()->delete();
            Licitacion::query()->delete();
            ConsultorObra::query()->delete();
            EjecutorObra::query()->delete();
            ProveedorServicio::query()->delete();
            ProveedorBien::query()->delete();
            EspecialistaEjecucion::query()->delete();
            EspecialistaConsultoria::query()->delete();
            Inmobiliaria::query()->delete();
            Topografia::query()->delete();
            Tecnologia::query()->delete();
            PlantillaIng::query()->delete();
            Curriculum::query()->delete();
            RecordShare::query()->delete();
            Folder::query()->delete();

            // Recrear carpetas fijas para módulos: Públicas/Privadas (inmobiliaria, topografia, tecnologia, plantillas-ing) y Profesionales/Empresas (cvs)
            $modulesPublicasPrivadas = ['inmobiliaria', 'topografia', 'tecnologia', 'plantillas-ing'];
            foreach ($modulesPublicasPrivadas as $module) {
                foreach (['Públicas', 'Privadas'] as $name) {
                    DB::table('folders')->insert([
                        'parent_id' => null,
                        'name' => $name,
                        'slug' => Str::slug($name . '-' . $module),
                        'color' => $name === 'Públicas' ? '#E3F2FD' : '#FCE4EC',
                        'icon' => $name === 'Públicas' ? 'Globe' : 'Lock',
                        'description' => null,
                        'is_system' => true,
                        'module' => $module,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
            foreach (['Profesionales', 'Empresas'] as $name) {
                DB::table('folders')->insert([
                    'parent_id' => null,
                    'name' => $name,
                    'slug' => Str::slug($name . '-cvs'),
                    'color' => $name === 'Profesionales' ? '#E8F5E9' : '#FFF3E0',
                    'icon' => 'Building',
                    'description' => null,
                    'is_system' => true,
                    'module' => 'cvs',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }

            DB::commit();
            Log::info('Reset de datos ejecutado por usuario id=' . $user->id);
            return redirect()->route('config.resetData')->with('success', 'Datos eliminados correctamente. Los contadores del dashboard están en 0.');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en reset de datos: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error al eliminar datos: ' . $e->getMessage());
        }
    }
}
