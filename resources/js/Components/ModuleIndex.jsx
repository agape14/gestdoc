import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';
import PdfModal from '@/Components/PdfModal';
import ModuleFolderModal from '@/Components/ModuleFolderModal';
import FolderCardModule from '@/Components/FolderCardModule';
import ModuleFolderEditModal from '@/Components/ModuleFolderEditModal';

const getIconClass = (iconName) => {
    const iconMap = { Lock: 'bi-lock-fill', Globe: 'bi-globe', Folder: 'bi-folder-fill', Building: 'bi-building' };
    return iconMap[iconName] || 'bi-folder-fill';
};

export default function ModuleIndex({ title, description, items, columns, createRoute, onCreate, filters, routeParams = {}, renderDetail, editRoute, deleteRoute, userRole, folders = [], currentFolder = null, breadcrumb = [], storeFolderRoute, indexRoute, indexTitle, operadores = [], getDocumentLinks = null, anulados = [] }) {
    const { auth, flash } = usePage().props;
    const currentUserRole = userRole || auth?.user?.role || 'Visualizador';
    const isAdmin = currentUserRole === 'Administrador';
    const [search, setSearch] = useState(routeParams.search || '');
    const [operatorId, setOperatorId] = useState(routeParams.user_id || '');
    const [currentFilters, setCurrentFilters] = useState(routeParams);
    const [expandedRow, setExpandedRow] = useState(null);
    const [tabActivo, setTabActivo] = useState('activos');
    const [showFolderModal, setShowFolderModal] = useState(false);
    const [editingFolder, setEditingFolder] = useState(null);
    const [showDocumentsModal, setShowDocumentsModal] = useState(false);
    const [listDocumentLinks, setListDocumentLinks] = useState([]);
    const [showPdfModal, setShowPdfModal] = useState(false);
    const [pdfModalUrl, setPdfModalUrl] = useState('');
    const [pdfModalTitle, setPdfModalTitle] = useState('');

    const breadcrumbTitle = (breadcrumb && breadcrumb.length > 0) ? breadcrumb.map(f => f.name).join(' / ') : (indexTitle || title);
    const buildIndexParams = (extra = {}) => ({ ...currentFilters, ...extra });
    const hasFolders = Boolean(indexRoute && (storeFolderRoute || (folders && folders.length > 0)));
    const canCreateFolder = Boolean(storeFolderRoute);

    const handleRowClick = (id) => {
        setExpandedRow(expandedRow === id ? null : id);
    };

    useEffect(() => {
        const timer = setTimeout(() => {
            const params = { ...currentFilters, search, folder_id: routeParams.folder_id };
            if (isAdmin) params.user_id = operatorId || undefined;
            if (search !== (routeParams.search || '') || operatorId !== (routeParams.user_id || '')) {
                router.get(window.location.pathname, params, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search, operatorId]);

    const handleCloseFolderModal = () => setShowFolderModal(false);

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
            return item.user_id == null || item.user_id === auth?.user?.id;
        }
        return false;
    };

    const canDelete = (item) => {
        if (currentUserRole === 'Administrador') return true;
        if (currentUserRole === 'Operador') {
            return item.user_id == null || item.user_id === auth?.user?.id;
        }
        return false;
    };

    /** Administrador: todas las carpetas. Operador: solo las propias. Visualizador: ninguna. */
    const canEditOrDeleteFolder = (folder) => {
        if (currentUserRole === 'Administrador') return true;
        if (currentUserRole === 'Operador') return folder.user_id === auth?.user?.id;
        return false;
    };

    const handleDeleteFolder = (folder) => {
        if (folder?.is_system) {
            Swal.fire({ icon: 'error', title: 'No permitido', text: 'No se pueden eliminar carpetas del sistema' });
            return;
        }
        Swal.fire({
            title: '¿Eliminar carpeta?',
            text: 'Se eliminará la carpeta y su contenido. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('folders.destroy', folder.id), { preserveScroll: true });
            }
        });
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

    const openDocumentsModal = (item) => {
        if (!getDocumentLinks) return;
        const links = (getDocumentLinks(item) || []).filter(d => d.path);
        setListDocumentLinks(links);
        setShowDocumentsModal(true);
    };

    const openPdfInModal = (label, path) => {
        setPdfModalTitle(`${breadcrumbTitle} - ${label}`);
        setPdfModalUrl(`/storage/${path}`);
        setShowPdfModal(true);
    };

    return (
        <MainLayout>
            <Head title={title} />

            {flash?.success && (
                <div className="alert alert-success alert-dismissible fade show rounded-3 mb-3" role="alert">
                    {flash.success}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            )}
            {flash?.error && (
                <div className="alert alert-danger alert-dismissible fade show rounded-3 mb-3" role="alert">
                    {flash.error}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                </div>
            )}

            {hasFolders && breadcrumb && breadcrumb.length > 0 && (
                <nav aria-label="breadcrumb" className="mb-3">
                    <ol className="breadcrumb bg-body-tertiary rounded-3 p-3">
                        <li className="breadcrumb-item">
                            <Link href={route(indexRoute)} className="text-decoration-none"><i className="bi bi-house-door-fill me-1"></i> {indexTitle || title}</Link>
                        </li>
                        {breadcrumb.map((folder, index) => (
                            <li key={folder.id} className={`breadcrumb-item ${index === breadcrumb.length - 1 ? 'active' : ''}`}>
                                {index === breadcrumb.length - 1 ? folder.name : <Link href={route(indexRoute, { folder_id: folder.id })} className="text-decoration-none">{folder.name}</Link>}
                            </li>
                        ))}
                    </ol>
                </nav>
            )}

            {hasFolders && (
                <div className="mb-4">
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="fw-bold text-body mb-0"><i className="bi bi-folder me-2"></i>Carpetas</h5>
                        {canCreateFolder && currentUserRole !== 'Visualizador' && (
                            <button type="button" className="btn btn-primary rounded-pill px-3" onClick={() => setShowFolderModal(true)}>
                                <i className="bi bi-folder-plus me-2"></i> Nueva carpeta
                            </button>
                        )}
                    </div>
                    {folders && folders.length > 0 && (
                        <div className="row g-3">
                            {folders.map((folder) => (
                                <FolderCardModule
                                    key={folder.id}
                                    folder={folder}
                                    indexRoute={indexRoute}
                                    indexParams={buildIndexParams()}
                                    canEditFolder={canEditOrDeleteFolder(folder)}
                                    onEdit={(f) => setEditingFolder(f)}
                                    onDelete={handleDeleteFolder}
                                />
                            ))}
                        </div>
                    )}
                </div>
            )}

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
                <div className="row g-3 align-items-end">
                    {isAdmin && operadores.length > 0 && (
                        <div className="col-md-6 col-lg-3">
                            <label className="form-label small text-secondary mb-1">Operador</label>
                            <select className="form-select rounded-pill bg-body-tertiary border-0" value={operatorId} onChange={(e) => setOperatorId(e.target.value)}>
                                <option value="">Todos los operadores</option>
                                {operadores.map(op => (<option key={op.id} value={op.id}>{op.name}</option>))}
                            </select>
                        </div>
                    )}
                    <div className="col-md-6 col-lg-4">
                        <label className="form-label small text-secondary mb-1 d-none d-lg-block">Buscar</label>
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
                </div>
            </div>

            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
                {isAdmin && Array.isArray(anulados) && (
                    <ul className="nav nav-tabs card-header-tabs border-0 px-4 pt-3">
                        <li className="nav-item">
                            <button type="button" className={`nav-link rounded-top-pill ${tabActivo === 'activos' ? 'active bg-white border-bottom-0 fw-semibold' : 'border-0 bg-transparent text-secondary'}`} onClick={() => setTabActivo('activos')}>
                                Activos {items.data?.length != null && <span className="badge bg-primary-subtle text-primary ms-1">{items.data.length}</span>}
                            </button>
                        </li>
                        <li className="nav-item">
                            <button type="button" className={`nav-link rounded-top-pill ${tabActivo === 'anulados' ? 'active bg-white border-bottom-0 fw-semibold' : 'border-0 bg-transparent text-secondary'}`} onClick={() => setTabActivo('anulados')}>
                                Anulados {anulados.length > 0 && <span className="badge bg-secondary-subtle text-secondary ms-1">{anulados.length}</span>}
                            </button>
                        </li>
                    </ul>
                )}
                <div className="table-responsive">
                    {tabActivo === 'anulados' && isAdmin ? (
                        <table className="table table-hover align-middle mb-0">
                            <thead className="border-bottom text-secondary small text-uppercase">
                                <tr>
                                    {columns.map((col, i) => (
                                        <th key={i} scope="col" className={`py-3 ${i === 0 ? 'ps-4' : ''} ${i === columns.length - 1 ? 'text-end pe-4' : ''}`}>
                                            {col.header === 'ACCIONES' ? 'ESTADO' : col.header}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody>
                                {anulados.length > 0 ? anulados.map((item, idx) => (
                                    <tr key={item.id || idx} className="table-secondary">
                                        {columns.map((col, i) => (
                                            <td key={i} className={`py-3 ${i === 0 ? 'ps-4' : ''} ${i === columns.length - 1 ? 'text-end pe-4' : ''}`}>
                                                {col.header === 'ACCIONES' ? <span className="badge bg-secondary">Anulado</span> : (col.render ? col.render(item) : (item[col.accessor] ?? '-'))}
                                            </td>
                                        ))}
                                    </tr>
                                )) : (
                                    <tr><td colSpan={columns.length} className="text-center py-4 text-muted">No hay registros anulados.</td></tr>
                                )}
                            </tbody>
                        </table>
                    ) : (
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
                                            if (col.header === 'ACCIONES' && !col.render && (editRoute || deleteRoute || getDocumentLinks)) {
                                                const docLinks = getDocumentLinks ? (getDocumentLinks(item) || []).filter(d => d.path) : [];
                                                const hasDocs = docLinks.length > 0;
                                                return (
                                                    <td key={i} className="text-end pe-4">
                                                        <div className="d-flex gap-1 justify-content-end flex-wrap">
                                                            {hasDocs && (
                                                                <button
                                                                    type="button"
                                                                    onClick={(e) => { e.stopPropagation(); openDocumentsModal(item); }}
                                                                    className="btn btn-sm btn-outline-primary"
                                                                    title="Ver documentos"
                                                                >
                                                                    <i className="bi bi-file-earmark-pdf"></i>
                                                                </button>
                                                            )}
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
                    )}
                </div>
                {tabActivo === 'activos' && items.links && items.links.length > 3 && (
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

            {hasFolders && canCreateFolder && showFolderModal && (
                <ModuleFolderModal
                    show={showFolderModal}
                    onClose={handleCloseFolderModal}
                    storeFolderRoute={storeFolderRoute}
                    parentId={currentFolder?.id ?? null}
                />
            )}

            {editingFolder && (
                <ModuleFolderEditModal show={!!editingFolder} onClose={() => setEditingFolder(null)} folder={editingFolder} />
            )}

            {showDocumentsModal && (
                <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1050 }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border-0 shadow-lg rounded-4">
                            <div className="modal-header border-bottom">
                                <h5 className="modal-title fw-bold">{breadcrumbTitle} — Documentos adjuntos</h5>
                                <button type="button" className="btn-close" onClick={() => { setShowDocumentsModal(false); setListDocumentLinks([]); }}></button>
                            </div>
                            <div className="modal-body">
                                <ul className="list-group list-group-flush">
                                    {listDocumentLinks.map((doc, idx) => (
                                        <li key={idx} className="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                            <span>{doc.label}</span>
                                            <button type="button" className="btn btn-sm btn-outline-primary" onClick={() => { setShowDocumentsModal(false); openPdfInModal(doc.label, doc.path); }}>
                                                <i className="bi bi-file-earmark-pdf me-1"></i> Ver PDF
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                            </div>
                            <div className="modal-footer border-0">
                                <button type="button" className="btn btn-secondary" onClick={() => { setShowDocumentsModal(false); setListDocumentLinks([]); }}>Cerrar</button>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            <PdfModal show={showPdfModal} onClose={() => { setShowPdfModal(false); setPdfModalUrl(''); setPdfModalTitle(''); }} pdfUrl={pdfModalUrl} title={pdfModalTitle} />
        </MainLayout >
    );
}
