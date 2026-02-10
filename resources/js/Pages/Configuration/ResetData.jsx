import React, { useState } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, router } from '@inertiajs/react';

export default function ResetData({ auth, flash }) {
    const [confirmacion, setConfirmacion] = useState('');
    const [loading, setLoading] = useState(false);

    const isAdmin = auth?.user?.role === 'Administrador';

    const handleSubmit = (e) => {
        e.preventDefault();
        if (confirmacion !== 'BORRAR TODO') {
            alert('Escribe exactamente BORRAR TODO para confirmar.');
            return;
        }
        if (!confirm('¿Estás seguro? Se eliminarán TODOS los datos y TODAS las carpetas creadas (registros y carpetas de todos los módulos). Los contadores quedarán en 0. Esta acción no se puede deshacer.')) {
            return;
        }
        setLoading(true);
        router.post(route('config.resetData.execute'), { confirmacion }, {
            onFinish: () => setLoading(false),
        });
    };

    if (!isAdmin) {
        return null;
    }

    return (
        <MainLayout>
            <Head title="Configuración - Resetear datos" />

            {flash?.success && (
                <div className="alert alert-success alert-dismissible fade show" role="alert">
                    <i className="bi bi-check-circle-fill me-2"></i>
                    {flash.success}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            )}

            {flash?.error && (
                <div className="alert alert-danger alert-dismissible fade show" role="alert">
                    <i className="bi bi-exclamation-triangle-fill me-2"></i>
                    {flash.error}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            )}

            <div className="mb-4">
                <h2 className="fw-bold text-body mb-0">
                    <i className="bi bi-trash3 me-2 text-danger"></i>
                    Resetear datos del sistema
                </h2>
                <p className="text-secondary mb-0">Elimina todos los datos ingresados y deja los contadores del dashboard en 0</p>
            </div>

            <div className="card border-0 shadow-sm rounded-4 border-danger border-opacity-25">
                <div className="card-header bg-danger bg-opacity-10 py-3 px-4 border-bottom">
                    <h5 className="mb-0 fw-bold text-danger">
                        <i className="bi bi-exclamation-triangle-fill me-2"></i>
                        Zona de riesgo
                    </h5>
                </div>
                <div className="card-body p-4">
                    <p className="text-body mb-3">
                        Esta acción eliminará <strong>todos</strong> los registros y <strong>todas las carpetas creadas</strong>:
                    </p>
                    <ul className="mb-4 text-secondary">
                        <li>Licitaciones, Consultor de Obras, Ejecutor de Obra, Proveedor de Servicios/Bienes</li>
                        <li>Especialistas en Ejecución y Consultoría, Inmobiliaria, Topografía, Tecnología, Plantillas de Ing.</li>
                        <li>Banco de CVs, Contratos, Gestión documental (cartas, oficios, memos)</li>
                        <li><strong>Carpetas creadas por operadores y administradores</strong> en todos los módulos (se borran por completo)</li>
                    </ul>
                    <p className="text-danger fw-semibold mb-4">
                        No se eliminan: usuarios ni configuración (imagen 360). Los contadores del inicio quedarán en 0. Tras el reset se recrean automáticamente solo las carpetas fijas del sistema (Públicas/Privadas, Profesionales/Empresas) para los módulos correspondientes.
                    </p>

                    <form onSubmit={handleSubmit}>
                        <div className="mb-4">
                            <label htmlFor="confirmacion" className="form-label fw-semibold">
                                Para confirmar, escribe exactamente: <code className="bg-light px-2 py-1 rounded">BORRAR TODO</code>
                            </label>
                            <input
                                id="confirmacion"
                                type="text"
                                className="form-control form-control-lg"
                                placeholder="BORRAR TODO"
                                value={confirmacion}
                                onChange={(e) => setConfirmacion(e.target.value)}
                                disabled={loading}
                                autoComplete="off"
                            />
                        </div>
                        <button
                            type="submit"
                            className="btn btn-danger"
                            disabled={confirmacion !== 'BORRAR TODO' || loading}
                        >
                            {loading ? (
                                <>
                                    <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                    Eliminando...
                                </>
                            ) : (
                                <>
                                    <i className="bi bi-trash3 me-2"></i>
                                    Eliminar todos los datos y carpetas creadas
                                </>
                            )}
                        </button>
                    </form>
                </div>
            </div>
        </MainLayout>
    );
}
