<?php

namespace App\Traits;

use App\Models\RecordShare;

trait HasRoleBasedAccess
{
    /**
     * Aplicar filtros según el rol del usuario.
     * Operador: solo sus registros (user_id = su id) o legacy (user_id null).
     */
    protected function applyRoleBasedFilter($query, $user)
    {
        if ($user->role === 'Administrador') {
            return $query;
        } elseif ($user->role === 'Operador') {
            return $query->where(function ($q) use ($user) {
                $q->where('user_id', $user->id)->orWhereNull('user_id');
            });
        } else {
            return $query;
        }
    }

    /**
     * Aplicar filtros incluyendo registros compartidos con el operador (solo lectura por defecto).
     * $modelClass ej: ProveedorServicio::class
     */
    protected function applyRoleBasedFilterWithShared($query, $user, $modelClass)
    {
        if ($user->role === 'Administrador') {
            return $query;
        } elseif ($user->role === 'Operador') {
            $sharedIds = RecordShare::valid()
                ->where('target_user_id', $user->id)
                ->where('shareable_type', $modelClass)
                ->pluck('shareable_id');
            return $query->where(function ($q) use ($user, $sharedIds) {
                $q->where('user_id', $user->id);
                if ($sharedIds->isNotEmpty()) {
                    $q->orWhereIn('id', $sharedIds);
                }
            });
        } else {
            return $query;
        }
    }

    /**
     * IDs de registros compartidos con el usuario actual en solo lectura (can_edit = false).
     */
    protected function sharedReadOnlyIds($user, $modelClass): array
    {
        if ($user->role !== 'Operador') {
            return [];
        }
        return RecordShare::valid()
            ->where('target_user_id', $user->id)
            ->where('shareable_type', $modelClass)
            ->where('can_edit', false)
            ->pluck('shareable_id')
            ->all();
    }

    /**
     * Verificar si el usuario puede editar un registro.
     * Administrador: todos. Operador: solo los suyos (user_id = su id) o legacy (user_id null). Incluye compartidos con can_edit.
     */
    protected function canEdit($record, $user)
    {
        if ($user->role === 'Administrador') {
            return true;
        } elseif ($user->role === 'Operador') {
            if ($record->user_id === null || (int) $record->user_id === (int) $user->id) {
                return true;
            }
            $share = RecordShare::valid()
                ->where('target_user_id', $user->id)
                ->where('shareable_type', get_class($record))
                ->where('shareable_id', $record->id)
                ->first();
            return $share && $share->can_edit;
        }
        return false;
    }

    /**
     * Verificar si el usuario puede eliminar/anular un registro.
     * Administrador: todos. Operador: solo los suyos (user_id = su id) o legacy (user_id null).
     */
    protected function canDelete($record, $user)
    {
        if ($user->role === 'Administrador') {
            return true;
        }
        if ($user->role === 'Operador') {
            $recordUserId = $record->user_id ?? null;
            $userId = (int) $user->id;
            return $recordUserId === null || (int) $recordUserId === $userId;
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
