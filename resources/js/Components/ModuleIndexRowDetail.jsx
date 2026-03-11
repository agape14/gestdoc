import React from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';

/**
 * Panel desplegable reutilizable para ModuleIndex: muestra campos adicionales y botones Editar / Eliminar.
 * Clic en la fila para expandir. Responsive: datos principales en tabla, resto + acciones aquí.
 */
export default function ModuleIndexRowDetail({ item, userRole, fields, editHref, deleteRouteName, onDelete, documentButton = null }) {
    const auth = usePage().props?.auth;

    const canEdit = () => {
        if (userRole === 'Administrador') return true;
        if (userRole === 'Operador') return item.user_id == null || item.user_id === auth?.user?.id;
        return false;
    };

    const canDeleteItem = () => {
        if (userRole === 'Administrador') return true;
        if (userRole === 'Operador') return item.user_id == null || item.user_id === auth?.user?.id;
        return false;
    };

    const handleDeleteClick = () => {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'No podrás revertir esta acción.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (result.isConfirmed && onDelete) onDelete(item);
            else if (result.isConfirmed && deleteRouteName) {
                router.delete(route(deleteRouteName, item.id));
            }
        });
    };

    return (
        <div className="row g-3">
            <div className="col-12 col-lg-8">
                <div className="row g-2 small">
                    {fields.map((f, i) => (
                        (f.value != null && f.value !== '') && (
                            <div key={i} className="col-12 col-sm-6">
                                <span className="text-secondary fw-medium">{f.label}:</span>{' '}
                                <span className="text-break">
                                    {typeof f.value === 'string' && f.value.length > 80 ? f.value.slice(0, 80) + '…' : f.value}
                                </span>
                            </div>
                        )
                    ))}
                </div>
            </div>
            <div className="col-12 col-lg-4 d-flex flex-wrap align-items-center gap-2 justify-content-lg-end">
                {userRole === 'Visualizador' ? (
                    <span className="badge bg-secondary">Solo lectura</span>
                ) : (
                    <>
                        {documentButton}
                        {canEdit() && editHref && (
                            <Link href={editHref} className="btn btn-sm btn-outline-primary" onClick={(e) => e.stopPropagation()}>
                                <i className="bi bi-pencil me-1"></i> Editar
                            </Link>
                        )}
                        {canDeleteItem() && (onDelete || deleteRouteName) && (
                            <button
                                type="button"
                                className="btn btn-sm btn-outline-danger"
                                onClick={(e) => { e.stopPropagation(); handleDeleteClick(); }}
                            >
                                <i className="bi bi-trash me-1"></i> Eliminar
                            </button>
                        )}
                    </>
                )}
            </div>
        </div>
    );
}
