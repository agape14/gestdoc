import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';

export default function ModuleIndex({ title, description, items, columns, createRoute, onCreate, filters, routeParams = {}, renderDetail, editRoute, deleteRoute, userRole }) {
    const { auth } = usePage().props;
    const currentUserRole = userRole || auth?.user?.role || 'Visualizador';
    const [search, setSearch] = useState('');
    const [currentFilters, setCurrentFilters] = useState(routeParams);
    const [expandedRow, setExpandedRow] = useState(null);

    const handleRowClick = (id) => {
        setExpandedRow(expandedRow === id ? null : id);
    };

    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== (routeParams.search || '')) {
                router.get(window.location.pathname, { ...currentFilters, search }, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search]);

    const handleFilterClick = (key, value) => {
        const newFilters = { ...currentFilters, [key]: value };
        setCurrentFilters(newFilters);
        router.get(window.location.pathname, newFilters, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const canEdit = (item) => {
        if (currentUserRole === 'Administrador') return true;
        if (currentUserRole === 'Operador') {
            return item.user_id === auth?.user?.id;
        }
        return false;
    };

    const canDelete = (item) => {
        if (currentUserRole === 'Administrador') return true;
        if (currentUserRole === 'Operador') {
            return item.user_id === auth?.user?.id;
        }
        return false;
    };

    const handleDelete = (item, routeName) => {
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
                router.delete(route(routeName, item.id));
            }
        });
    };

    return (
        <MainLayout>
            <Head title={title} />

            <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 className="fw-bold text-body mb-0">{title}</h2>
                    <p className="text-secondary mb-0">{description}</p>
                </div>
            </div>

            {
                filters && filters.length > 0 && (
                    <div className="row g-4 mb-4">
                        {filters.map((f, i) => (
                            <div key={i} className="col-md-6 col-xl-3">
                                <div
                                    onClick={() => handleFilterClick(f.key, f.value)}
                                    className={`card border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative ${currentFilters[f.key] === f.value ? 'ring-2 ring-primary' : ''}`}
                                    style={{ cursor: 'pointer', transition: 'transform 0.2s' }}
                                    onMouseEnter={(e) => e.currentTarget.style.transform = 'scale(1.02)'}
                                    onMouseLeave={(e) => e.currentTarget.style.transform = 'scale(1)'}
                                >
                                    <div className={`card-body p-4 d-flex align-items-center gap-3 ${currentFilters[f.key] === f.value ? 'bg-primary-subtle' : 'bg-body'}`}>
                                        <div className={`p-3 rounded-circle ${currentFilters[f.key] === f.value ? 'bg-primary text-white' : 'bg-secondary bg-opacity-10 text-secondary'}`}>
                                            <i className={`bi ${f.icon} fs-4`}></i>
                                        </div>
                                        <div>
                                            <h6 className="fw-bold mb-1">{f.label}</h6>
                                            <small className="text-secondary">Filtrar por {f.label.toLowerCase()}</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                )
            }

            <div className="d-flex justify-content-end mb-4">
                {(createRoute || onCreate) && currentUserRole !== 'Visualizador' && (
                    <button
                        onClick={() => onCreate ? onCreate() : router.visit(createRoute)}
                        className="btn btn-primary shadow-sm rounded-pill px-4"
                    >
                        <i className="bi bi-plus-lg me-2"></i>
                        Nuevo Registro
                    </button>
                )}
            </div>

            <div className="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-body">
                <div className="input-group">
                    <span className="input-group-text bg-body-tertiary border-end-0 rounded-start-pill ps-3"><i className="bi bi-search text-secondary"></i></span>
                    <input
                        type="text"
                        className="form-control border-start-0 bg-body-tertiary rounded-end-pill"
                        placeholder="Buscar..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>
            </div>

            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
                <div className="table-responsive">
                    <table className="table table-hover align-middle mb-0">
                        <thead className="border-bottom text-secondary small text-uppercase">
                            <tr>
                                {columns.map((col, i) => (
                                    <th key={i} scope="col" className={`py-3 ${i === 0 ? 'ps-4' : ''} ${i === columns.length - 1 ? 'text-end pe-4' : ''}`}>
                                        {col.header}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody>
                            {items.data.length > 0 ? items.data.map((item, idx) => (
                                <React.Fragment key={item.id || idx}>
                                    <tr
                                        className={renderDetail ? 'cursor-pointer' : ''}
                                        onClick={() => renderDetail && handleRowClick(item.id)}
                                        style={{ backgroundColor: expandedRow === item.id ? 'var(--bs-light)' : 'transparent' }}
                                    >
                                        {columns.map((col, i) => {
                                            // Si es la columna de acciones y no tiene render personalizado, usar el render por defecto
                                            if (col.header === 'ACCIONES' && !col.render && (editRoute || deleteRoute)) {
                                                return (
                                                    <td key={i} className="text-end pe-4">
                                                        <div className="d-flex gap-1 justify-content-end">
                                                            {currentUserRole === 'Visualizador' ? (
                                                                <button className="btn btn-sm btn-outline-info" title="Ver">
                                                                    <i className="bi bi-eye"></i>
                                                                </button>
                                                            ) : (
                                                                <>
                                                                    {canEdit(item) && editRoute && (
                                                                        <Link href={editRoute(item.id)} className="btn btn-sm btn-outline-secondary" title="Editar">
                                                                            <i className="bi bi-pencil"></i>
                                                                        </Link>
                                                                    )}
                                                                    {canDelete(item) && deleteRoute && (
                                                                        <button
                                                                            onClick={(e) => {
                                                                                e.stopPropagation();
                                                                                handleDelete(item, deleteRoute);
                                                                            }}
                                                                            className="btn btn-sm btn-outline-danger"
                                                                            title="Eliminar"
                                                                        >
                                                                            <i className="bi bi-trash"></i>
                                                                        </button>
                                                                    )}
                                                                    {!canEdit(item) && !canDelete(item) && (
                                                                        <button className="btn btn-sm btn-outline-info" title="Ver">
                                                                            <i className="bi bi-eye"></i>
                                                                        </button>
                                                                    )}
                                                                </>
                                                            )}
                                                        </div>
                                                    </td>
                                                );
                                            }
                                            return (
                                                <td key={i} className={`${i === 0 ? 'ps-4 fw-medium text-body' : ''} ${i === columns.length - 1 ? 'text-end pe-4' : ''}`}>
                                                    {col.render ? col.render(item) : (item[col.accessor] || '-')}
                                                </td>
                                            );
                                        })}
                                    </tr>
                                    {renderDetail && expandedRow === item.id && (
                                        <tr className="bg-light animate-fade-in">
                                            <td colSpan={columns.length} className="p-0 border-0">
                                                <div className="p-4 border-bottom border-light shadow-inner">
                                                    {renderDetail(item)}
                                                </div>
                                            </td>
                                        </tr>
                                    )}
                                </React.Fragment>
                            )) : (
                                <tr>
                                    <td colSpan={columns.length} className="text-center py-5 text-muted">No se encontraron registros.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {items.links && items.links.length > 3 && (
                    <div className="card-footer bg-body border-top-0 py-3">
                        <nav aria-label="Page navigation">
                            <ul className="pagination justify-content-center mb-0">
                                {items.links.map((link, key) => (
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
        </MainLayout >
    );
}
