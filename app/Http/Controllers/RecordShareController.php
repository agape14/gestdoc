<?php

namespace App\Http\Controllers;

use App\Models\RecordShare;
use Illuminate\Http\Request;

class RecordShareController extends Controller
{
    /**
     * Compartir un registro con otro operador por un tiempo (solo ver por defecto).
     */
    public function store(Request $request)
    {
        $user = auth()->user();
        if ($user->role !== 'Administrador' && $user->role !== 'Operador') {
            return redirect()->back()->with('error', 'No tienes permiso para compartir.');
        }

        $validated = $request->validate([
            'shareable_type' => 'required|string|max:100',
            'shareable_id' => 'required|integer',
            'target_user_id' => 'required|exists:users,id',
            'expires_at' => 'nullable|date|after:now',
            'can_edit' => 'boolean',
        ]);

        $validated['user_id'] = $user->id;
        $validated['can_edit'] = $request->boolean('can_edit', false);

        // Solo operadores pueden ser destino (o admin)
        $target = \App\Models\User::findOrFail($validated['target_user_id']);
        if ($target->id === $user->id) {
            return redirect()->back()->with('error', 'No puedes compartir contigo mismo.');
        }

        RecordShare::updateOrCreate(
            [
                'user_id' => $user->id,
                'target_user_id' => $validated['target_user_id'],
                'shareable_type' => $validated['shareable_type'],
                'shareable_id' => $validated['shareable_id'],
            ],
            [
                'expires_at' => $validated['expires_at'] ?? null,
                'can_edit' => $validated['can_edit'],
            ]
        );

        return redirect()->back()->with('success', 'Registro compartido correctamente.');
    }
}
