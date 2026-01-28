<?php

namespace App\Traits;

trait HasRoleBasedAccess
{
    /**
     * Aplicar filtros según el rol del usuario
     */
    protected function applyRoleBasedFilter($query, $user)
    {
        if ($user->role === 'Administrador') {
            // Administrador ve todos los registros
            return $query;
        } elseif ($user->role === 'Operador') {
            // Operador solo ve sus propios registros
            return $query->where('user_id', $user->id);
        } else {
            // Visualizador ve todos pero solo lectura
            return $query;
        }
    }

    /**
     * Verificar si el usuario puede editar un registro
     */
    protected function canEdit($record, $user)
    {
        if ($user->role === 'Administrador') {
            return true;
        } elseif ($user->role === 'Operador') {
            return $record->user_id === $user->id;
        }
        return false; // Visualizador no puede editar
    }

    /**
     * Verificar si el usuario puede eliminar un registro
     */
    protected function canDelete($record, $user)
    {
        if ($user->role === 'Administrador') {
            return true;
        } elseif ($user->role === 'Operador') {
            return $record->user_id === $user->id;
        }
        return false; // Visualizador no puede eliminar
    }

    /**
     * Verificar si el usuario puede crear registros
     */
    protected function canCreate($user)
    {
        return in_array($user->role, ['Administrador', 'Operador']);
    }
}
