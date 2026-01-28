import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';

const DetailForm = ({ item, onClose }) => {
    const { data, setData, post, processing, errors } = useForm({
        _method: 'PUT',
        titulo: item.titulo || '',
        entidad: item.entidad || '',
        modalidad: item.modalidad || '',
        duracion: item.duracion || '',
        especialidad: item.especialidad || '',
        tipo_servicio: item.tipo_servicio || '',
        presupuesto: item.presupuesto || '',
        estado: item.estado || 'En Curso',
        contrato_archivo: null,
        tdr_archivo: null,
        personal_clave: null,
        producto_tecnico: null,
        actas_resoluciones: null,
        conformidad_tecnica: null,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('consultor-obras.update', item.id), {
            forceFormData: true,
            onSuccess: () => {
                Swal.fire('Éxito', 'Registro actualizado correctamente', 'success');
                onClose();
            }
        });
    };

    return (
        <form onSubmit={submit} className="p-4 bg-white rounded-4 shadow-sm">
            <div className="d-flex justify-content-between align-items-center mb-4">
                <h5 className="fw-bold mb-0">Detalle de Consultoría</h5>
                <button type="button" className="btn-close" onClick={onClose}></button>
            </div>

            <div className="row g-3 mb-3">
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Proyecto</label>
                    <input type="text" className="form-control" value={data.titulo} onChange={e => setData('titulo', e.target.value)} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Entidad</label>
                    <input type="text" className="form-control" value={data.entidad} onChange={e => setData('entidad', e.target.value)} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Modalidad</label>
                    <input type="text" className="form-control" value={data.modalidad} onChange={e => setData('modalidad', e.target.value)} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Duración</label>
                    <input type="text" className="form-control" value={data.duracion} onChange={e => setData('duracion', e.target.value)} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Especialidad</label>
                    <input type="text" className="form-control" value={data.especialidad} onChange={e => setData('especialidad', e.target.value)} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Tipo</label>
                    <input type="text" className="form-control" placeholder="Elaboración de expediente técnico, evaluación, liquidación, etc" value={data.tipo_servicio} onChange={e => setData('tipo_servicio', e.target.value)} />
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Presupuesto</label>
                    <div className="input-group">
                        <span className="input-group-text">S/</span>
                        <input type="number" step="0.01" className="form-control" value={data.presupuesto} onChange={e => setData('presupuesto', e.target.value)} />
                    </div>
                </div>
            </div>

            <hr className="my-3" />

            <div className="row g-3 mb-3">
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Contrato</label>
                    <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx" onChange={e => setData('contrato_archivo', e.target.files[0])} />
                    {item.contrato_archivo && (
                        <a href={`/storage/${item.contrato_archivo}`} target="_blank" className="small text-primary mt-1 d-block">
                            <i className="bi bi-file-earmark-pdf"></i> Ver archivo actual
                        </a>
                    )}
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">TDR</label>
                    <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx" onChange={e => setData('tdr_archivo', e.target.files[0])} />
                    {item.tdr_archivo && (
                        <a href={`/storage/${item.tdr_archivo}`} target="_blank" className="small text-primary mt-1 d-block">
                            <i className="bi bi-file-earmark-pdf"></i> Ver archivo actual
                        </a>
                    )}
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Personal Clave</label>
                    <input type="file" className="form-control form-control-sm" accept="image/*" onChange={e => setData('personal_clave', e.target.files[0])} />
                    {item.personal_clave && (
                        <a href={`/storage/${item.personal_clave}`} target="_blank" className="small text-primary mt-1 d-block">
                            <i className="bi bi-image"></i> Ver imagen actual
                        </a>
                    )}
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Producto Técnico</label>
                    <input type="file" className="form-control form-control-sm" multiple accept=".pdf,.doc,.docx" onChange={e => setData('producto_tecnico', Array.from(e.target.files))} />
                    {item.producto_tecnico && Array.isArray(item.producto_tecnico) && item.producto_tecnico.length > 0 && (
                        <div className="small mt-1">
                            {item.producto_tecnico.map((file, idx) => (
                                <a key={idx} href={`/storage/${file}`} target="_blank" className="d-block text-primary">
                                    <i className="bi bi-file-earmark-pdf"></i> Archivo {idx + 1}
                                </a>
                            ))}
                        </div>
                    )}
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Actas y Resoluciones</label>
                    <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx" onChange={e => setData('actas_resoluciones', e.target.files[0])} />
                    {item.actas_resoluciones && (
                        <a href={`/storage/${item.actas_resoluciones}`} target="_blank" className="small text-primary mt-1 d-block">
                            <i className="bi bi-file-earmark-pdf"></i> Ver archivo actual
                        </a>
                    )}
                </div>
                <div className="col-md-6">
                    <label className="form-label fw-bold small text-secondary">Conformidad Técnica</label>
                    <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx" onChange={e => setData('conformidad_tecnica', e.target.files[0])} />
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
                <div className="d-flex gap-2">
                    <button type="button" className="btn btn-outline-secondary" onClick={onClose}>Cancelar</button>
                    <button type="submit" disabled={processing} className="btn btn-primary">
                        <i className="bi bi-save me-2"></i> Guardar
                    </button>
                </div>
            </div>
        </form>
    );
};

