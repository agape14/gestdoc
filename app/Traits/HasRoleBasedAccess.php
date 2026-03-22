<?php

namespace App\Traits;

use App\Models\RecordShare;
use Illuminate\Http\Request;

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

    /**
     * Exportación Excel (listados): el operador solo exporta registros que él creó (user_id = su id).
     * El administrador exporta todos los registros o, si envía user_id, solo los de ese operador.
     * El visualizador no obtiene filas (0 = 1).
     */
    protected function applyExportRoleFilter($query, $user, ?Request $request = null)
    {
        if ($user->role === 'Administrador') {
            if ($request && $request->filled('user_id')) {
                $query->where('user_id', (int) $request->user_id);
            }

            return $query;
        }
        if ($user->role === 'Operador') {
            return $query->where('user_id', $user->id);
        }

        return $query->whereRaw('0 = 1');
    }

    /**
     * Exportar un solo registro a Excel: operador solo si es dueño del registro.
     */
    protected function assertCanExportOwnedRecord(object $record, $user): void
    {
        if ($user->role === 'Administrador') {
            return;
        }
        if ($user->role === 'Operador' && isset($record->user_id) && (int) $record->user_id === (int) $user->id) {
            return;
        }
        abort(403, 'No autorizado para exportar este registro.');
    }
}
