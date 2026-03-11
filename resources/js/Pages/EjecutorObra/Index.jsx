import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';
import PdfModal from '@/Components/PdfModal';
import ModuleFolderModal from '@/Components/ModuleFolderModal';
import FolderCardModule from '@/Components/FolderCardModule';
import ModuleFolderEditModal from '@/Components/ModuleFolderEditModal';

const fmtDate = (d) => (!d ? '-' : (typeof d === 'string' && d.length >= 10 ? d.substring(0, 10) : d));

const DetailForm = ({ item, onClose }) => {
    const { auth } = usePage().props;
    const currentUserRole = auth?.user?.role || 'Visualizador';
    const canEdit = currentUserRole === 'Administrador' || (currentUserRole === 'Operador' && item.user_id === auth?.user?.id);

    return (
        <div className="p-4 bg-white rounded-4 shadow-sm">
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h5 className="fw-bold mb-0">Detalle de Ejecutor de Obra</h5>
                <div className="d-flex gap-2">
                    <button type="button" className="btn-close" onClick={onClose} aria-label="Cerrar"></button>
                    {canEdit && (
                        <Link href={route('ejecutor-obra.edit', item.id)} className="btn btn-primary btn-sm rounded-pill">
                            <i className="bi bi-pencil me-1"></i> Editar
                        </Link>
                    )}
                </div>
            </div>
            <div className="row g-3 mb-3 small">
                <div className="col-md-6"><span className="text-secondary">Entidad:</span> {item.nombre_sigla_entidad || '-'}</div>
                <div className="col-md-6"><span className="text-secondary">Nomenclatura:</span> {item.nomenclatura || '-'}</div>
                <div className="col-12"><span className="text-secondary">Descripción:</span> {(item.descripcion_objeto || '-').substring(0, 200)}{(item.descripcion_objeto || '').length > 200 ? '…' : ''}</div>
                <div className="col-md-6"><span className="text-secondary">CUI:</span> {item.cui || '-'}</div>
                <div className="col-md-6"><span className="text-secondary"># Contrato:</span> {item.numero_contrato || '-'}</div>
                <div className="col-md-6"><span className="text-secondary">F. Firma contrato:</span> {fmtDate(item.fecha_firma_contrato)}</div>
                <div className="col-md-6"><span className="text-secondary">Monto total:</span> S/ {parseFloat(item.monto_total || 0).toLocaleString('es-PE', { minimumFractionDigits: 2 })}</div>
                <div className="col-md-6"><span className="text-secondary">Plazo (días):</span> {item.plazo ?? '-'}</div>
                <div className="col-md-6"><span className="text-secondary">Liquidado/recepcionado:</span> {item.liquidado_recepcionado ? 'Sí' : 'No'}</div>
            </div>
            <div className="d-flex justify-content-end">
                <button type="button" className="btn btn-outline-secondary btn-sm" onClick={onClose}>Cerrar</button>
            </div>
        </div>
    );
};

const getDocumentLinks = (item) => {
    const links = [];
    const files = [
        ['archivo_contrato', 'Contrato'],
        ['archivo_acta_recepcion', 'Acta de Recepción'],
        ['archivo_acta_inicio', 'Acta de Inicio'],
        ['archivo_acta_suspension', 'Acta de Suspensión'],
        ['archivo_acta_reinicio', 'Acta de Reinicio'],
        ['archivo_acta_entrega_terreno', 'Acta de Entrega de Terreno'],
        ['archivo_resolucion_liquidacion', 'Resolución de Liquidación'],
    ];
    files.forEach(([key, label]) => {
        const path = item[key];
        const url = item[key + '_url'];
        if (path || url) links.push({ label, path: path || '', url: url || null });
    });
    return links;
};

