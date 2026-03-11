import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';
import FolderModal from '@/Components/FolderModal';
import DocumentModal from '@/Components/DocumentModal';
import DocumentPdfModal from '@/Components/DocumentPdfModal';

export default function Index({
    folders = [],
    subfolders = [],
    documents = [],
    currentFolder = null,
    breadcrumb = [],
    flash,
    filters = {},
    operadores = [],
}) {
    const { auth } = usePage().props;
    const isAdmin = auth?.user?.role === 'Administrador';
    const isViewer = auth?.user?.role === 'Visualizador';
    const [showFolderModal, setShowFolderModal] = useState(false);
    const [showDocumentModal, setShowDocumentModal] = useState(false);
    const [showPdfModal, setShowPdfModal] = useState(false);
    const [editingFolder, setEditingFolder] = useState(null);
    const [editingDocument, setEditingDocument] = useState(null);
    const [viewingFile, setViewingFile] = useState(null);
    const [viewingDocument, setViewingDocument] = useState(null);
    const [movingDocument, setMovingDocument] = useState(null);
    const [moveTargetFolderId, setMoveTargetFolderId] = useState('');

    const [search, setSearch] = useState(filters.search || '');
    const [dateStart, setDateStart] = useState(filters.date_start || '');
    const [dateEnd, setDateEnd] = useState(filters.date_end || '');
    const [operatorId, setOperatorId] = useState(filters.user_id || '');
    const [selectedDocIds, setSelectedDocIds] = useState([]);

    useEffect(() => {
        if (!currentFolder) return;
        const timer = setTimeout(() => {
            const params = { search, date_start: dateStart, date_end: dateEnd };
            if (isAdmin) params.user_id = operatorId || undefined;
            const hasChanges =
                search !== (filters.search || '') ||
                dateStart !== (filters.date_start || '') ||
                dateEnd !== (filters.date_end || '') ||
                operatorId !== (filters.user_id || '');
            if (hasChanges) {
                router.get(route('folders.show', currentFolder.id), params, { preserveState: true, preserveScroll: true, replace: true });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search, dateStart, dateEnd, operatorId]);

    useEffect(() => {
        if (currentFolder) return;
        const timer = setTimeout(() => {
            const currentFilterUserId = filters.user_id != null ? String(filters.user_id) : '';
            if (String(operatorId) === currentFilterUserId) return;
            const params = {};
            if (operatorId) params.user_id = operatorId;
            router.get(route('folders.index'), params, { preserveState: true, preserveScroll: true, replace: true });
        }, 300);
        return () => clearTimeout(timer);
    }, [operatorId]);

    const handleCreateFolder = () => {
        setEditingFolder(null);
        setShowFolderModal(true);
    };

    const handleEditFolder = (folder) => {
        setEditingFolder(folder);
        setShowFolderModal(true);
    };

    const handleDeleteFolder = (folder) => {
        if (folder.is_system) {
            Swal.fire({ icon: 'error', title: 'No permitido', text: 'No se pueden eliminar carpetas del sistema' });
            return;
        }
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Se eliminará la carpeta (tipo de documento) y todos sus documentos.',
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

    const handleCreateDocument = () => {
        setEditingDocument(null);
        setShowDocumentModal(true);
    };

    const handleEditDocument = (doc) => {
        setEditingDocument(doc);
        setShowDocumentModal(true);
    };

    const handleDeleteDocument = (doc) => {
        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Se eliminará el documento y todos sus archivos.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('folders.documents.destroy', doc.id), { preserveScroll: true });
            }
        });
    };

    const handleViewPdf = (doc, file) => {
        setViewingDocument(doc);
        setViewingFile(file);
        setShowPdfModal(true);
    };

    const handleMoveDocument = () => {
        if (!movingDocument || !moveTargetFolderId) return;
        router.put(route('folders.documents.move', movingDocument.id), { folder_id: moveTargetFolderId }, {
            preserveScroll: true,
            onSuccess: () => { setMovingDocument(null); setMoveTargetFolderId(''); },
        });
    };

    const toggleDocSelection = (docId) => {
        setSelectedDocIds((prev) =>
            prev.includes(docId) ? prev.filter((id) => id !== docId) : [...prev, docId]
        );
    };

    const toggleSelectAllDocs = () => {
        if (!documents || documents.length === 0) return;
        if (selectedDocIds.length === documents.length) {
            setSelectedDocIds([]);
        } else {
            setSelectedDocIds(documents.map((d) => d.id));
        }
    };

    const getDownloadZipUrl = (all = false) => {
        if (!currentFolder) return '#';
        const base = route('folders.documents.download-zip', currentFolder.id);
        const params = new URLSearchParams();
        if (all) {
            params.set('all', '1');
        } else {
            selectedDocIds.forEach((id) => params.append('ids[]', id));
        }
        return `${base}?${params.toString()}`;
    };

    const getExportExcelUrl = () => {
        if (!currentFolder) return '#';
        const base = route('folders.documents.export-excel', currentFolder.id);
        const params = new URLSearchParams();
        if (search) params.set('search', search);
        if (dateStart) params.set('date_start', dateStart);
        if (dateEnd) params.set('date_end', dateEnd);
        if (isAdmin && operatorId) params.set('user_id', operatorId);
        const qs = params.toString();
        return qs ? `${base}?${qs}` : base;
    };

    const handleDownloadZip = (all) => {
        if (!currentFolder) return;
        if (!all && selectedDocIds.length === 0) return;
        window.location.href = getDownloadZipUrl(all);
    };

    const clearFilters = () => {
        setSearch('');
        setDateStart('');
        setDateEnd('');
    };

    const canEditOrDeleteFolder = (folder) => {
        if (isViewer) return false;
        if (isAdmin) return true;
        if (auth?.user?.role === 'Operador' && folder.user_id === auth?.user?.id) return true;
        return false;
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        return new Intl.DateTimeFormat('es-PE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }).format(new Date(dateString));
    };

    const getIconClass = (iconName) => {
        const iconMap = {
            Lock: 'bi-lock-fill',
            Globe: 'bi-globe',
            Package: 'bi-box-seam',
            Settings: 'bi-gear-fill',
            MoreHorizontal: 'bi-three-dots',
            Briefcase: 'bi-briefcase-fill',
            HardHat: 'bi-hammer',
            Droplets: 'bi-droplet-fill',
            Waves: 'bi-water',
            School: 'bi-building',
            Road: 'bi-signpost-fill',
            Bridge: 'bi-bricks',
            Trophy: 'bi-trophy-fill',
            FileText: 'bi-file-text-fill',
            Diagram: 'bi-diagram-3-fill',
            Tools: 'bi-tools',
            Lightning: 'bi-lightning-charge-fill',
            Tree: 'bi-tree-fill',
            Shield: 'bi-shield-fill-check',
            Star: 'bi-star-fill',
            Calendar: 'bi-calendar-check-fill',
            Archive: 'bi-archive-fill',
            ClipboardCheck: 'bi-clipboard-check-fill',
        };
        return iconMap[iconName] || 'bi-folder-fill';
    };

    return (
        <MainLayout>
            <Head title="Gestión Documental" />
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

            {isAdmin && operadores.length > 0 && (
                <div className="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-body min-w-0 w-100">
                    <label className="form-label fw-semibold text-body mb-2">
                        <i className="bi bi-person-fill me-2 text-primary"></i>
                        Filtrar por operador
                    </label>
                    <select
                        className="form-select rounded-pill bg-body-tertiary border-0 px-3 text-secondary"
                        style={{ maxWidth: '320px' }}
                        value={operatorId}
                        onChange={(e) => setOperatorId(e.target.value)}
                    >
                        <option value="">Todos los operadores</option>
                        {operadores.map((op) => (
                            <option key={op.id} value={op.id}>{op.name}</option>
                        ))}
                    </select>
                </div>
            )}

            <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 className="fw-bold text-body mb-0">Gestión Documental</h2>
                    <p className="text-secondary mb-0">Carpetas por tipo de documento (Cartas, Oficios, Memos) y registro con múltiples PDF</p>
                </div>
                {!isViewer && (
                <div className="d-flex gap-2">
                    {currentFolder && (
                        <button onClick={handleCreateDocument} className="btn btn-success shadow-sm rounded-pill px-4">
                            <i className="bi bi-file-earmark-plus me-2"></i>
                            Nuevo Documento
                        </button>
                    )}
                    <button onClick={handleCreateFolder} className="btn btn-primary shadow-sm rounded-pill px-4">
                        <i className="bi bi-folder-plus me-2"></i>
                        Nueva Carpeta (tipo)
                    </button>
                </div>
                )}
            </div>

            {breadcrumb && breadcrumb.length > 0 && (
                <nav aria-label="breadcrumb" className="mb-4">
                    <ol className="breadcrumb bg-body-tertiary rounded-3 p-3">
                        <li className="breadcrumb-item">
                            <Link href={filters.user_id ? `${route('folders.index')}?user_id=${filters.user_id}` : route('folders.index')} className="text-decoration-none">
                                <i className="bi bi-house-door-fill me-1"></i>
                                Inicio
                            </Link>
                        </li>
                        {breadcrumb.map((folder, index) => (
                            <li
                                key={folder.id}
                                className={`breadcrumb-item ${index === breadcrumb.length - 1 ? 'active' : ''}`}
                            >
                                {index === breadcrumb.length - 1 ? (
                                    folder.name
                                ) : (
                                    <Link href={filters.user_id ? `${route('folders.show', folder.id)}?user_id=${filters.user_id}` : route('folders.show', folder.id)} className="text-decoration-none">
                                        {folder.name}
                                    </Link>
                                )}
                            </li>
                        ))}
                    </ol>
                </nav>
            )}

            {/* Carpetas creadas: justo debajo de la miga de pan */}
            {currentFolder && subfolders && subfolders.length > 0 && (
                <div className="mb-4">
                    <h5 className="fw-bold text-body mb-3">
                        <i className="bi bi-folder2 me-2"></i>
                        Carpetas dentro de {currentFolder.name}
                    </h5>
                    <div className="row g-3">
                        {subfolders.map((sub) => (
                            <div key={sub.id} className="col-md-6 col-lg-4 col-xl-3">
                                <div
                                    className="card border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative"
                                    style={{ cursor: 'pointer', transition: 'transform 0.2s' }}
                                    onMouseEnter={(e) => (e.currentTarget.style.transform = 'scale(1.02)')}
                                    onMouseLeave={(e) => (e.currentTarget.style.transform = 'scale(1)')}
                                >
                                    <Link
                                        href={filters.user_id ? `${route('folders.show', sub.id)}?user_id=${filters.user_id}` : route('folders.show', sub.id)}
                                        className="text-decoration-none text-body"
                                    >
                                        <div
                                            className="card-header border-0 p-4 position-relative"
                                            style={{ backgroundColor: sub.color || '#EAEAEA', minHeight: '100px' }}
                                        >
                                            <div className="d-flex justify-content-between align-items-start">
                                                <i className={`bi ${getIconClass(sub.icon)} fs-2 opacity-75`}></i>
                                                <span className="badge bg-primary text-white rounded-pill shadow-sm" style={{ fontSize: '0.75rem' }}>
                                                    {sub.documents_count ?? 0}
                                                </span>
                                            </div>
                                        </div>
                                    </Link>
                                    <div className="card-body p-3">
                                        <h6 className="card-title fw-bold mb-1">{sub.name}</h6>
                                        {canEditOrDeleteFolder(sub) && (
                                            <div className="d-flex gap-1 mt-2">
                                                <button
                                                    type="button"
                                                    onClick={(e) => { e.preventDefault(); handleEditFolder(sub); }}
                                                    className="btn btn-sm btn-outline-secondary"
                                                    title="Editar carpeta"
                                                >
                                                    <i className="bi bi-pencil"></i>
                                                </button>
                                                {!sub.is_system && (
                                                    <button
                                                        type="button"
                                                        onClick={(e) => { e.preventDefault(); handleDeleteFolder(sub); }}
                                                        className="btn btn-sm btn-outline-danger"
                                                        title="Eliminar"
                                                    >
                                                        <i className="bi bi-trash"></i>
                                                    </button>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {currentFolder && (
                <div className="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-body min-w-0 w-100">
                    <div className="row g-3">
                        <div className="col-12 col-lg-4">
                            <div className="input-group min-w-0">
                                <span className="input-group-text bg-body-tertiary border-end-0 rounded-start-pill ps-3">
                                    <i className="bi bi-search text-secondary"></i>
                                </span>
                                <input
                                    type="text"
                                    className="form-control border-start-0 bg-body-tertiary rounded-end-pill"
                                    placeholder="Buscar por número, asunto, remitente..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>
                        </div>
                        <div className="col-12 col-lg-3">
                            <input
                                type="date"
                                className="form-control rounded-pill bg-body-tertiary border-0 px-3"
                                placeholder="Fecha inicio"
                                value={dateStart}
                                onChange={(e) => setDateStart(e.target.value)}
                            />
                        </div>
                        <div className="col-12 col-lg-3">
                            <input
                                type="date"
                                className="form-control rounded-pill bg-body-tertiary border-0 px-3"
                                placeholder="Fecha fin"
                                value={dateEnd}
                                onChange={(e) => setDateEnd(e.target.value)}
                            />
                        </div>
                        <div className="col-12 col-lg-2">
                            <button
                                onClick={clearFilters}
                                className="btn btn-outline-secondary rounded-pill w-100"
                                title="Limpiar filtros"
                            >
                                <i className="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Dentro de una carpeta: documentos */}
            {currentFolder && (
                <>
                    {documents && documents.length > 0 ? (
                        <div className="mb-4">
                            <div className="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3">
                                <h5 className="fw-bold text-body mb-0">
                                    <i className="bi bi-file-earmark-text me-2"></i>
                                    Documentos en {currentFolder.name} ({documents.length})
                                </h5>
                                <div className="d-flex gap-2 align-items-center flex-wrap">
                                    <a
                                        href={getExportExcelUrl()}
                                        className="btn btn-success btn-sm"
                                        title="Exportar registros visibles a Excel"
                                    >
                                        <i className="bi bi-file-earmark-excel me-1"></i>
                                        Exportar Excel
                                    </a>
                                    <button
                                        type="button"
                                        className="btn btn-outline-primary btn-sm"
                                        onClick={() => handleDownloadZip(false)}
                                        disabled={selectedDocIds.length === 0}
                                        title={selectedDocIds.length === 0 ? 'Seleccione al menos un documento' : `Descargar ${selectedDocIds.length} documento(s) en ZIP`}
                                    >
                                        <i className="bi bi-file-zip me-1"></i>
                                        Descargar seleccionados ({selectedDocIds.length})
                                    </button>
                                    <button
                                        type="button"
                                        className="btn btn-primary btn-sm"
                                        onClick={() => handleDownloadZip(true)}
                                        title="Descargar todos los PDF en un ZIP (con el nombre indicado en Archivos PDF)"
                                    >
                                        <i className="bi bi-download me-1"></i>
                                        Descargar todos (ZIP)
                                    </button>
                                </div>
                            </div>
                            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body min-w-0 w-100">
                                <div className="table-responsive overflow-x-auto min-w-0" style={{ WebkitOverflowScrolling: 'touch' }}>
                                    <table className="table table-hover align-middle mb-0" style={{ minWidth: '640px' }}>
                                        <thead className="border-bottom text-secondary small text-uppercase">
                                            <tr>
                                                <th className="ps-4 py-3" style={{ width: '40px' }}>
                                                    <input
                                                        type="checkbox"
                                                        className="form-check-input"
                                                        checked={documents.length > 0 && selectedDocIds.length === documents.length}
                                                        onChange={toggleSelectAllDocs}
                                                        title="Seleccionar todos"
                                                    />
                                                </th>
                                                <th className="py-3">Número</th>
                                                <th className="py-3">Fecha</th>
                                                <th className="py-3">Asunto</th>
                                                <th className="py-3">Remitente</th>
                                                <th className="py-3">Destinatario</th>
                                                <th className="py-3">Archivos</th>
                                                <th className="text-end pe-4 py-3">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {documents.map((doc) => (
                                                <tr key={doc.id}>
                                                    <td className="ps-4 py-3">
                                                        <input
                                                            type="checkbox"
                                                            className="form-check-input"
                                                            checked={selectedDocIds.includes(doc.id)}
                                                            onChange={() => toggleDocSelection(doc.id)}
                                                            title="Seleccionar para descargar en ZIP"
                                                        />
                                                    </td>
                                                    <td className="py-3 fw-medium text-body">{doc.numero || '—'}</td>
                                                    <td className="text-secondary">{formatDate(doc.fecha_documento)}</td>
                                                    <td className="text-body">{doc.asunto || '—'}</td>
                                                    <td className="text-secondary">{doc.remitente || '—'}</td>
                                                    <td className="text-secondary">{doc.destinatario || '—'}</td>
                                                    <td>
                                                        {doc.files && doc.files.length > 0 ? (
                                                            <div className="d-flex flex-wrap gap-1">
                                                                {doc.files.map((f) => (
                                                                    <button
                                                                        key={f.id}
                                                                        type="button"
                                                                        className="btn btn-sm btn-outline-primary"
                                                                        onClick={() => handleViewPdf(doc, f)}
                                                                        title={f.nombre_archivo}
                                                                    >
                                                                        <i className="bi bi-file-pdf me-1"></i>
                                                                        {f.nombre_archivo}
                                                                    </button>
                                                                ))}
                                                            </div>
                                                        ) : (
                                                            <span className="text-secondary">—</span>
                                                        )}
                                                    </td>
                                                    <td className="text-end pe-4">
                                                        {!isViewer && (
                                                        <>
                                                            <button
                                                                onClick={() => handleEditDocument(doc)}
                                                                className="btn btn-sm btn-outline-secondary me-1"
                                                                title="Editar"
                                                            >
                                                                <i className="bi bi-pencil"></i>
                                                            </button>
                                                            <button
                                                                onClick={() => { setMovingDocument(doc); setMoveTargetFolderId(currentFolder?.id ? String(currentFolder.id) : ''); }}
                                                                className="btn btn-sm btn-outline-info me-1"
                                                                title="Mover a otra carpeta"
                                                            >
                                                                <i className="bi bi-folder-symlink"></i>
                                                            </button>
                                                            <button
                                                                onClick={() => handleDeleteDocument(doc)}
                                                                className="btn btn-sm btn-outline-danger"
                                                                title="Eliminar"
                                                            >
                                                                <i className="bi bi-trash"></i>
                                                            </button>
                                                        </>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    ) : (
                        <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body mb-4">
                            <div className="card-body text-center py-5">
                                <i className="bi bi-file-earmark-plus fs-1 text-secondary mb-3 d-block"></i>
                                <h5 className="text-body fw-bold mb-2">No hay documentos en {currentFolder.name}</h5>
                                <p className="text-secondary mb-4">
                                    {isViewer ? 'No hay documentos en esta carpeta.' : 'Registra el primer documento (con uno o más PDF) para esta carpeta.'}
                                </p>
                                {!isViewer && (
                                <button onClick={handleCreateDocument} className="btn btn-primary rounded-pill px-4">
                                    <i className="bi bi-file-earmark-plus me-2"></i>
                                    Nuevo Documento
                                </button>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Enlaces rápidos a otros tipos de documento */}
                    {folders && folders.length > 0 && (
                        <div className="mb-4">
                            <h6 className="fw-bold text-body mb-2 small text-uppercase text-secondary">
                                <i className="bi bi-folder2 me-1"></i>
                                Otros tipos de documento
                            </h6>
                            <div className="d-flex flex-wrap gap-2 align-items-center">
                                {folders.map((folder) => (
                                    <span key={folder.id} className="d-inline-flex align-items-center gap-1">
                                        <Link
                                            href={filters.user_id ? `${route('folders.show', folder.id)}?user_id=${filters.user_id}` : route('folders.show', folder.id)}
                                            className={`btn btn-sm rounded-pill ${currentFolder?.id === folder.id ? 'btn-primary' : 'btn-outline-secondary'}`}
                                        >
                                            {folder.name}
                                            {folder.documents_count != null && (
                                                <span className="ms-1 badge bg-white text-dark rounded-pill">{folder.documents_count}</span>
                                            )}
                                        </Link>
                                        {canEditOrDeleteFolder(folder) && (
                                            <>
                                                <button
                                                    type="button"
                                                    onClick={() => handleEditFolder(folder)}
                                                    className="btn btn-sm btn-outline-secondary rounded-pill p-1"
                                                    title="Editar carpeta"
                                                >
                                                    <i className="bi bi-pencil"></i>
                                                </button>
                                                {!folder.is_system && (
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDeleteFolder(folder)}
                                                        className="btn btn-sm btn-outline-danger rounded-pill p-1"
                                                        title="Eliminar"
                                                    >
                                                        <i className="bi bi-trash"></i>
                                                    </button>
                                                )}
                                            </>
                                        )}
                                    </span>
                                ))}
                            </div>
                        </div>
                    )}
                </>
            )}

            {/* Solo en la raíz: carpetas (tipos de documento) */}
            {!currentFolder && folders && folders.length > 0 && (
                <div className="mb-4">
                    <h5 className="fw-bold text-body mb-3">
                        <i className="bi bi-folder me-2"></i>
                        Tipos de documento (carpetas)
                    </h5>
                    <div className="row g-3">
                        {folders.map((folder) => (
                            <div key={folder.id} className="col-md-6 col-lg-4 col-xl-3">
                                <div
                                    className="card border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative"
                                    style={{ cursor: 'pointer', transition: 'transform 0.2s' }}
                                    onMouseEnter={(e) => (e.currentTarget.style.transform = 'scale(1.02)')}
                                    onMouseLeave={(e) => (e.currentTarget.style.transform = 'scale(1)')}
                                >
                                    <Link
                                        href={filters.user_id ? `${route('folders.show', folder.id)}?user_id=${filters.user_id}` : route('folders.show', folder.id)}
                                        className="text-decoration-none text-body"
                                    >
                                        <div
                                            className="card-header border-0 p-4 position-relative"
                                            style={{ backgroundColor: folder.color || '#EAEAEA', minHeight: '120px' }}
                                        >
                                            <div className="d-flex justify-content-between align-items-start">
                                                <i className={`bi ${getIconClass(folder.icon)} fs-1 opacity-75`}></i>
                                                <span
                                                    className="badge bg-primary text-white rounded-pill shadow-sm"
                                                    style={{ fontSize: '0.75rem', fontWeight: '600' }}
                                                >
                                                    <i className="bi bi-file-earmark-text me-1"></i>
                                                    {folder.documents_count ?? 0}
                                                </span>
                                            </div>
                                        </div>
                                    </Link>
                                    <div className="card-body p-3">
                                        <h6 className="card-title fw-bold mb-1">{folder.name}</h6>
                                        {folder.description && (
                                            <p className="card-text text-secondary small mb-2" style={{ fontSize: '0.85rem' }}>
                                                {folder.description}
                                            </p>
                                        )}
                                        {canEditOrDeleteFolder(folder) && (
                                            <div className="d-flex gap-1 mt-2">
                                                <button
                                                    type="button"
                                                    onClick={(e) => {
                                                        e.preventDefault();
                                                        handleEditFolder(folder);
                                                    }}
                                                    className="btn btn-sm btn-outline-secondary"
                                                    title="Editar carpeta"
                                                >
                                                    <i className="bi bi-pencil"></i>
                                                </button>
                                                {!folder.is_system && (
                                                    <button
                                                        type="button"
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            handleDeleteFolder(folder);
                                                        }}
                                                        className="btn btn-sm btn-outline-danger"
                                                        title="Eliminar"
                                                    >
                                                        <i className="bi bi-trash"></i>
                                                    </button>
                                                )}
                                            </div>
                                        )}
                                    </div>
                                </div>
                            </div>
                        ))}
                    </div>
                </div>
            )}

            {(!folders || folders.length === 0) && (!documents || documents.length === 0) && !currentFolder && (
                <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
                    <div className="card-body text-center py-5">
                        <i className="bi bi-folder-x fs-1 text-secondary mb-3 d-block"></i>
                        <h5 className="text-body fw-bold mb-2">No hay contenido</h5>
                        <p className="text-secondary mb-4">
                            {currentFolder
                                ? (isViewer ? 'Esta carpeta está vacía.' : 'Esta carpeta está vacía. Crea un documento (cartas, oficios, memos) con uno o más PDF.')
                                : (isViewer ? 'No hay carpetas asignadas para ver.' : 'Crea una carpeta por tipo de documento (ej. Cartas, Oficios, Memos) y luego registra documentos con sus archivos PDF.')}
                        </p>
                        {!isViewer && (
                        <button
                            onClick={currentFolder ? handleCreateDocument : handleCreateFolder}
                            className="btn btn-primary rounded-pill px-4"
                        >
                            <i className={`bi ${currentFolder ? 'bi-file-earmark-plus' : 'bi-folder-plus'} me-2`}></i>
                            {currentFolder ? 'Nuevo Documento' : 'Nueva Carpeta'}
                        </button>
                        )}
                    </div>
                </div>
            )}

            <FolderModal
                show={showFolderModal}
                onClose={() => setShowFolderModal(false)}
                folder={editingFolder}
                parentId={currentFolder?.id ?? null}
            />

            <DocumentModal
                show={showDocumentModal}
                onClose={() => setShowDocumentModal(false)}
                document={editingDocument}
                folderId={currentFolder?.id}
            />

            <DocumentPdfModal
                show={showPdfModal}
                onClose={() => { setShowPdfModal(false); setViewingDocument(null); setViewingFile(null); }}
                document={viewingDocument}
                file={viewingFile}
            />

            {movingDocument && (
                <div className="modal show d-block" tabIndex={-1} style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border-0 shadow-lg rounded-4">
                            <div className="modal-header border-0">
                                <h5 className="modal-title fw-bold">Mover a otra carpeta</h5>
                                <button type="button" className="btn-close" onClick={() => { setMovingDocument(null); setMoveTargetFolderId(''); }}></button>
                            </div>
                            <div className="modal-body">
                                <p className="text-secondary small mb-2">Documento: <strong>{movingDocument.numero || movingDocument.asunto || 'Sin número'}</strong></p>
                                <label className="form-label fw-semibold">Carpeta de destino</label>
                                <select className="form-select" value={moveTargetFolderId} onChange={(e) => setMoveTargetFolderId(e.target.value)}>
                                    <option value="">Seleccionar carpeta...</option>
                                    {(folders || []).filter((f) => String(f.id) !== String(movingDocument.folder_id)).map((f) => (
                                        <option key={f.id} value={f.id}>{f.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="modal-footer border-0">
                                <button type="button" className="btn btn-secondary" onClick={() => { setMovingDocument(null); setMoveTargetFolderId(''); }}>Cancelar</button>
                                <button type="button" className="btn btn-primary" disabled={!moveTargetFolderId} onClick={handleMoveDocument}>Mover</button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
            </div>
        </MainLayout>
    );
}
