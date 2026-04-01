import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import UserFolderPermissionsModal from '@/Components/UserFolderPermissionsModal';
import { GridPerPageSelect, SortTh } from '@/Components/GridTableControls';

export default function Index({ users, filters, flash }) {
    const [search, setSearch] = useState(filters.search || '');
    const [dateStart, setDateStart] = useState(filters.date_start || '');
    const [dateEnd, setDateEnd] = useState(filters.date_end || '');
    const [roleFilter, setRoleFilter] = useState(filters.role || '');
    const [folderModalUser, setFolderModalUser] = useState(null);

    const sortField = filters.sort || 'created_at';
    const sortDirection = filters.direction === 'asc' ? 'asc' : 'desc';
    const navigateList = (extra = {}) => {
        router.get(route('config'), {
            ...filters,
            search,
            date_start: dateStart,
            date_end: dateEnd,
            role: roleFilter || undefined,
            ...extra,
        }, { preserveState: true, preserveScroll: true, replace: true });
    };
    const toggleSort = (field) => {
        const nextDir = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        navigateList({ sort: field, direction: nextDir, page: 1 });
    };

    useEffect(() => {
        const timer = setTimeout(() => {
            const params = { ...filters, search, date_start: dateStart, date_end: dateEnd, role: roleFilter || undefined };
            const hasChanges =
                search !== (filters.search || '') ||
                dateStart !== (filters.date_start || '') ||
                dateEnd !== (filters.date_end || '') ||
                roleFilter !== (filters.role || '');
            if (hasChanges) {
                router.get(route('config'), { ...params, page: 1 }, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search, dateStart, dateEnd, roleFilter]);

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
                router.delete(route('users.destroy', id));
            }
        });
    };

    return (
        <MainLayout>
            <Head title="Configuración - Usuarios" />
            <div className="grid-page-wrapper min-w-0 w-100" style={{ maxWidth: '100%' }}>
            {flash?.success && (
                <div className="alert alert-success alert-dismissible fade show" role="alert">
                    {flash.success}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            )}

            {flash?.error && (
                <div className="alert alert-danger alert-dismissible fade show" role="alert">
                    {flash.error}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            )}

            <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 className="fw-bold text-body mb-0">Gestión de Usuarios</h2>
                    <p className="text-secondary mb-0">Configuración de acceso al sistema</p>
                </div>
                <Link href={route('users.create')} className="btn btn-primary shadow-sm rounded-pill px-4">
                    <i className="bi bi-person-plus me-2"></i>
                    Nuevo Usuario
                </Link>
            </div>

            <div className="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-body min-w-0 w-100">
                <div className="row g-3 items-center">
                    <div className="col-12 col-lg-4">
                        <div className="input-group min-w-0">
                            <span className="input-group-text bg-body-tertiary border-end-0 rounded-start-pill ps-3"><i className="bi bi-search text-secondary"></i></span>
                            <input
                                type="text"
                                className="form-control border-start-0 bg-body-tertiary rounded-end-pill"
                                placeholder="Buscar por nombre, email o rol..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </div>
                    <div className="col-12 col-md-6 col-lg-2">
                        <select
                            className="form-select rounded-pill bg-body-tertiary border-0 px-3"
                            value={roleFilter}
                            onChange={(e) => setRoleFilter(e.target.value)}
                            title="Filtrar por rol"
                        >
                            <option value="">Todos los roles</option>
                            <option value="Administrador">Administrador</option>
                            <option value="Operador">Operador</option>
                            <option value="Visualizador">Visualizador</option>
                        </select>
                    </div>
                    <div className="col-12 col-md-6 col-lg-3">
                        <input
                            type="date"
                            className="form-control rounded-pill bg-body-tertiary border-0 px-3"
                            placeholder="Fecha Inicio"
                            value={dateStart}
                            onChange={(e) => setDateStart(e.target.value)}
                        />
                    </div>
                    <div className="col-12 col-md-6 col-lg-3">
                        <input
                            type="date"
                            className="form-control rounded-pill bg-body-tertiary border-0 px-3"
                            placeholder="Fecha Fin"
                            value={dateEnd}
                            onChange={(e) => setDateEnd(e.target.value)}
                        />
                    </div>
                    <div className="col-12 col-md-6 col-lg-auto d-flex align-items-end">
                        <GridPerPageSelect value={String(filters.per_page ?? '50')} onChange={(v) => navigateList({ per_page: v, page: 1 })} />
                    </div>
                </div>
            </div>

            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body min-w-0 w-100">
                <div className="table-responsive overflow-x-auto min-w-0" style={{ WebkitOverflowScrolling: 'touch' }}>
                    <table className="table table-hover align-middle mb-0" style={{ minWidth: '560px' }}>
                        <thead className="border-bottom text-secondary small text-uppercase">
                            <tr>
                                <SortTh label="Nombre" field="name" currentSort={sortField} currentDirection={sortDirection} onSort={toggleSort} className="ps-4 py-3" />
                                <SortTh label="Email" field="email" currentSort={sortField} currentDirection={sortDirection} onSort={toggleSort} className="py-3" />
                                <SortTh label="Rol" field="role" currentSort={sortField} currentDirection={sortDirection} onSort={toggleSort} className="py-3" />
                                <SortTh label="Fecha Registro" field="created_at" currentSort={sortField} currentDirection={sortDirection} onSort={toggleSort} className="py-3" />
                                <th scope="col" className="text-end pe-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {users.data.length > 0 ? users.data.map(user => (
                                <tr key={user.id}>
                                    <td className="ps-4 py-3 fw-medium text-body">{user.name}</td>
                                    <td className="text-secondary">{user.email}</td>
                                    <td><span className="badge bg-secondary-subtle text-secondary border rounded-pill px-3">{user.role}</span></td>
                                    <td className="text-secondary">{new Date(user.created_at).toLocaleDateString('es-PE', { timeZone: 'America/Lima' })}</td>
                                    <td className="text-end pe-4">
                                        <Link href={route('users.edit', user.id)} className="btn btn-sm btn-outline-secondary me-1" title="Editar">
                                            <i className="bi bi-pencil"></i>
                                        </Link>
                                        {user.role === 'Visualizador' && (
                                            <button
                                                type="button"
                                                onClick={() => setFolderModalUser({ id: user.id, name: user.name })}
                                                className="btn btn-sm btn-outline-primary me-1"
                                                title="Carpetas visibles (por menú)"
                                            >
                                                <i className="bi bi-folder2"></i>
                                            </button>
                                        )}
                                        <button onClick={() => handleDelete(user.id)} className="btn btn-sm btn-outline-danger" title="Eliminar">
                                            <i className="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="5" className="text-center py-5 text-muted">
                                        No se encontraron usuarios
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {users.links && users.links.length > 3 && (
                    <div className="card-footer bg-body border-top-0 py-3">
                        <div className="d-flex flex-wrap align-items-center justify-content-between gap-2">
                            <small className="text-secondary">
                                Mostrando {users.from ?? 0} a {users.to ?? 0} de {users.total} usuarios
                            </small>
                            <nav aria-label="Paginación">
                                <ul className="pagination pagination-sm mb-0">
                                    {users.links.map((link, key) => (
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
                    </div>
                )}
            </div>

            <UserFolderPermissionsModal
                show={!!folderModalUser}
                onClose={() => setFolderModalUser(null)}
                userId={folderModalUser?.id}
                userName={folderModalUser?.name}
            />
            </div>
        </MainLayout>
    );
}
