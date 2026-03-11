<?php

namespace App\Traits;

use App\Models\Folder;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Model;

/**
 * Trait para mover registros (uno o varios) a otra carpeta en módulos con folder_id.
 * Uso: en el controlador definir move() y moveBulk() que llamen a moveItem() y moveBulkItems().
 */
trait MovesToFolder
{
    /**
     * Mover un registro a otra carpeta.
     * @param Request $request
     * @param Model $model Instancia del modelo (Licitacion, EspecialistaConsultoria, etc.)
     * @param string $module Constante MODULE del controlador (ej. 'especialistas-consultoria')
     * @param string|null $redirectRoute Nombre de ruta para redirección (ej. 'especialistas-consultoria.index')
     */
    protected function moveItem(Request $request, Model $model, string $module, string $redirectRoute = null)
    {
        if ($request->user()->role === 'Visualizador') {
            abort(403, 'No tienes permiso.');
        }
        $validated = $request->validate([
            'folder_id' => 'required|exists:folders,id',
        ]);
        $folder = Folder::where('module', $module)->findOrFail($validated['folder_id']);
        $model->update(['folder_id' => $folder->id]);
        $redirectRoute = $redirectRoute ?: $module . '.index';
        if ($request->header('X-Inertia')) {
            return back()->with('success', 'Registro movido.');
        }
        return redirect()->route($redirectRoute, $model->folder_id ? ['folder_id' => $model->folder_id] : [])->with('success', 'Registro movido.');
    }

    /**
     * Mover varios registros a otra carpeta.
     * @param Request $request
     * @param string $modelClass Clase del modelo (ej. EspecialistaConsultoria::class)
     * @param string $module Constante MODULE del controlador
     * @param string $idKey Clave del request para los IDs (por defecto 'item_ids')
     * @param string|null $redirectRoute Nombre de ruta para redirección
     */
    protected function moveBulkItems(Request $request, string $modelClass, string $module, string $idKey = 'item_ids', string $redirectRoute = null)
    {
        if ($request->user()->role === 'Visualizador') {
            abort(403, 'No tienes permiso.');
        }
        $validated = $request->validate([
            $idKey => 'required|array',
            $idKey . '.*' => 'required|integer',
            'folder_id' => 'required|exists:folders,id',
        ]);
        $folder = Folder::where('module', $module)->findOrFail($validated['folder_id']);
        $ids = $validated[$idKey];
        $updated = $modelClass::whereIn('id', $ids)->update(['folder_id' => $folder->id]);
        $redirectRoute = $redirectRoute ?: $module . '.index';
        $message = $updated > 0 ? "Se movieron {$updated} registro(s) correctamente." : 'No se movió ningún registro.';
        if ($request->header('X-Inertia')) {
            return back()->with('success', $message);
        }
        return redirect()->route($redirectRoute, ['folder_id' => $folder->id])->with('success', $message);
    }
}
