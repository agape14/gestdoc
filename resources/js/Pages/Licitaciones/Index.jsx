import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';
import FolderCardModule from '@/Components/FolderCardModule';
import ModuleFolderEditModal from '@/Components/ModuleFolderEditModal';
import ModuleFolderModal from '@/Components/ModuleFolderModal';
import { GridPerPageSelect, SortTh } from '@/Components/GridTableControls';

const DetailForm = ({ item, onClose }) => {
    const existingDocs = item.documentos || [];
    const { data, setData, post, processing, errors } = useForm({
        _method: 'PUT',
        titulo: item.titulo || '',
        entidad: item.entidad || '',
        especialidad: item.especialidad || '',
        presupuesto: item.presupuesto || '',
        estado: item.estado || 'En Curso',
        modalidad: item.modalidad || '',
        clasificacion: item.clasificacion || '',
        consorcio: item.consorcio || false,
        nombre_rc: item.nombre_rc || '',
        nombre_consorcio: item.nombre_consorcio || '',
        consorciados: item.consorciados && typeof item.consorciados === 'string'
            ? JSON.parse(item.consorciados)
            : (item.consorciados || [{ nombre: '', porcentaje: '' }]),
        promesa_consorcio: null,
        documento_delete_ids: [],
        documentos: [],
    });

    const handleAddConsorciado = () => {
        setData('consorciados', [...data.consorciados, { nombre: '', porcentaje: '' }]);
    };

    const handleRemoveConsorciado = (index) => {
        const list = [...data.consorciados];
        list.splice(index, 1);
        setData('consorciados', list);
    };

    const handleConsorciadoChange = (e, index, field) => {
        const list = [...data.consorciados];
        list[index][field] = e.target.value;
        setData('consorciados', list);
    };

    const addDocumento = () => {
        setData('documentos', [...(data.documentos || []), { nombre: '', archivo: null }]);
    };

    const removeDocumento = (index) => {
        const list = data.documentos ? [...data.documentos] : [];
        list.splice(index, 1);
        setData('documentos', list);
    };

    const removeExistingDoc = (id) => {
        setData('documento_delete_ids', [...(data.documento_delete_ids || []), id]);
    };

    const undoRemoveExisting = (id) => {
        setData('documento_delete_ids', (data.documento_delete_ids || []).filter(x => x !== id));
    };

    const handleDocumentoChange = (index, field, value) => {
        const list = [...(data.documentos || [])];
        if (!list[index]) list[index] = { nombre: '', archivo: null };
        list[index][field] = value;
        setData('documentos', list);
    };

    const submit = (e) => {
        e.preventDefault();
        post(route('licitaciones.update', item.id), {
            forceFormData: true,
            onSuccess: () => {
                Swal.fire('Éxito', 'Licitación actualizada correctamente', 'success');
                onClose();
            }
        });
    };

    const toDelete = data.documento_delete_ids || [];

    return (
        <form onSubmit={submit} className="p-4 bg-white rounded-4 shadow-sm">
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h5 className="fw-bold mb-0">Detalle de Licitación</h5>
                <button type="button" className="btn-close" onClick={onClose}></button>
            </div>

            <div className="row g-3 mb-3">
                <div className="col-md-12">
                    <label className="form-label fw-bold small text-secondary">Licitación</label>
                    <input type="text" className="form-control" value={data.titulo} onChange={e => setData('titulo', e.target.value)} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Proyecto</label>
                    <input type="text" className="form-control" value={data.titulo} onChange={e => setData('titulo', e.target.value)} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Entidad</label>
                    <input type="text" className="form-control" value={data.entidad} onChange={e => setData('entidad', e.target.value)} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Presupuesto</label>
                    <div className="input-group">
                        <span className="input-group-text">S/</span>
                        <input type="number" step="0.01" className="form-control" value={data.presupuesto} onChange={e => setData('presupuesto', e.target.value)} />
                    </div>
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Modalidad</label>
                    <input type="text" className="form-control" value={data.modalidad} onChange={e => setData('modalidad', e.target.value)} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Estado</label>
                    <select className="form-select" value={data.estado} onChange={e => setData('estado', e.target.value)}>
                        <option value="Buena Pro">Buena Pro</option>
                        <option value="Nulo">Nulo</option>
                        <option value="Desierto">Desierto</option>
                        <option value="Perdido">Perdido</option>
                        <option value="En Curso">En Curso</option>
                    </select>
                </div>
                <div className="col-md-12">
                    <label className="form-label fw-bold small text-secondary">Tipo / Clasificación</label>
                    <input type="text" className="form-control bg-light" value={data.clasificacion} readOnly placeholder="Ej: PUBLICAS / CONSULTORIAS DE OBRA / PUENTES" />
                </div>
            </div>

            <hr className="my-3" />

            <div className="mb-3">
                <label className="form-label fw-bold small text-secondary">Documentación del Proceso (nombre + archivo)</label>
                {existingDocs.length > 0 && (
                    <div className="mb-2">
                        <span className="small text-secondary d-block mb-1">Documentos existentes</span>
                        {existingDocs.map((doc) => {
                            const marked = toDelete.includes(doc.id);
                            return (
                                <div key={doc.id} className={`d-flex align-items-center gap-2 p-2 mb-2 rounded ${marked ? 'bg-danger bg-opacity-10' : 'bg-body-tertiary'}`}>
                                    <span className="fw-medium small">{doc.nombre}</span>
                                    <a href={doc.url || `/storage/${doc.file_path}`} target="_blank" rel="noopener noreferrer" className="small">Ver archivo</a>
                                    {marked ? (
                                        <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => undoRemoveExisting(doc.id)}>Deshacer</button>
                                    ) : (
                                        <button type="button" className="btn btn-sm btn-outline-danger" onClick={() => removeExistingDoc(doc.id)}>Eliminar</button>
                                    )}
                                </div>
                            );
                        })}
                    </div>
                )}
                <span className="small text-secondary d-block mb-1">Nuevos documentos</span>
                {(data.documentos || []).map((doc, index) => (
                    <div key={index} className="row g-2 align-items-end mb-2 p-2 bg-body-tertiary rounded">
                        <div className="col-md-4">
                            <input type="text" className="form-control form-control-sm" placeholder="Nombre" value={doc.nombre || ''} onChange={e => handleDocumentoChange(index, 'nombre', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx" onChange={e => handleDocumentoChange(index, 'archivo', e.target.files[0] || null)} />
                            {doc.archivo && typeof doc.archivo === 'object' && doc.archivo.name && <small className="text-success">{doc.archivo.name}</small>}
                        </div>
                        <div className="col-md-2">
                            <button type="button" className="btn btn-outline-danger btn-sm w-100" onClick={() => removeDocumento(index)} title="Eliminar"><i className="bi bi-trash"></i></button>
                        </div>
                    </div>
                ))}
                <button type="button" className="btn btn-sm btn-outline-primary" onClick={addDocumento}>
                    <i className="bi bi-plus-lg me-1"></i> Agregar más documento
                </button>
            </div>

            <div className="form-check form-switch mb-3">
                <input className="form-check-input" type="checkbox" checked={data.consorcio} onChange={e => setData('consorcio', e.target.checked)} />
                <label className="form-check-label fw-bold">Consorcio</label>
            </div>

            {data.consorcio && (
                <div className="p-3 bg-light rounded mb-3">
                    <div className="row g-3 mb-3">
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">R.C.</label>
                            <input type="text" className="form-control" value={data.nombre_rc} onChange={e => setData('nombre_rc', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Consorcio</label>
                            <input type="text" className="form-control" value={data.nombre_consorcio} onChange={e => setData('nombre_consorcio', e.target.value)} />
                        </div>
                    </div>
                    <label className="form-label fw-bold small text-secondary">Consorciados</label>
                    {data.consorciados.map((item, index) => (
                        <div key={index} className="d-flex gap-2 mb-2">
                            <input type="text" className="form-control" placeholder="Nombre" value={item.nombre} onChange={e => handleConsorciadoChange(e, index, 'nombre')} />
                            <div className="input-group" style={{ width: '150px' }}>
                                <input type="number" className="form-control" placeholder="%" value={item.porcentaje} onChange={e => handleConsorciadoChange(e, index, 'porcentaje')} />
                                <span className="input-group-text">%</span>
                            </div>
                            {data.consorciados.length > 1 && (
                                <button type="button" className="btn btn-outline-danger" onClick={() => handleRemoveConsorciado(index)}>
                                    <i className="bi bi-trash"></i>
                                </button>
                            )}
                        </div>
                    ))}
                    <button type="button" className="btn btn-sm btn-outline-primary" onClick={handleAddConsorciado}>
                        <i className="bi bi-plus-circle me-1"></i> Agregar Consorciado
                    </button>
                    <div className="mt-3">
                        <label className="form-label fw-bold small text-secondary">Promesa de Consorcio</label>
                        <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx" onChange={e => setData('promesa_consorcio', e.target.files[0])} />
                    </div>
                </div>
            )}

            <div className="d-flex justify-content-end gap-2">
                <button type="button" className="btn btn-outline-secondary" onClick={onClose}>Cancelar</button>
                <button type="submit" disabled={processing} className="btn btn-primary">
                    <i className="bi bi-save me-2"></i> Guardar
                </button>
            </div>
        </form>
    );
};

const getIconClass = (iconName) => {
    const iconMap = {
        Lock: 'bi-lock-fill', Globe: 'bi-globe', Package: 'bi-box-seam', Settings: 'bi-gear-fill',
        MoreHorizontal: 'bi-three-dots', Briefcase: 'bi-briefcase-fill', HardHat: 'bi-hammer',
        Droplets: 'bi-droplet-fill', Waves: 'bi-water', School: 'bi-building', Road: 'bi-signpost-fill',
        Bridge: 'bi-bricks', Trophy: 'bi-trophy-fill', FileText: 'bi-file-text-fill', Folder: 'bi-folder-fill',
        Diagram: 'bi-diagram-3-fill', Tools: 'bi-tools', Lightning: 'bi-lightning-charge-fill',
        Tree: 'bi-tree-fill', Shield: 'bi-shield-fill-check', Star: 'bi-star-fill',
        Calendar: 'bi-calendar-check-fill', Archive: 'bi-archive-fill', ClipboardCheck: 'bi-clipboard-check-fill',
    };
    return iconMap[iconName] || 'bi-folder-fill';
};

export default function Index({ licitaciones, groupedByEspecialidad, filters, flash, userRole, anulados = [], operadores = [], folders = [], currentFolder = null, breadcrumb = [] }) {
    const { auth } = usePage().props;
    const currentUserRole = userRole || auth?.user?.role || 'Visualizador';
    const isAdmin = currentUserRole === 'Administrador';
    const [search, setSearch] = useState(filters.search || '');
    const [dateStart, setDateStart] = useState(filters.date_start || '');
    const [dateEnd, setDateEnd] = useState(filters.date_end || '');
    const [operatorId, setOperatorId] = useState(filters.user_id || '');
    const [expandedRow, setExpandedRow] = useState(null);
    const [showGrouped, setShowGrouped] = useState(true);
    const [tab, setTab] = useState('activos');
    const [showFolderModal, setShowFolderModal] = useState(false);
    const [editingFolder, setEditingFolder] = useState(null);
    const [selectedIds, setSelectedIds] = useState([]);
    const [movingItem, setMovingItem] = useState(null);
    const [movingIds, setMovingIds] = useState([]);
    const [moveTargetFolderId, setMoveTargetFolderId] = useState('');
    const hasFolders = Boolean(folders && folders.length > 0);
    const hasMove = hasFolders && currentUserRole !== 'Visualizador';

    const sortField = filters.sort || 'created_at';
    const sortDirection = filters.direction === 'asc' ? 'asc' : 'desc';
    const navigateList = (extra = {}) => {
        router.get(route('licitaciones.index'), {
            ...filters,
            search,
            date_start: dateStart,
            date_end: dateEnd,
            folder_id: filters.folder_id,
            ...(isAdmin ? { user_id: operatorId || undefined } : {}),
            ...extra,
        }, { preserveState: true, preserveScroll: true, replace: true });
    };
    const toggleSort = (field) => {
        const nextDir = sortField === field && sortDirection === 'asc' ? 'desc' : 'asc';
        navigateList({ sort: field, direction: nextDir, page: 1 });
    };

    const toggleSelectOne = (id) => {
        setSelectedIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
    };
    const toggleSelectAll = () => {
        const data = licitaciones.data || [];
        if (selectedIds.length === data.length) setSelectedIds([]);
        else setSelectedIds(data.map((item) => item.id));
    };
    const openBulkMoveModal = () => {
        if (selectedIds.length === 0) return;
        setMovingItem(null);
        setMovingIds([...selectedIds]);
        setMoveTargetFolderId(currentFolder?.id ? String(currentFolder.id) : '');
    };
    const openSingleMoveModal = (item) => {
        setMovingItem(item);
        setMovingIds([]);
        setMoveTargetFolderId(currentFolder?.id ? String(currentFolder.id) : '');
    };
    const closeMoveModal = () => {
        setMovingItem(null);
        setMovingIds([]);
        setMoveTargetFolderId('');
    };
    const handleMove = () => {
        if (!moveTargetFolderId) return;
        if (movingItem) {
            router.put(route('licitaciones.move', movingItem.id), { folder_id: moveTargetFolderId }, { preserveScroll: true, onSuccess: closeMoveModal });
        } else if (movingIds.length > 0) {
            router.post(route('licitaciones.move-bulk'), { item_ids: movingIds, folder_id: moveTargetFolderId }, { preserveScroll: true, onSuccess: () => { closeMoveModal(); setSelectedIds([]); } });
        }
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

    useEffect(() => {
        const timer = setTimeout(() => {
            const params = { ...filters, search, date_start: dateStart, date_end: dateEnd, folder_id: filters.folder_id };
            if (isAdmin) params.user_id = operatorId || undefined;
            if (search !== (filters.search || '') || dateStart !== (filters.date_start || '') || dateEnd !== (filters.date_end || '') || operatorId !== (filters.user_id || '')) {
                router.get(route('licitaciones.index'), { ...params, page: 1 }, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search, dateStart, dateEnd, operatorId]);

    const buildIndexParams = (extra = {}) => {
        const params = { ...filters, ...extra };
        if (filters.folder_id) params.folder_id = filters.folder_id;
        return params;
    };

    const handleAnular = (id) => {
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
                router.delete(route('licitaciones.destroy', id));
            }
        });
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (filters.tipo) params.append('tipo', filters.tipo);
        if (filters.especialidad) params.append('especialidad', filters.especialidad);
        window.location.href = route('licitaciones.export') + '?' + params.toString();
    };

    const handleExportProject = (id) => {
        window.location.href = route('licitaciones.export-project', id);
    };

    const allLicitaciones = licitaciones.data || [];
    const grouped = groupedByEspecialidad || {};

    return (
        <MainLayout>
            <Head title="Licitaciones" />
            <div className="grid-page-wrapper min-w-0 w-100" style={{ maxWidth: '100%' }}>
            {flash?.success && (
                <div className="alert alert-success alert-dismissible fade show" role="alert">
                    {flash.success}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            )}

            {/* Breadcrumb carpetas */}
            {breadcrumb && breadcrumb.length > 0 && (
                <nav aria-label="breadcrumb" className="mb-3">
                    <ol className="breadcrumb bg-body-tertiary rounded-3 p-3">
                        <li className="breadcrumb-item">
                            <Link href={route('licitaciones.index')} className="text-decoration-none">
                                <i className="bi bi-house-door-fill me-1"></i> Licitaciones
                            </Link>
                        </li>
                        {breadcrumb.map((folder, index) => (
                            <li key={folder.id} className={`breadcrumb-item ${index === breadcrumb.length - 1 ? 'active' : ''}`}>
                                {index === breadcrumb.length - 1 ? folder.name : (
                                    <Link href={route('licitaciones.index', { folder_id: folder.id })} className="text-decoration-none">{folder.name}</Link>
                                )}
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
                                indexRoute="licitaciones.index"
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
                    <h2 className="fw-bold text-body mb-0">Licitaciones</h2>
                    <p className="text-secondary mb-0">Gestión de licitaciones</p>
                </div>
                <div className="d-flex gap-2 flex-wrap">
                    {hasMove && (
                        <button
                            type="button"
                            className="btn btn-outline-info rounded-pill px-4"
                            onClick={openBulkMoveModal}
                            disabled={selectedIds.length === 0}
                            title={selectedIds.length === 0 ? 'Seleccione al menos un registro' : `Mover ${selectedIds.length} registro(s) a otra carpeta`}
                        >
                            <i className="bi bi-folder-symlink me-2"></i> Mover seleccionados ({selectedIds.length})
                        </button>
                    )}
                    {currentUserRole !== 'Visualizador' && (
                        <>
                            <button onClick={handleExport} className="btn btn-success rounded-pill px-4">
                                <i className="bi bi-file-earmark-excel me-2"></i> Exportar Excel
                            </button>
                            <Link href={route('licitaciones.create', currentFolder?.id ? { folder_id: currentFolder.id } : {})} className="btn btn-success shadow-sm rounded-pill px-4">
                                <i className="bi bi-plus-lg me-2"></i> Nueva Licitación
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
                            <select
                                className="form-select rounded-pill bg-body-tertiary border-0"
                                value={operatorId}
                                onChange={(e) => setOperatorId(e.target.value)}
                            >
                                <option value="">Todos los operadores</option>
                                {operadores.map(op => (
                                    <option key={op.id} value={op.id}>{op.name}</option>
                                ))}
                            </select>
                        </div>
                    )}
                    <div className="col-12 col-md-6 col-lg-4">
                        <label className="form-label small text-secondary mb-1 d-none d-lg-block">Buscar</label>
                        <div className="input-group min-w-0">
                            <span className="input-group-text bg-body-tertiary border-end-0 rounded-start-pill ps-3"><i className="bi bi-search text-secondary"></i></span>
                            <input
                                type="text"
                                className="form-control border-start-0 bg-body-tertiary rounded-end-pill"
                                placeholder="Proyecto, entidad o especialidad..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </div>
                    <div className="col-12 col-md-6 col-lg-2">
                        <label className="form-label small text-secondary mb-1 d-none d-lg-block">Desde</label>
                        <input
                            type="date"
                            className="form-control rounded-pill bg-body-tertiary border-0 px-3"
                            placeholder="Fecha Inicio"
                            value={dateStart}
                            onChange={(e) => setDateStart(e.target.value)}
                        />
                    </div>
                    <div className="col-12 col-md-6 col-lg-2">
                        <label className="form-label small text-secondary mb-1 d-none d-lg-block">Hasta</label>
                        <input
                            type="date"
                            className="form-control rounded-pill bg-body-tertiary border-0 px-3"
                            placeholder="Fecha Fin"
                            value={dateEnd}
                            onChange={(e) => setDateEnd(e.target.value)}
                        />
                    </div>
                    <div className="col-12 col-md-6 col-lg-auto">
                        <label className="form-label small text-secondary mb-1 d-none d-lg-block">&nbsp;</label>
                        <GridPerPageSelect value={String(filters.per_page ?? '50')} onChange={(v) => navigateList({ per_page: v, page: 1 })} />
                    </div>
                </div>
            </div>

            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body min-w-0 w-100">
                <div className="card-header bg-body border-0 py-3 d-flex flex-wrap justify-content-between align-items-center gap-2">
                    <div className="d-flex align-items-center gap-2 min-w-0">
                        <h6 className="mb-0 fw-bold text-truncate">Listado {showGrouped ? 'Agrupado por Especialidad' : 'General'}</h6>
                        {currentUserRole === 'Administrador' && (
                            <ul className="nav nav-pills nav-pills-sm mb-0">
                                <li className="nav-item">
                                    <button
                                        className={`nav-link py-1 px-2 rounded-pill ${tab === 'activos' ? 'active' : ''}`}
                                        onClick={() => setTab('activos')}
                                    >
                                        Activos
                                    </button>
                                </li>
                                <li className="nav-item">
                                    <button
                                        className={`nav-link py-1 px-2 rounded-pill ${tab === 'anulados' ? 'active' : ''}`}
                                        onClick={() => setTab('anulados')}
                                    >
                                        Anulados ({anulados.length})
                                    </button>
                                </li>
                            </ul>
                        )}
                    </div>
                    <button className="btn btn-sm btn-outline-secondary flex-shrink-0" onClick={() => setShowGrouped(!showGrouped)}>
                        <i className={`bi bi-${showGrouped ? 'list' : 'grid'} me-1`}></i>
                        {showGrouped ? 'Vista General' : 'Vista Agrupada'}
                    </button>
                </div>
                <div className="table-responsive overflow-x-auto min-w-0" style={{ WebkitOverflowScrolling: 'touch' }}>
                    <table className="table table-hover align-middle mb-0" style={{ minWidth: '640px' }}>
                        <thead className="border-bottom text-secondary small text-uppercase">
                            <tr>
                                {hasMove && (
                                    <th scope="col" className="ps-4 py-3" style={{ width: '40px' }}>
                                        {!showGrouped && tab === 'activos' && (licitaciones.data || []).length > 0 && (
                                            <input type="checkbox" className="form-check-input" checked={selectedIds.length === (licitaciones.data || []).length} onChange={toggleSelectAll} title="Seleccionar todos" />
                                        )}
                                    </th>
                                )}
                                <SortTh label="PROYECTO" field="titulo" currentSort={sortField} currentDirection={sortDirection} onSort={toggleSort} />
                                <SortTh label="ENTIDAD" field="entidad" currentSort={sortField} currentDirection={sortDirection} onSort={toggleSort} />
                                <SortTh label="ESPECIALIDAD" field="especialidad" currentSort={sortField} currentDirection={sortDirection} onSort={toggleSort} />
                                <SortTh label="PRESUPUESTO" field="presupuesto" currentSort={sortField} currentDirection={sortDirection} onSort={toggleSort} />
                                <SortTh label="ESTADO" field="estado" currentSort={sortField} currentDirection={sortDirection} onSort={toggleSort} />
                                <th scope="col" className="text-end pe-4 py-3">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            {tab === 'anulados' && isAdmin ? (
                                anulados.length > 0 ? (
                                    anulados.map(licitacion => (
                                        <tr key={licitacion.id}>
                                            {hasMove && <td className="ps-4 py-3"></td>}
                                            <td className="ps-4 py-3 fw-medium text-body">{licitacion.titulo}</td>
                                            <td className="text-secondary">{licitacion.entidad}</td>
                                            <td className="text-secondary">{licitacion.especialidad || '-'}</td>
                                            <td className="text-secondary fw-bold text-body">
                                                S/ {parseFloat(licitacion.presupuesto || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td>
                                                <span className="badge bg-secondary rounded-pill px-3">Anulado</span>
                                            </td>
                                            <td className="text-end pe-4">
                                                <button
                                                    onClick={(e) => { e.stopPropagation(); setExpandedRow(expandedRow === licitacion.id ? null : licitacion.id); }}
                                                    className="btn btn-sm btn-outline-primary"
                                                    title="Ver"
                                                >
                                                    <i className="bi bi-eye"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    ))
                                ) : (
                                    <tr>
                                        <td colSpan={hasMove ? 7 : 6} className="text-center py-5 text-muted">No hay registros anulados.</td>
                                    </tr>
                                )
                            ) : showGrouped && Object.keys(grouped).length > 0 ? (
                                Object.entries(grouped).map(([especialidad, items]) => (
                                    <React.Fragment key={especialidad}>
                                        <tr className="bg-light">
                                            <td colSpan={hasMove ? 7 : 6} className="ps-4 py-2 fw-bold text-primary">
                                                <i className="bi bi-folder-fill me-2"></i>
                                                {especialidad || 'Sin Especialidad'} ({items.length} {items.length === 1 ? 'registro' : 'registros'})
                                            </td>
                                        </tr>
                                        {items.map(licitacion => (
                                            <React.Fragment key={licitacion.id}>
                                                <tr
                                                    className="cursor-pointer"
                                                    onClick={() => setExpandedRow(expandedRow === licitacion.id ? null : licitacion.id)}
                                                    style={{ backgroundColor: expandedRow === licitacion.id ? 'var(--bs-light)' : 'transparent' }}
                                                >
                                                    {hasMove && (
                                                        <td className="ps-4 py-3" onClick={(e) => e.stopPropagation()}>
                                                            <input type="checkbox" className="form-check-input" checked={selectedIds.includes(licitacion.id)} onChange={() => toggleSelectOne(licitacion.id)} title="Seleccionar" />
                                                        </td>
                                                    )}
                                                    <td className="ps-4 py-3 fw-medium text-body">{licitacion.titulo}</td>
                                                    <td className="text-secondary">{licitacion.entidad}</td>
                                                    <td className="text-secondary">{licitacion.especialidad || '-'}</td>
                                                    <td className="text-secondary fw-bold text-body">
                                                        S/ {parseFloat(licitacion.presupuesto || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                    <td>
                                                        <span className={`badge bg-${licitacion.estado === 'En Curso' ? 'warning' : 'success'}-subtle text-${licitacion.estado === 'En Curso' ? 'warning-emphasis' : 'success'} border border-${licitacion.estado === 'En Curso' ? 'warning' : 'success'}-subtle rounded-pill px-3`}>
                                                            {licitacion.estado}
                                                        </span>
                                                    </td>
                                                    <td className="text-end pe-4">
                                                        {currentUserRole === 'Visualizador' ? (
                                                            <button className="btn btn-sm btn-outline-info" title="Ver">
                                                                <i className="bi bi-eye"></i>
                                                            </button>
                                                        ) : (
                                                            <>
                                                                {hasMove && (
                                                                    <button type="button" onClick={(e) => { e.stopPropagation(); openSingleMoveModal(licitacion); }} className="btn btn-sm btn-outline-info me-1" title="Mover a otra carpeta"><i className="bi bi-folder-symlink"></i></button>
                                                                )}
                                                                <button
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        setExpandedRow(expandedRow === licitacion.id ? null : licitacion.id);
                                                                    }}
                                                                    className="btn btn-sm btn-outline-primary me-1"
                                                                >
                                                                    <i className={`bi bi-chevron-${expandedRow === licitacion.id ? 'up' : 'down'}`}></i>
                                                                </button>
                                                                <button
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        handleExportProject(licitacion.id);
                                                                    }}
                                                                    className="btn btn-sm btn-outline-success me-1"
                                                                    title="Exportar a Excel"
                                                                >
                                                                    <i className="bi bi-file-earmark-excel"></i>
                                                                </button>
                                                                {canDelete(licitacion) && (
                                                                    <button
                                                                        onClick={(e) => {
                                                                            e.stopPropagation();
                                                                            handleAnular(licitacion.id);
                                                                        }}
                                                                        className="btn btn-sm btn-outline-danger"
                                                                        title="Anular"
                                                                    >
                                                                        <i className="bi bi-x-circle"></i>
                                                                    </button>
                                                                )}
                                                            </>
                                                        )}
                                                    </td>
                                                </tr>
                                                {expandedRow === licitacion.id && (
                                                    <tr>
                                                        <td colSpan={hasMove ? 7 : 6} className="p-0 border-0">
                                                            <div className="p-3 bg-light">
                                                                <DetailForm item={licitacion} onClose={() => setExpandedRow(null)} />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                )}
                                            </React.Fragment>
                                        ))}
                                    </React.Fragment>
                                ))
                            ) : allLicitaciones.length > 0 ? (
                                allLicitaciones.map(licitacion => (
                                    <React.Fragment key={licitacion.id}>
                                        <tr
                                            className="cursor-pointer"
                                            onClick={() => setExpandedRow(expandedRow === licitacion.id ? null : licitacion.id)}
                                            style={{ backgroundColor: expandedRow === licitacion.id ? 'var(--bs-light)' : 'transparent' }}
                                        >
                                            {hasMove && (
                                                <td className="ps-4 py-3" onClick={(e) => e.stopPropagation()}>
                                                    <input type="checkbox" className="form-check-input" checked={selectedIds.includes(licitacion.id)} onChange={() => toggleSelectOne(licitacion.id)} title="Seleccionar" />
                                                </td>
                                            )}
                                            <td className="ps-4 py-3 fw-medium text-body">{licitacion.titulo}</td>
                                            <td className="text-secondary">{licitacion.entidad}</td>
                                            <td className="text-secondary">{licitacion.especialidad || '-'}</td>
                                            <td className="text-secondary fw-bold text-body">
                                                S/ {parseFloat(licitacion.presupuesto || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td>
                                                <span className={`badge bg-${licitacion.estado === 'En Curso' ? 'warning' : 'success'}-subtle text-${licitacion.estado === 'En Curso' ? 'warning-emphasis' : 'success'} border border-${licitacion.estado === 'En Curso' ? 'warning' : 'success'}-subtle rounded-pill px-3`}>
                                                    {licitacion.estado}
                                                </span>
                                            </td>
                                            <td className="text-end pe-4">
                                                {currentUserRole === 'Visualizador' ? (
                                                    <button className="btn btn-sm btn-outline-info" title="Ver">
                                                        <i className="bi bi-eye"></i>
                                                    </button>
                                                ) : (
                                                    <>
                                                        {hasMove && (
                                                            <button type="button" onClick={(e) => { e.stopPropagation(); openSingleMoveModal(licitacion); }} className="btn btn-sm btn-outline-info me-1" title="Mover a otra carpeta"><i className="bi bi-folder-symlink"></i></button>
                                                        )}
                                                        <button
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                setExpandedRow(expandedRow === licitacion.id ? null : licitacion.id);
                                                            }}
                                                            className="btn btn-sm btn-outline-primary me-1"
                                                        >
                                                            <i className={`bi bi-chevron-${expandedRow === licitacion.id ? 'up' : 'down'}`}></i>
                                                        </button>
                                                        <button
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                handleExportProject(licitacion.id);
                                                            }}
                                                            className="btn btn-sm btn-outline-success me-1"
                                                            title="Exportar a Excel"
                                                        >
                                                            <i className="bi bi-file-earmark-excel"></i>
                                                        </button>
                                                        {canDelete(licitacion) && (
                                                            <button
                                                                onClick={(e) => {
                                                                    e.stopPropagation();
                                                                    handleAnular(licitacion.id);
                                                                }}
                                                                className="btn btn-sm btn-outline-danger"
                                                                title="Anular"
                                                            >
                                                                <i className="bi bi-x-circle"></i>
                                                            </button>
                                                        )}
                                                    </>
                                                )}
                                            </td>
                                        </tr>
                                        {expandedRow === licitacion.id && (
                                            <tr>
                                                <td colSpan={hasMove ? 7 : 6} className="p-0 border-0">
                                                    <div className="p-3 bg-light">
                                                        <DetailForm item={licitacion} onClose={() => setExpandedRow(null)} />
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                    </React.Fragment>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan={hasMove ? 7 : 6} className="text-center py-5 text-muted">No se encontraron licitaciones.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {licitaciones.links && licitaciones.links.length > 3 && (
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

            <ModuleFolderModal
                show={showFolderModal}
                onClose={() => setShowFolderModal(false)}
                storeFolderRoute="licitaciones.folders.store"
                parentId={currentFolder?.id ?? null}
            />

            {editingFolder && (
                <ModuleFolderEditModal
                    show={!!editingFolder}
                    onClose={() => setEditingFolder(null)}
                    folder={editingFolder}
                />
            )}

            {(movingItem || movingIds.length > 0) && hasMove && (
                <div className="modal show d-block" tabIndex={-1} style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1050 }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border-0 shadow-lg rounded-4">
                            <div className="modal-header border-0">
                                <h5 className="modal-title fw-bold">{movingItem ? 'Mover a otra carpeta' : `Mover ${movingIds.length} registro(s) a otra carpeta`}</h5>
                                <button type="button" className="btn-close" onClick={closeMoveModal}></button>
                            </div>
                            <div className="modal-body">
                                {movingItem ? <p className="text-secondary small mb-2">Se moverá el registro a la carpeta que elijas.</p> : <p className="text-secondary small mb-2">Se moverán <strong>{movingIds.length}</strong> registro(s) a la carpeta que elijas.</p>}
                                <label className="form-label fw-semibold">Carpeta de destino</label>
                                <select className="form-select" value={moveTargetFolderId} onChange={(e) => setMoveTargetFolderId(e.target.value)}>
                                    <option value="">Seleccionar carpeta...</option>
                                    {(folders || []).filter((f) => String(f.id) !== (movingItem ? String(movingItem.folder_id) : (currentFolder?.id ? String(currentFolder.id) : ''))).map((f) => (
                                        <option key={f.id} value={f.id}>{f.name}</option>
                                    ))}
                                </select>
                            </div>
                            <div className="modal-footer border-0">
                                <button type="button" className="btn btn-secondary" onClick={closeMoveModal}>Cancelar</button>
                                <button type="button" className="btn btn-primary" disabled={!moveTargetFolderId} onClick={handleMove}>Mover</button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
            </div>
        </MainLayout>
    );
}