export default function Index({ consultorias, groupedByEspecialidad, filters, flash, userRole }) {
    const { auth } = usePage().props;
    const currentUserRole = userRole || auth?.user?.role || 'Visualizador';
    const [search, setSearch] = useState(filters.search || '');
    const [expandedRow, setExpandedRow] = useState(null);
    const [showGrouped, setShowGrouped] = useState(true);

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

    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== (filters.search || '')) {
                router.get(route('consultor-obras.index'), { ...filters, search }, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search]);

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
                router.delete(route('consultor-obras.destroy', id));
            }
        });
    };

    const handleExport = () => {
        const params = new URLSearchParams();
        if (filters.tipo) params.append('tipo', filters.tipo);
        if (filters.especialidad) params.append('especialidad', filters.especialidad);
        window.location.href = route('consultor-obras.export') + '?' + params.toString();
    };

    const handleExportProject = (id) => {
        window.location.href = route('consultor-obras.export-project', id);
    };

    const handleCreate = () => {
        router.post(route('consultor-obras.store'), {
            titulo: 'Nuevo Proyecto',
            entidad: 'Sin Entidad',
            categoria: filters.tipo || 'Privada'
        });
    };

    const allConsultorias = consultorias.data || [];
    const grouped = groupedByEspecialidad || {};

    return (
        <MainLayout>
            <Head title="Consultor de Obras" />

            {flash?.success && (
                <div className="alert alert-success alert-dismissible fade show" role="alert">
                    {flash.success}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            )}

            <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 className="fw-bold text-body mb-0">Consultor de Obras {filters.tipo ? `(${filters.tipo}s)` : ''}</h2>
                    <p className="text-secondary mb-0">Gestión de consultorías de obras públicas y privadas</p>
                </div>
                <div className="d-flex gap-2 flex-wrap">
                    <Link href={route('consultor-obras.index', { tipo: 'Publica' })} className={`btn ${filters.tipo === 'Publica' ? 'btn-primary' : 'btn-outline-primary'} rounded-pill px-4`}>
                        <i className="bi bi-building me-2"></i> PÚBLICAS
                    </Link>
                    <Link href={route('consultor-obras.index', { tipo: 'Privada' })} className={`btn ${filters.tipo === 'Privada' ? 'btn-primary' : 'btn-outline-primary'} rounded-pill px-4`}>
                        <i className="bi bi-shield-lock me-2"></i> PRIVADAS
                    </Link>
                    {currentUserRole !== 'Visualizador' && (
                        <>
                            <button onClick={handleExport} className="btn btn-success rounded-pill px-4">
                                <i className="bi bi-file-earmark-excel me-2"></i> Exportar Excel
                            </button>
                            <button onClick={handleCreate} className="btn btn-success shadow-sm rounded-pill px-4">
                                <i className="bi bi-plus-lg me-2"></i> Nuevo Registro
                            </button>
                        </>
                    )}
                </div>
            </div>

            <div className="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-body">
                <div className="input-group">
                    <span className="input-group-text bg-body-tertiary border-end-0 rounded-start-pill ps-3"><i className="bi bi-search text-secondary"></i></span>
                    <input
                        type="text"
                        className="form-control border-start-0 bg-body-tertiary rounded-end-pill"
                        placeholder="Buscar por proyecto, entidad o especialidad..."
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
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
                                <th scope="col" className="ps-4 py-3">PROYECTO</th>
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
                                        {items.map(consultoria => (
                                            <React.Fragment key={consultoria.id}>
                                                <tr
                                                    className="cursor-pointer"
                                                    onClick={() => setExpandedRow(expandedRow === consultoria.id ? null : consultoria.id)}
                                                    style={{ backgroundColor: expandedRow === consultoria.id ? 'var(--bs-light)' : 'transparent' }}
                                                >
                                                    <td className="ps-4 py-3 fw-medium text-body">{consultoria.titulo}</td>
                                                    <td className="text-secondary">{consultoria.entidad}</td>
                                                    <td className="text-secondary">{consultoria.especialidad || '-'}</td>
                                                    <td className="text-secondary">{consultoria.tipo_servicio || '-'}</td>
                                                    <td className="text-secondary fw-bold text-body">
                                                        S/ {parseFloat(consultoria.presupuesto || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                                    </td>
                                                    <td>
                                                        <span className={`badge bg-${consultoria.estado === 'En Curso' ? 'warning' : 'success'}-subtle text-${consultoria.estado === 'En Curso' ? 'warning-emphasis' : 'success'} border border-${consultoria.estado === 'En Curso' ? 'warning' : 'success'}-subtle rounded-pill px-3`}>
                                                            {consultoria.estado}
                                                        </span>
                                                    </td>
                                                    <td className="text-secondary">{consultoria.duracion || '-'}</td>
                                                    <td className="text-end pe-4">
                                                        {currentUserRole === 'Visualizador' ? (
                                                            <button className="btn btn-sm btn-outline-info" title="Ver">
                                                                <i className="bi bi-eye"></i>
                                                            </button>
                                                        ) : (
                                                            <>
                                                                <button
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        setExpandedRow(expandedRow === consultoria.id ? null : consultoria.id);
                                                                    }}
                                                                    className="btn btn-sm btn-outline-primary me-1"
                                                                >
                                                                    <i className={`bi bi-chevron-${expandedRow === consultoria.id ? 'up' : 'down'}`}></i>
                                                                </button>
                                                                <button
                                                                    onClick={(e) => {
                                                                        e.stopPropagation();
                                                                        handleExportProject(consultoria.id);
                                                                    }}
                                                                    className="btn btn-sm btn-outline-success me-1"
                                                                    title="Exportar a Excel"
                                                                >
                                                                    <i className="bi bi-file-earmark-excel"></i>
                                                                </button>
                                                                {canDelete(consultoria) && (
                                                                    <button
                                                                        onClick={(e) => {
                                                                            e.stopPropagation();
                                                                            handleDelete(consultoria.id);
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
                                                {expandedRow === consultoria.id && (
                                                    <tr>
                                                        <td colSpan="8" className="p-0 border-0">
                                                            <div className="p-3 bg-light">
                                                                <DetailForm item={consultoria} onClose={() => setExpandedRow(null)} />
                                                            </div>
                                                        </td>
                                                    </tr>
                                                )}
                                            </React.Fragment>
                                        ))}
                                    </React.Fragment>
                                ))
                            ) : allConsultorias.length > 0 ? (
                                allConsultorias.map(consultoria => (
                                    <React.Fragment key={consultoria.id}>
                                        <tr
                                            className="cursor-pointer"
                                            onClick={() => setExpandedRow(expandedRow === consultoria.id ? null : consultoria.id)}
                                            style={{ backgroundColor: expandedRow === consultoria.id ? 'var(--bs-light)' : 'transparent' }}
                                        >
                                            <td className="ps-4 py-3 fw-medium text-body">{consultoria.titulo}</td>
                                            <td className="text-secondary">{consultoria.entidad}</td>
                                            <td className="text-secondary">{consultoria.especialidad || '-'}</td>
                                            <td className="text-secondary">{consultoria.tipo_servicio || '-'}</td>
                                            <td className="text-secondary fw-bold text-body">
                                                S/ {parseFloat(consultoria.presupuesto || 0).toLocaleString('es-PE', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}
                                            </td>
                                            <td>
                                                <span className={`badge bg-${consultoria.estado === 'En Curso' ? 'warning' : 'success'}-subtle text-${consultoria.estado === 'En Curso' ? 'warning-emphasis' : 'success'} border border-${consultoria.estado === 'En Curso' ? 'warning' : 'success'}-subtle rounded-pill px-3`}>
                                                    {consultoria.estado}
                                                </span>
                                            </td>
                                            <td className="text-secondary">{consultoria.duracion || '-'}</td>
                                            <td className="text-end pe-4">
                                                {currentUserRole === 'Visualizador' ? (
                                                    <button className="btn btn-sm btn-outline-info" title="Ver">
                                                        <i className="bi bi-eye"></i>
                                                    </button>
                                                ) : (
                                                    <>
                                                        <button
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                setExpandedRow(expandedRow === consultoria.id ? null : consultoria.id);
                                                            }}
                                                            className="btn btn-sm btn-outline-primary me-1"
                                                        >
                                                            <i className={`bi bi-chevron-${expandedRow === consultoria.id ? 'up' : 'down'}`}></i>
                                                        </button>
                                                        <button
                                                            onClick={(e) => {
                                                                e.stopPropagation();
                                                                handleExportProject(consultoria.id);
                                                            }}
                                                            className="btn btn-sm btn-outline-success me-1"
                                                            title="Exportar a Excel"
                                                        >
                                                            <i className="bi bi-file-earmark-excel"></i>
                                                        </button>
                                                        {canDelete(consultoria) && (
                                                            <button
                                                                onClick={(e) => {
                                                                    e.stopPropagation();
                                                                    handleDelete(consultoria.id);
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
                                        {expandedRow === consultoria.id && (
                                            <tr>
                                                <td colSpan="8" className="p-0 border-0">
                                                    <div className="p-3 bg-light">
                                                        <DetailForm item={consultoria} onClose={() => setExpandedRow(null)} />
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
                {consultorias.links && consultorias.links.length > 3 && (
                    <div className="card-footer bg-body border-top-0 py-3">
                        <nav aria-label="Page navigation">
                            <ul className="pagination justify-content-center mb-0">
                                {consultorias.links.map((link, key) => (
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
