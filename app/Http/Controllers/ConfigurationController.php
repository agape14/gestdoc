<?php

namespace App\Http\Controllers;

use App\Models\Configuration;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Storage;

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
}
