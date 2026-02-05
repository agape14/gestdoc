import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';
import PdfModal from '@/Components/PdfModal';
import ModuleFolderModal from '@/Components/ModuleFolderModal';
import FolderCardModule from '@/Components/FolderCardModule';
import ModuleFolderEditModal from '@/Components/ModuleFolderEditModal';

const DetailForm = ({ item, onClose }) => {
    const { auth } = usePage().props;
    const currentUserRole = auth?.user?.role || 'Visualizador';
    const canEdit = currentUserRole === 'Administrador' || (currentUserRole === 'Operador' && item.user_id === auth?.user?.id);

    const { data, setData, post, processing, errors } = useForm({
        _method: 'PUT',
        titulo: item.titulo || '',
        entidad: item.entidad || '',
        modalidad: item.modalidad || '',
        especialidad: item.especialidad || '',
        tipo_servicio: item.tipo_servicio || '',
        presupuesto: item.presupuesto || '',
        estado: item.estado || 'En Curso',
        duracion: item.duracion || '',
        plazo_ejecucion: item.plazo_ejecucion || '',
        tiempo_culminacion: item.tiempo_culminacion || '',
        plantel_tecnico_aplica: item.plantel_tecnico_aplica || false,
        valorizaciones_aplica: item.valorizaciones_aplica || false,
        liquidacion_aplica: item.liquidacion_aplica || false,
        contrato_archivo: null,
        tdr_archivo: null,
        informes_tecnicos: null,
        actas_resoluciones: null,
        conformidad_tecnica: null,
        panel_fotografico: null,
        cargos: Array.isArray(item.cargos)
            ? item.cargos.map(c => typeof c === 'object' && c !== null ? { cargo: c.cargo || '', nombre: c.nombre || '' } : { cargo: String(c), nombre: '' })
            : [{ cargo: '', nombre: '' }],
    });

    const handleAddCargo = () => {
        setData('cargos', [...(data.cargos || []), { cargo: '', nombre: '' }]);
    };
    const handleRemoveCargo = (index) => {
        const list = (data.cargos || []).filter((_, i) => i !== index);
        setData('cargos', list.length ? list : [{ cargo: '', nombre: '' }]);
    };
    const handleCargoChange = (index, field, value) => {
        const list = [...(data.cargos || [])];
        if (!list[index]) list[index] = { cargo: '', nombre: '' };
        list[index][field] = value;
        setData('cargos', list);
    };

    const submit = (e) => {
        e.preventDefault();
        if (!canEdit) {
            Swal.fire('Error', 'No tienes permiso para editar este registro', 'error');
            return;
        }
        post(route('proveedor-servicios.update', item.id), {
            forceFormData: true,
            transform: (d) => ({ ...d, cargos: typeof d.cargos !== 'undefined' ? JSON.stringify(d.cargos) : undefined }),
            onSuccess: () => {
                Swal.fire('Éxito', 'Registro actualizado correctamente', 'success');
                onClose();
            }
        });
    };

    return (
        <form onSubmit={submit} className="p-4 bg-white rounded-4 shadow-sm">
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h5 className="fw-bold mb-0">Detalle de Proveedor de Servicios</h5>
                <button type="button" className="btn-close" onClick={onClose}></button>
            </div>

            <div className="row g-3 mb-3">
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Servicio</label>
                    <input type="text" className="form-control" value={data.titulo} onChange={e => setData('titulo', e.target.value)} disabled={!canEdit} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Entidad</label>
                    <input type="text" className="form-control" value={data.entidad} onChange={e => setData('entidad', e.target.value)} disabled={!canEdit} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Modalidad</label>
                    <input type="text" className="form-control" value={data.modalidad} onChange={e => setData('modalidad', e.target.value)} disabled={!canEdit} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Especialidad</label>
                    <input type="text" className="form-control" value={data.especialidad} onChange={e => setData('especialidad', e.target.value)} disabled={!canEdit} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Tipo</label>
                    <input type="text" className="form-control" value={data.tipo_servicio} onChange={e => setData('tipo_servicio', e.target.value)} disabled={!canEdit} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Presupuesto</label>
                    <div className="input-group">
                        <span className="input-group-text">S/</span>
                        <input type="number" step="0.01" className="form-control" value={data.presupuesto} onChange={e => setData('presupuesto', e.target.value)} disabled={!canEdit} />
                    </div>
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Plazo de Ejecución</label>
                    <input type="text" className="form-control" value={data.plazo_ejecucion} onChange={e => setData('plazo_ejecucion', e.target.value)} disabled={!canEdit} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Tiempo de Culminación</label>
                    <input type="text" className="form-control" value={data.tiempo_culminacion} onChange={e => setData('tiempo_culminacion', e.target.value)} disabled={!canEdit} />
                </div>
            </div>

            <hr className="my-3" />

            <div className="row g-3 mb-3">
                <div className="col-md-4">
                    <div className="form-check form-switch">
                        <input className="form-check-input" type="checkbox" checked={data.plantel_tecnico_aplica} onChange={e => setData('plantel_tecnico_aplica', e.target.checked)} disabled={!canEdit} />
                        <label className="form-check-label fw-bold small text-secondary">Plantel Técnico: {data.plantel_tecnico_aplica ? 'Corresponde' : 'No Corresponde'}</label>
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-check form-switch">
                        <input className="form-check-input" type="checkbox" checked={data.valorizaciones_aplica} onChange={e => setData('valorizaciones_aplica', e.target.checked)} disabled={!canEdit} />
                        <label className="form-check-label fw-bold small text-secondary">Valorizaciones: {data.valorizaciones_aplica ? 'Corresponde' : 'No Corresponde'}</label>
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="form-check form-switch">
                        <input className="form-check-input" type="checkbox" checked={data.liquidacion_aplica} onChange={e => setData('liquidacion_aplica', e.target.checked)} disabled={!canEdit} />
                        <label className="form-check-label fw-bold small text-secondary">Liquidación: {data.liquidacion_aplica ? 'Corresponde' : 'No Corresponde'}</label>
                    </div>
                </div>
            </div>

            <div className="mb-3">
                <label className="form-label fw-bold small text-secondary">Cargos (identificar cargo de quien es)</label>
                {(data.cargos || []).map((c, index) => (
                    <div key={index} className="d-flex gap-2 align-items-center mb-2">
                        <input type="text" className="form-control form-control-sm" placeholder="Cargo" value={c.cargo || ''} onChange={e => handleCargoChange(index, 'cargo', e.target.value)} disabled={!canEdit} />
                        <input type="text" className="form-control form-control-sm" placeholder="Nombre" value={c.nombre || ''} onChange={e => handleCargoChange(index, 'nombre', e.target.value)} disabled={!canEdit} />
                        {(data.cargos || []).length > 1 && canEdit && (
                            <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => handleRemoveCargo(index)}><i className="bi bi-trash"></i></button>
                        )}
                    </div>
                ))}
                {canEdit && (
                    <button type="button" className="btn btn-sm btn-outline-primary" onClick={handleAddCargo}>
                        <i className="bi bi-plus-lg me-1"></i> Agregar cargo
                    </button>
                )}
            </div>

            <div className="row g-3 mb-3">
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Contrato</label>
                    <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx" onChange={e => setData('contrato_archivo', e.target.files[0])} disabled={!canEdit} />
                    {item.contrato_archivo && (
                        <a href={`/storage/${item.contrato_archivo}`} target="_blank" className="small text-primary mt-1 d-block">
                            <i className="bi bi-file-earmark-pdf"></i> Ver archivo actual
                        </a>
                    )}
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">TDR</label>
                    <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx" onChange={e => setData('tdr_archivo', e.target.files[0])} disabled={!canEdit} />
                    {item.tdr_archivo && (
                        <a href={`/storage/${item.tdr_archivo}`} target="_blank" className="small text-primary mt-1 d-block">
                            <i className="bi bi-file-earmark-pdf"></i> Ver archivo actual
                        </a>
                    )}
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Informes Técnicos</label>
                    <input type="file" className="form-control form-control-sm" multiple accept=".pdf,.doc,.docx" onChange={e => setData('informes_tecnicos', Array.from(e.target.files))} disabled={!canEdit} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Panel Fotográfico</label>
                    <input type="file" className="form-control form-control-sm" accept="image/*" onChange={e => setData('panel_fotografico', e.target.files[0])} disabled={!canEdit} />
                    {item.panel_fotografico && (
                        <a href={`/storage/${item.panel_fotografico}`} target="_blank" className="small text-primary mt-1 d-block">
                            <i className="bi bi-image"></i> Ver imagen actual
                        </a>
                    )}
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Actas y Resoluciones</label>
                    <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx" onChange={e => setData('actas_resoluciones', e.target.files[0])} disabled={!canEdit} />
                    {item.actas_resoluciones && (
                        <a href={`/storage/${item.actas_resoluciones}`} target="_blank" className="small text-primary mt-1 d-block">
                            <i className="bi bi-file-earmark-pdf"></i> Ver archivo actual
                        </a>
                    )}
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Conformidad Técnica</label>
                    <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx" onChange={e => setData('conformidad_tecnica', e.target.files[0])} disabled={!canEdit} />
                    {item.conformidad_tecnica && (
                        <a href={`/storage/${item.conformidad_tecnica}`} target="_blank" className="small text-primary mt-1 d-block">
                            <i className="bi bi-file-earmark-pdf"></i> Ver archivo actual
                        </a>
                    )}
                </div>
            </div>

            <div className="d-flex justify-content-between align-items-center mt-3">
                <a href={route('licitaciones.index')} className="btn btn-link text-decoration-none btn-sm">
                    <i className="bi bi-box-arrow-up-right me-1"></i> Ver Proceso (Licitación)
                </a>
                {canEdit && (
                    <div className="d-flex gap-2">
                        <button type="button" className="btn btn-outline-secondary" onClick={onClose}>Cancelar</button>
                        <button type="submit" disabled={processing} className="btn btn-primary">
                            <i className="bi bi-save me-2"></i> Guardar
                        </button>
                    </div>
                )}
            </div>
        </form>
    );
};

