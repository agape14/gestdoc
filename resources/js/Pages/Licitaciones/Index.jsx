import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router } from '@inertiajs/react';
import Swal from 'sweetalert2';

export default function Index({ licitaciones, filters, flash }) {
    const [search, setSearch] = useState(filters.search || '');
    const [dateStart, setDateStart] = useState(filters.date_start || '');
    const [dateEnd, setDateEnd] = useState(filters.date_end || '');

    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== (filters.search || '') || dateStart !== (filters.date_start || '') || dateEnd !== (filters.date_end || '')) {
                router.get(route('licitaciones.index'), { search, date_start: dateStart, date_end: dateEnd }, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search, dateStart, dateEnd]);

    const handleDelete = (id) => {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "No podrás revertir esta acción",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('licitaciones.destroy', id));
            }
        });
    };

    return (
        <MainLayout>
            <Head title="Licitaciones" />

            {flash?.success && (
                <div className="alert alert-success alert-dismissible fade show" role="alert">
                    {flash.success}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            )}

            <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 className="fw-bold text-body mb-0">Licitaciones</h2>
                    <p className="text-secondary mb-0">Gestión de licitaciones en curso e historial</p>
                </div>
                <Link href={route('licitaciones.create')} className="btn btn-primary shadow-sm rounded-pill px-4">
                    <i className="bi bi-plus-lg me-2"></i>
                    Nueva Licitación
                </Link>
            </div>

            <div className="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-body">
                <div className="row g-3 items-center">
                    <div className="col-lg-6">
                        <div className="input-group">
                            <span className="input-group-text bg-body-tertiary border-end-0 rounded-start-pill ps-3"><i className="bi bi-search text-secondary"></i></span>
                            <input
                                type="text"
                                className="form-control border-start-0 bg-body-tertiary rounded-end-pill"
                                placeholder="Buscar por título o entidad..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </div>
                    <div className="col-lg-3">
                        <input
                            type="date"
                            className="form-control rounded-pill bg-body-tertiary border-0 px-3"
                            placeholder="Fecha Inicio"
                            value={dateStart}
                            onChange={(e) => setDateStart(e.target.value)}
                        />
                    </div>
                    <div className="col-lg-3">
                        <input
                            type="date"
                            className="form-control rounded-pill bg-body-tertiary border-0 px-3"
                            placeholder="Fecha Fin"
                            value={dateEnd}
                            onChange={(e) => setDateEnd(e.target.value)}
                        />
                    </div>
                </div>
            </div>

            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
                <div className="table-responsive">
                    <table className="table table-hover align-middle mb-0">
                        <thead className="border-bottom text-secondary small text-uppercase">
                            <tr>
                                <th scope="col" className="ps-4 py-3">Título</th>
                                <th scope="col" className="py-3">Entidad</th>
                                <th scope="col" className="py-3">Presupuesto</th>
                                <th scope="col" className="py-3">Estado</th>
                                <th scope="col" className="text-end pe-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {licitaciones.data.length > 0 ? licitaciones.data.map(licitacion => (
                                <tr key={licitacion.id}>
                                    <td className="ps-4 py-3 fw-medium text-body">{licitacion.titulo}</td>
                                    <td className="text-secondary">{licitacion.entidad}</td>
                                    <td className="text-secondary fw-bold text-body">
                                        S/ {parseFloat(licitacion.presupuesto || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} PEN
                                    </td>
                                    <td>
                                        <span className={`badge bg-${licitacion.estado === 'En Curso' ? 'warning' : 'success'}-subtle text-${licitacion.estado === 'En Curso' ? 'warning-emphasis' : 'success'} border border-${licitacion.estado === 'En Curso' ? 'warning' : 'success'}-subtle rounded-pill px-3`}>
                                            {licitacion.estado}
                                        </span>
                                    </td>
                                    <td className="text-end pe-4">
                                        <Link href={route('licitaciones.edit', licitacion.id)} className="btn btn-sm btn-outline-secondary me-1">
                                            <i className="bi bi-pencil"></i>
                                        </Link>
                                        <button onClick={() => handleDelete(licitacion.id)} className="btn btn-sm btn-outline-danger">
                                            <i className="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="5" className="text-center py-5 text-muted">No se encontraron licitaciones.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {/* Simple Pagination */}
                {licitaciones.links.length > 3 && (
                    <div className="card-footer bg-body border-top-0 py-3">
                        <nav aria-label="Page navigation">
                            <ul className="pagination justify-content-center mb-0">
                                {licitaciones.links.map((link, key) => (
                                    <li key={key} className={`page-item ${link.active ? 'active' : ''} ${!link.url ? 'disabled' : ''}`}>
                                        <Link
                                            className="page-link"
                                            href={link.url || '#'}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    </div>
                )}
            </div>
        </MainLayout>
    );
}