export default function Index({ obras, filters, flash, userRole, operadores = [], folders = [], currentFolder = null, breadcrumb = [] }) {
    const { auth } = usePage().props;
    const currentUserRole = userRole || auth?.user?.role || 'Visualizador';
    const isAdmin = currentUserRole === 'Administrador';
    const [search, setSearch] = useState(filters.search || '');
    const [operatorId, setOperatorId] = useState(filters.user_id || '');
    const [expandedRow, setExpandedRow] = useState(null);
    const [showFolderModal, setShowFolderModal] = useState(false);
    const [editingFolder, setEditingFolder] = useState(null);
    const [showDocumentsModal, setShowDocumentsModal] = useState(false);
    const [listDocumentLinks, setListDocumentLinks] = useState([]);
    const [showPdfModal, setShowPdfModal] = useState(false);
    const [pdfModalUrl, setPdfModalUrl] = useState('');
    const [pdfModalTitle, setPdfModalTitle] = useState('');

    const breadcrumbTitle = (breadcrumb && breadcrumb.length > 0) ? breadcrumb.map(f => f.name).join(' / ') : (currentFolder?.name || 'Ejecutor de Obra');

    const buildIndexParams = (extra = {}) => ({ ...filters, ...extra, folder_id: filters.folder_id });

    useEffect(() => {
        const timer = setTimeout(() => {
            const params = { ...filters, search, folder_id: filters.folder_id };
            if (isAdmin) params.user_id = operatorId || undefined;
            if (search !== (filters.search || '') || operatorId !== (filters.user_id || '')) {
                router.get(route('ejecutor-obra.index'), params, { preserveState: true, preserveScroll: true, replace: true });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search, operatorId]);

    const handleCloseFolderModal = () => setShowFolderModal(false);

    const handleDelete = (id) => {
        Swal.fire({
            title: '¿Anular registro?',
            text: 'El registro no se borrará pero dejará de mostrarse en el listado activo.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, anular',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('ejecutor-obra.destroy', id));
            }
        });
    };

    const handleExport = () => {
        window.location.href = route('ejecutor-obra.export');
    };

    const handleExportProject = (id) => {
        window.location.href = route('ejecutor-obra.export-project', id);
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

    const openDocumentsModal = (item, e) => {
        if (e) e.stopPropagation();
        const links = getDocumentLinks(item).filter(d => d.path);
        setListDocumentLinks(links);
        setShowDocumentsModal(true);
    };

    const openPdfInModal = (label, path, url) => {
        setPdfModalTitle(`${breadcrumbTitle} - ${label}`);
        setPdfModalUrl(url || (path ? `/storage/${path}` : ''));
        setShowPdfModal(true);
    };

    const allObras = obras.data || [];

    return (
        <MainLayout>
            <Head title="Ejecutor de Obra" />
            <div className="grid-page-wrapper min-w-0 w-100" style={{ maxWidth: '100%' }}>
            {flash?.success && (
                <div className="alert alert-success alert-dismissible fade show" role="alert">
                    {flash.success}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            )}

            {breadcrumb && breadcrumb.length > 0 && (
                <nav aria-label="breadcrumb" className="mb-3">
                    <ol className="breadcrumb bg-body-tertiary rounded-3 p-3">
                        <li className="breadcrumb-item">
                            <Link href={route('ejecutor-obra.index')} className="text-decoration-none"><i className="bi bi-house-door-fill me-1"></i> Ejecutor de Obra</Link>
                        </li>
                        {breadcrumb.map((folder, index) => (
                            <li key={folder.id} className={`breadcrumb-item ${index === breadcrumb.length - 1 ? 'active' : ''}`}>
                                {index === breadcrumb.length - 1 ? folder.name : <Link href={route('ejecutor-obra.index', { folder_id: folder.id })} className="text-decoration-none">{folder.name}</Link>}
                            </li>
                        ))}
                    </ol>
                </nav>
            )}

            {/* Carpetas: siempre visible (raíz o dentro de carpeta) */}
            <div className="mb-4">
                <div className="d-flex justify-content-between align-items-center mb-3">
                    <h5 className="fw-bold text-body mb-0"><i className="bi bi-folder me-2"></i>Carpetas</h5>
                    {currentUserRole !== 'Visualizador' && (
                        <button type="button" className="btn btn-primary rounded-pill px-3" onClick={() => setShowFolderModal(true)}>
                            <i className="bi bi-folder-plus me-2"></i> Nueva Carpeta
                        </button>
                    )}
                </div>
                {folders && folders.length > 0 && (
                    <div className="row g-3">
                        {folders.map((folder) => (
                            <FolderCardModule
                                key={folder.id}
                                folder={folder}
                                indexRoute="ejecutor-obra.index"
                                indexParams={buildIndexParams()}
                                canEditFolder={canEditOrDeleteFolder(folder)}
                                onEdit={(f) => setEditingFolder(f)}
                                onDelete={handleDeleteFolder}
                            />
                        ))}
                    </div>
                )}
            </div>

            <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 className="fw-bold text-body mb-0">Ejecutor de Obra</h2>
                    <p className="text-secondary mb-0">Gestión de ejecución de obras</p>
                </div>
                <div className="d-flex gap-2 flex-wrap">
                    {currentUserRole !== 'Visualizador' && (
                        <>
                            <button onClick={handleExport} className="btn btn-success rounded-pill px-4">
                                <i className="bi bi-file-earmark-excel me-2"></i> Exportar Excel
                            </button>
                            <Link href={route('ejecutor-obra.create', currentFolder?.id ? { folder_id: currentFolder.id } : {})} className="btn btn-success shadow-sm rounded-pill px-4">
                                <i className="bi bi-plus-lg me-2"></i> Nuevo Registro
                            </Link>
                        </>
                    )}
                </div>
            </div>

            <div className="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-body min-w-0 w-100">
                <div className="row g-3 align-items-end">
                    {isAdmin && operadores.length > 0 && (
                        <div className="col-12 col-md-6 col-lg-3">
                            <label className="form-label small text-secondary mb-1">Operador</label>
                            <select className="form-select rounded-pill bg-body-tertiary border-0" value={operatorId} onChange={(e) => setOperatorId(e.target.value)}>
                                <option value="">Todos los operadores</option>
                                {operadores.map(op => (<option key={op.id} value={op.id}>{op.name}</option>))}
                            </select>
                        </div>
                    )}
                    <div className="col-12 col-md-6 col-lg-4">
                        <div className="input-group min-w-0">
                            <span className="input-group-text bg-body-tertiary border-end-0 rounded-start-pill ps-3"><i className="bi bi-search text-secondary"></i></span>
                            <input
                                type="text"
                                className="form-control border-start-0 bg-body-tertiary rounded-end-pill"
                                placeholder="Buscar por entidad, nomenclatura, CUI o número de contrato..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body min-w-0 w-100">
                <div className="card-header bg-body border-0 py-3">
                    <h6 className="mb-0 fw-bold text-truncate min-w-0">Listado</h6>
                </div>
                <div className="table-responsive overflow-x-auto min-w-0" style={{ WebkitOverflowScrolling: 'touch' }}>
                    <table className="table table-hover align-middle mb-0" style={{ minWidth: '720px' }}>
                        <thead className="border-bottom text-secondary small text-uppercase">
                            <tr>
                                <th scope="col" className="ps-4 py-3">ENTIDAD</th>
                                <th scope="col" className="py-3">NOMENCLATURA</th>
                                <th scope="col" className="py-3">CUI</th>
                                <th scope="col" className="py-3"># CONTRATO</th>
                                <th scope="col" className="py-3">MONTO TOTAL</th>
                                <th scope="col" className="py-3">PLAZO</th>
                                <th scope="col" className="text-end pe-4 py-3">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            {allObras.length > 0 ? (
                                allObras.map(obra => (
                                    <React.Fragment key={obra.id}>
                                        <tr
                                            className="cursor-pointer"
                                            onClick={() => setExpandedRow(expandedRow === obra.id ? null : obra.id)}
                                            style={{ backgroundColor: expandedRow === obra.id ? 'var(--bs-light)' : 'transparent' }}
                                        >
                                            <td className="ps-4 py-3 fw-medium text-body">{(obra.nombre_sigla_entidad || obra.titulo || '-').substring(0, 40)}{(obra.nombre_sigla_entidad || obra.titulo || '').length > 40 ? '…' : ''}</td>
                                            <td className="text-secondary">{(obra.nomenclatura || '-').substring(0, 25)}{(obra.nomenclatura || '').length > 25 ? '…' : ''}</td>
                                            <td className="text-secondary">{obra.cui || '-'}</td>
                                            <td className="text-secondary">{obra.numero_contrato || '-'}</td>
                                            <td className="text-secondary fw-bold text-body">
                                                S/ {parseFloat(obra.monto_total || obra.presupuesto || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td className="text-secondary">{obra.plazo ?? obra.plazo_ejecucion ?? '-'}</td>
                                            <td className="text-end pe-4">
                                                {getDocumentLinks(obra).filter(d => d.path || d.url).length > 0 && (
                                                    <button
                                                        type="button"
                                                        onClick={(e) => openDocumentsModal(obra, e)}
                                                        className="btn btn-sm btn-outline-primary me-1"
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
                                                        <button
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                setExpandedRow(expandedRow === obra.id ? null : obra.id);
                                                            }}
                                                            className="btn btn-sm btn-outline-primary me-1"
                                                        >
                                                            <i className={`bi bi-chevron-${expandedRow === obra.id ? 'up' : 'down'}`}></i>
                                                        </button>
                                                        <Link
                                                            href={route('ejecutor-obra.edit', obra.id)}
                                                            className="btn btn-sm btn-outline-primary me-1"
                                                            title="Editar"
                                                            onClick={(e) => e.stopPropagation()}
                                                        >
                                                            <i className="bi bi-pencil"></i>
                                                        </Link>
                                                        <button
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                handleExportProject(obra.id);
                                                            }}
                                                            className="btn btn-sm btn-outline-success me-1"
                                                            title="Exportar a Excel"
                                                        >
                                                            <i className="bi bi-file-earmark-excel"></i>
                                                        </button>
                                                        {canDelete(obra) && (
                                                            <button
                                                                onClick={(e) => {
                                                                    e.stopPropagation();
                                                                    handleDelete(obra.id);
                                                                }}
                                                                className="btn btn-sm btn-outline-danger"
                                                            >
                                                                <i className="bi bi-trash"></i>
                                                            </button>
                                                        )}
                                                    </>
                                                )}
                                            </td>
                                        </tr>
                                        {expandedRow === obra.id && (
                                            <tr>
                                                <td colSpan="7" className="p-0 border-0">
                                                    <div className="p-3 bg-light">
                                                        <DetailForm item={obra} onClose={() => setExpandedRow(null)} />
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                    </React.Fragment>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="7" className="text-center py-5 text-muted">No se encontraron registros.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {obras.links && obras.links.length > 3 && (
                    <div className="card-footer bg-body border-top-0 py-3">
                        <nav aria-label="Page navigation">
                            <ul className="pagination justify-content-center mb-0">
                                {obras.links.map((link, key) => (
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

            {showFolderModal && (
                <ModuleFolderModal
                    show={showFolderModal}
                    onClose={handleCloseFolderModal}
                    storeFolderRoute="ejecutor-obra.folders.store"
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
                                <h5 className="modal-title fw-bold text-truncate pe-3">{breadcrumbTitle} — Documentos adjuntos</h5>
                                <button type="button" className="btn-close" onClick={() => { setShowDocumentsModal(false); setListDocumentLinks([]); }}></button>
                            </div>
                            <div className="modal-body">
                                <ul className="list-group list-group-flush">
                                    {listDocumentLinks.map((doc, idx) => (
                                        <li key={idx} className="list-group-item d-flex justify-content-between align-items-center border-0 px-0">
                                            <span>{doc.label}</span>
                                            <button type="button" className="btn btn-sm btn-outline-primary" onClick={() => { setShowDocumentsModal(false); openPdfInModal(doc.label, doc.path, doc.url); }}>
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
            </div>
        </MainLayout>
    );
}