const getIconClass = (iconName) => {
    const iconMap = { Lock: 'bi-lock-fill', Globe: 'bi-globe', Folder: 'bi-folder-fill', Building: 'bi-building', Road: 'bi-signpost-fill' };
    return iconMap[iconName] || 'bi-folder-fill';
};

const getDocumentLinks = (item) => {
    const links = [];
    if (item.contrato_archivo) links.push({ label: 'Contrato', path: item.contrato_archivo });
    if (item.tdr_archivo) links.push({ label: 'TDR', path: item.tdr_archivo });
    if (item.actas_resoluciones) links.push({ label: 'Actas / Resoluciones', path: item.actas_resoluciones });
    if (item.conformidad_tecnica) links.push({ label: 'Conformidad técnica', path: item.conformidad_tecnica });
    (item.documentos || []).forEach((d, i) => {
        const path = d.archivo || d.path;
        if (path) links.push({ label: d.nombre || `Documento ${i + 1}`, path });
    });
    return links;
};

export default function Index({ servicios, groupedByEspecialidad, filters, flash, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [], sharedReadOnlyIds = [] }) {
    const { auth } = usePage().props;
    const currentUserRole = userRole || auth?.user?.role || 'Visualizador';
    const isAdmin = currentUserRole === 'Administrador';
    const [search, setSearch] = useState(filters.search || '');
    const [operatorId, setOperatorId] = useState(filters.user_id || '');
    const [expandedRow, setExpandedRow] = useState(null);
    const [showGrouped, setShowGrouped] = useState(true);
    const [showFolderModal, setShowFolderModal] = useState(false);
    const [editingFolder, setEditingFolder] = useState(null);
    const [showDocumentsModal, setShowDocumentsModal] = useState(false);
    const [listDocumentLinks, setListDocumentLinks] = useState([]);
    const [showPdfModal, setShowPdfModal] = useState(false);
    const [pdfModalUrl, setPdfModalUrl] = useState('');
    const [pdfModalTitle, setPdfModalTitle] = useState('');
    const [showShareModal, setShowShareModal] = useState(false);
    const [shareItem, setShareItem] = useState(null);
    const [shareTargetId, setShareTargetId] = useState('');
    const [shareExpiresAt, setShareExpiresAt] = useState('');
    const [shareCanEdit, setShareCanEdit] = useState(false);

    const breadcrumbTitle = (breadcrumb && breadcrumb.length > 0) ? breadcrumb.map(f => f.name).join(' / ') : (currentFolder?.name || 'Proveedor de Servicios');

    const buildIndexParams = (extra = {}) => ({ ...filters, ...extra, folder_id: filters.folder_id });

    useEffect(() => {
        const timer = setTimeout(() => {
            const params = { ...filters, search, folder_id: filters.folder_id };
            if (isAdmin) params.user_id = operatorId || undefined;
            if (search !== (filters.search || '') || operatorId !== (filters.user_id || '')) {
                router.get(route('proveedor-servicios.index'), params, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search, operatorId]);

    const handleCloseFolderModal = () => setShowFolderModal(false);

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
                router.delete(route('proveedor-servicios.destroy', id));
            }
        });
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (filters.tipo) params.append('tipo', filters.tipo);
        if (filters.especialidad) params.append('especialidad', filters.especialidad);
        window.location.href = route('proveedor-servicios.export') + '?' + params.toString();
    };

    const handleExportProject = (id) => {
        window.location.href = route('proveedor-servicios.export-project', id);
    };

    const canEdit = (item) => {
        if (currentUserRole === 'Administrador') return true;
        if (currentUserRole === 'Operador') {
            if (sharedReadOnlyIds.includes(item.id)) return false;
            return item.user_id === auth?.user?.id;
        }
        return false;
    };

    const canShare = (item) => currentUserRole === 'Operador' && item.user_id === auth?.user?.id;

    const openShareModal = (item, e) => {
        if (e) e.stopPropagation();
        setShareItem(item);
        setShareTargetId('');
        setShareExpiresAt('');
        setShareCanEdit(false);
        setShowShareModal(true);
    };

    const handleShareSubmit = (e) => {
        e.preventDefault();
        if (!shareItem || !shareTargetId) return;
        router.post(route('record-share.store'), {
            shareable_type: 'App\\Models\\ProveedorServicio',
            shareable_id: shareItem.id,
            target_user_id: shareTargetId,
            expires_at: shareExpiresAt || null,
            can_edit: shareCanEdit,
        }, { preserveScroll: true, onSuccess: () => { setShowShareModal(false); setShareItem(null); } });
    };

    const canDelete = (item) => {
        if (currentUserRole === 'Administrador') return true;
        if (currentUserRole === 'Operador') {
            return item.user_id === auth?.user?.id;
        }
        return false;
    };

    const openDocumentsModal = (item, e) => {
        if (e) e.stopPropagation();
        const links = getDocumentLinks(item).filter(d => d.path);
        setListDocumentLinks(links);
        setShowDocumentsModal(true);
    };

    const openPdfInModal = (label, path) => {
        setPdfModalTitle(`${breadcrumbTitle} - ${label}`);
        setPdfModalUrl(`/storage/${path}`);
        setShowPdfModal(true);
    };

    const allServicios = servicios.data || [];
    const grouped = groupedByEspecialidad || {};

    return (
        <MainLayout>
            <Head title="Proveedor de Servicios" />

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
                            <Link href={route('proveedor-servicios.index')} className="text-decoration-none"><i className="bi bi-house-door-fill me-1"></i> Proveedor de Servicios</Link>
                        </li>
                        {breadcrumb.map((folder, index) => (
                            <li key={folder.id} className={`breadcrumb-item ${index === breadcrumb.length - 1 ? 'active' : ''}`}>
                                {index === breadcrumb.length - 1 ? folder.name : <Link href={route('proveedor-servicios.index', { folder_id: folder.id })} className="text-decoration-none">{folder.name}</Link>}
                            </li>
                        ))}
                    </ol>
                </nav>
            )}

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
                                indexRoute="proveedor-servicios.index"
                                indexParams={buildIndexParams()}
                                isAdmin={isAdmin}
                                onEdit={(f) => setEditingFolder(f)}
                            />
                        ))}
                    </div>
                )}
            </div>

            <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 className="fw-bold text-body mb-0">Proveedor de Servicios</h2>
                    <p className="text-secondary mb-0">Gestión de servicios públicos y privados</p>
                </div>
                <div className="d-flex gap-2 flex-wrap">
                    {currentUserRole !== 'Visualizador' && (
                        <>
                            <button onClick={handleExport} className="btn btn-success rounded-pill px-4">
                                <i className="bi bi-file-earmark-excel me-2"></i> Exportar Excel
                            </button>
                            <Link href={route('proveedor-servicios.create', currentFolder?.id ? { folder_id: currentFolder.id } : {})} className="btn btn-success shadow-sm rounded-pill px-4">
                                <i className="bi bi-plus-lg me-2"></i> Nuevo Registro
                            </Link>
                        </>
                    )}
                </div>
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
                                placeholder="Buscar por servicio, entidad o especialidad..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </div>
                </div>
            </div>

            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
                <div className="card-header bg-body border-0 py-3 d-flex justify-content-between align-items-center">
                    <h6 className="mb-0 fw-bold">Listado {showGrouped ? 'Agrupado por Especialidad' : 'General'}</h6>
                    <button className="btn btn-sm btn-outline-secondary" onClick={() => setShowGrouped(!showGrouped)}>
                        <i className={`bi bi-${showGrouped ? 'list' : 'grid'} me-1`}></i>
                        {showGrouped ? 'Vista General' : 'Vista Agrupada'}
                    </button>
                </div>
                <div className="table-responsive">
                    <table className="table table-hover align-middle mb-0">
                        <thead className="border-bottom text-secondary small text-uppercase">
                            <tr>
                                <th scope="col" className="ps-4 py-3">SERVICIO</th>
                                <th scope="col" className="py-3">ENTIDAD</th>
                                <th scope="col" className="py-3">ESPECIALIDAD</th>
                                <th scope="col" className="py-3">TIPO</th>
                                <th scope="col" className="py-3">PRESUPUESTO</th>
                                <th scope="col" className="py-3">ESTADO</th>
                                <th scope="col" className="py-3">DURACION</th>
                                <th scope="col" className="text-end pe-4 py-3">ACCIONES</th>
                            </tr>
                        </thead>
                        <tbody>
                            {showGrouped && Object.keys(grouped).length > 0 ? (
                                Object.entries(grouped).map(([especialidad, items]) => (
                                    <React.Fragment key={especialidad}>
                                        <tr className="bg-light">
                                            <td colSpan="8" className="ps-4 py-2 fw-bold text-primary">
                                                <i className="bi bi-folder-fill me-2"></i>
                                                {especialidad || 'Sin Especialidad'} ({items.length} {items.length === 1 ? 'registro' : 'registros'})
                                            </td>
                                        </tr>
                                        {items.map(servicio => (
                                            <React.Fragment key={servicio.id}>
                                                <tr
                                                    className="cursor-pointer"
                                                    onClick={() => setExpandedRow(expandedRow === servicio.id ? null : servicio.id)}
                                                    style={{ backgroundColor: expandedRow === servicio.id ? 'var(--bs-light)' : 'transparent' }}
                                                >
                                                    <td className="ps-4 py-3 fw-medium text-body">{servicio.titulo}</td>
                                                    <td className="text-secondary">{servicio.entidad}</td>
                                                    <td className="text-secondary">{servicio.especialidad || '-'}</td>
                                                    <td className="text-secondary">{servicio.tipo_servicio || '-'}</td>
                                                    <td className="text-secondary fw-bold text-body">
                                                        S/ {parseFloat(servicio.presupuesto || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                    <td>
                                                        <span className={`badge bg-${servicio.estado === 'En Curso' ? 'info' : 'success'}-subtle text-${servicio.estado === 'En Curso' ? 'info-emphasis' : 'success'} border border-${servicio.estado === 'En Curso' ? 'info' : 'success'}-subtle rounded-pill px-3`}>
                                                            {servicio.estado}
                                                        </span>
                                                    </td>
                                                    <td className="text-secondary">{servicio.duracion || '-'}</td>
                                                    <td className="text-end pe-4">
                                                        {getDocumentLinks(servicio).filter(d => d.path).length > 0 && (
                                                            <button
                                                                type="button"
                                                                onClick={(e) => openDocumentsModal(servicio, e)}
                                                                className="btn btn-sm btn-outline-primary me-1"
                                                                title="Ver documentos"
                                                            >
                                                                <i className="bi bi-file-earmark-pdf"></i>
                                                            </button>
                                                        )}
                                                        {canShare(servicio) && (
                                                            <button
                                                                type="button"
                                                                onClick={(e) => openShareModal(servicio, e)}
                                                                className="btn btn-sm btn-outline-info me-1"
                                                                title="Compartir con otro operador"
                                                            >
                                                                <i className="bi bi-share"></i>
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
                                                                        setExpandedRow(expandedRow === servicio.id ? null : servicio.id);
                                                                    }}
                                                                    className="btn btn-sm btn-outline-primary me-1"
                                                                >
                                                                    <i className={`bi bi-chevron-${expandedRow === servicio.id ? 'up' : 'down'}`}></i>
                                                                </button>
                                                                <button
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        handleExportProject(servicio.id);
                                                                    }}
                                                                    className="btn btn-sm btn-outline-success me-1"
                                                                    title="Exportar a Excel"
                                                                >
                                                                    <i className="bi bi-file-earmark-excel"></i>
                                                                </button>
                                                                {canDelete(servicio) && (
                                                                    <button
                                                                        onClick={(e) => {
                                                                            e.stopPropagation();
                                                                            handleDelete(servicio.id);
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
                                                {expandedRow === servicio.id && (
                                                    <tr>
                                                        <td colSpan="8" className="p-0 border-0">
                                                            <div className="p-3 bg-light">
                                                                <DetailForm item={servicio} onClose={() => setExpandedRow(null)} />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                )}
                                            </React.Fragment>
                                        ))}
                                    </React.Fragment>
                                ))
                            ) : allServicios.length > 0 ? (
                                allServicios.map(servicio => (
                                    <React.Fragment key={servicio.id}>
                                        <tr
                                            className="cursor-pointer"
                                            onClick={() => setExpandedRow(expandedRow === servicio.id ? null : servicio.id)}
                                            style={{ backgroundColor: expandedRow === servicio.id ? 'var(--bs-light)' : 'transparent' }}
                                        >
                                            <td className="ps-4 py-3 fw-medium text-body">{servicio.titulo}</td>
                                            <td className="text-secondary">{servicio.entidad}</td>
                                            <td className="text-secondary">{servicio.especialidad || '-'}</td>
                                            <td className="text-secondary">{servicio.tipo_servicio || '-'}</td>
                                            <td className="text-secondary fw-bold text-body">
                                                S/ {parseFloat(servicio.presupuesto || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td>
                                                <span className={`badge bg-${servicio.estado === 'En Curso' ? 'info' : 'success'}-subtle text-${servicio.estado === 'En Curso' ? 'info-emphasis' : 'success'} border border-${servicio.estado === 'En Curso' ? 'info' : 'success'}-subtle rounded-pill px-3`}>
                                                    {servicio.estado}
                                                </span>
                                            </td>
                                            <td className="text-secondary">{servicio.duracion || '-'}</td>
                                            <td className="text-end pe-4">
                                                {getDocumentLinks(servicio).filter(d => d.path).length > 0 && (
                                                    <button
                                                        type="button"
                                                        onClick={(e) => openDocumentsModal(servicio, e)}
                                                        className="btn btn-sm btn-outline-primary me-1"
                                                        title="Ver documentos"
                                                    >
                                                        <i className="bi bi-file-earmark-pdf"></i>
                                                    </button>
                                                )}
                                                {canShare(servicio) && (
                                                    <button
                                                        type="button"
                                                        onClick={(e) => openShareModal(servicio, e)}
                                                        className="btn btn-sm btn-outline-info me-1"
                                                        title="Compartir con otro operador"
                                                    >
                                                        <i className="bi bi-share"></i>
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
                                                                setExpandedRow(expandedRow === servicio.id ? null : servicio.id);
                                                            }}
                                                            className="btn btn-sm btn-outline-primary me-1"
                                                        >
                                                            <i className={`bi bi-chevron-${expandedRow === servicio.id ? 'up' : 'down'}`}></i>
                                                        </button>
                                                        <button
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                handleExportProject(servicio.id);
                                                            }}
                                                            className="btn btn-sm btn-outline-success me-1"
                                                            title="Exportar a Excel"
                                                        >
                                                            <i className="bi bi-file-earmark-excel"></i>
                                                        </button>
                                                        {canDelete(servicio) && (
                                                            <button
                                                                onClick={(e) => {
                                                                    e.stopPropagation();
                                                                    handleDelete(servicio.id);
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
                                        {expandedRow === servicio.id && (
                                            <tr>
                                                <td colSpan="8" className="p-0 border-0">
                                                    <div className="p-3 bg-light">
                                                        <DetailForm item={servicio} onClose={() => setExpandedRow(null)} />
                                                    </div>
                                                </td>
                                            </tr>
                                        )}
                                    </React.Fragment>
                                ))
                            ) : (
                                <tr>
                                    <td colSpan="8" className="text-center py-5 text-muted">No se encontraron registros.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
                {servicios.links && servicios.links.length > 3 && (
                    <div className="card-footer bg-body border-top-0 py-3">
                        <nav aria-label="Page navigation">
                            <ul className="pagination justify-content-center mb-0">
                                {servicios.links.map((link, key) => (
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
                    storeFolderRoute="proveedor-servicios.folders.store"
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

            {showShareModal && shareItem && (
                <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1050 }}>
                    <div className="modal-dialog modal-dialog-centered">
                        <div className="modal-content border-0 shadow-lg rounded-4">
                            <div className="modal-header border-bottom">
                                <h5 className="modal-title fw-bold">Compartir registro</h5>
                                <button type="button" className="btn-close" onClick={() => { setShowShareModal(false); setShareItem(null); }}></button>
                            </div>
                            <form onSubmit={handleShareSubmit}>
                                <div className="modal-body">
                                    <p className="text-secondary small mb-3">El operador seleccionado podrá ver este registro en su listado. Por defecto solo podrá ver (no editar).</p>
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">Compartir con operador</label>
                                        <select className="form-select" value={shareTargetId} onChange={(e) => setShareTargetId(e.target.value)} required>
                                            <option value="">Seleccionar...</option>
                                            {operadores.filter(op => op.id !== auth?.user?.id).map(op => (
                                                <option key={op.id} value={op.id}>{op.name}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="mb-3">
                                        <label className="form-label fw-semibold">Válido hasta (opcional)</label>
                                        <input type="date" className="form-control" value={shareExpiresAt} onChange={(e) => setShareExpiresAt(e.target.value)} min={new Date().toISOString().slice(0, 10)} />
                                        <div className="form-text">Si no se indica, el compartido no expira.</div>
                                    </div>
                                    <div className="form-check">
                                        <input type="checkbox" className="form-check-input" id="shareCanEdit" checked={shareCanEdit} onChange={(e) => setShareCanEdit(e.target.checked)} />
                                        <label className="form-check-label" htmlFor="shareCanEdit">Permitir que edite (por defecto solo puede ver)</label>
                                    </div>
                                </div>
                                <div className="modal-footer border-0">
                                    <button type="button" className="btn btn-secondary" onClick={() => { setShowShareModal(false); setShareItem(null); }}>Cancelar</button>
                                    <button type="submit" className="btn btn-primary">Compartir</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            )}

            <PdfModal show={showPdfModal} onClose={() => { setShowPdfModal(false); setPdfModalUrl(''); setPdfModalTitle(''); }} pdfUrl={pdfModalUrl} title={pdfModalTitle} />
        </MainLayout>
    );
}
