import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Create({ folderId = null, breadcrumbLabel = '' }) {
    const { data, setData, post, processing, errors } = useForm({
        titulo: '',
        entidad: '',
        categoria: 'Privada',
        especialidad: '',
        tipo_servicio: '',
        presupuesto: '',
        estado: 'En Curso',
        duracion: '',
        modalidad: '',
        clasificacion: breadcrumbLabel || '',
        folder_id: folderId || '',
        documentos: [
            { nombre: 'Contrato', archivo: null },
            { nombre: 'TDR', archivo: null },
        ],
    });

    const addDocumento = () => {
        setData('documentos', [...(data.documentos || []), { nombre: '', archivo: null }]);
    };

    const removeDocumento = (index) => {
        const list = data.documentos ? [...data.documentos] : [];
        list.splice(index, 1);
        if (list.length === 0) list.push({ nombre: '', archivo: null });
        setData('documentos', list);
    };

    const handleDocumentoChange = (index, field, value) => {
        const list = [...(data.documentos || [])];
        if (!list[index]) list[index] = { nombre: '', archivo: null };
        list[index][field] = value;
        setData('documentos', list);
    };

    const cancelUrl = folderId ? route('consultor-obras.index', { folder_id: folderId }) : route('consultor-obras.index');

    const submit = (e) => {
        e.preventDefault();
        post(route('consultor-obras.store'), { forceFormData: true });
    };

    return (
        <MainLayout>
            <Head title="Nuevo Registro - Consultor de Obras" />

            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '1000px', margin: '0 auto' }}>
                <form onSubmit={submit} className="p-4 bg-white rounded-4 shadow-sm">
                    <div className="d-flex justify-content-between align-items-center mb-4">
                        <h5 className="fw-bold mb-0">Detalle de Consultoría</h5>
                        <Link href={cancelUrl} className="btn-close" aria-label="Cerrar"></Link>
                    </div>

                    {folderId && <input type="hidden" name="folder_id" value={folderId} />}

                    <div className="row g-3 mb-3">
                        <div className="col-md-12">
                            <label className="form-label fw-bold small text-secondary">Proyecto / Título</label>
                            <input type="text" className={`form-control ${errors.titulo ? 'is-invalid' : ''}`} value={data.titulo} onChange={e => setData('titulo', e.target.value)} />
                            {errors.titulo && <div className="invalid-feedback">{errors.titulo}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Entidad</label>
                            <input type="text" className={`form-control ${errors.entidad ? 'is-invalid' : ''}`} value={data.entidad} onChange={e => setData('entidad', e.target.value)} />
                            {errors.entidad && <div className="invalid-feedback">{errors.entidad}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Categoría</label>
                            <select className="form-select" value={data.categoria} onChange={e => setData('categoria', e.target.value)}>
                                <option value="Publica">Pública</option>
                                <option value="Privada">Privada</option>
                            </select>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Especialidad</label>
                            <input type="text" className="form-control" value={data.especialidad} onChange={e => setData('especialidad', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Tipo de servicio</label>
                            <input type="text" className="form-control" placeholder="Elaboración de expediente técnico, evaluación, etc." value={data.tipo_servicio} onChange={e => setData('tipo_servicio', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Presupuesto (S/)</label>
                            <div className="input-group">
                                <span className="input-group-text">S/</span>
                                <input type="number" step="0.01" className="form-control" value={data.presupuesto} onChange={e => setData('presupuesto', e.target.value)} />
                            </div>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Estado</label>
                            <select className="form-select" value={data.estado} onChange={e => setData('estado', e.target.value)}>
                                <option value="En Curso">En Curso</option>
                                <option value="Finalizado">Finalizado</option>
                            </select>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Duración</label>
                            <input type="text" className="form-control" value={data.duracion} onChange={e => setData('duracion', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Modalidad</label>
                            <input type="text" className="form-control" value={data.modalidad} onChange={e => setData('modalidad', e.target.value)} />
                        </div>
                        <div className="col-md-12">
                            <label className="form-label fw-bold small text-secondary">Tipo / Clasificación (miga de pan)</label>
                            <input type="text" className="form-control bg-light" placeholder="Se rellena con la ruta de la carpeta" value={data.clasificacion || ''} readOnly />
                        </div>
                    </div>

                    <hr className="my-3" />

                    <div className="mb-3">
                        <label className="form-label fw-bold small text-secondary">Documentación (nombre + archivo)</label>
                        <p className="text-secondary small mb-2">Cada documento con nombre y archivo. Use &quot;Agregar más documento&quot; para añadir.</p>
                        {(data.documentos || []).map((doc, index) => (
                            <div key={index} className="row g-2 align-items-end mb-2 p-2 bg-body-tertiary rounded">
                                <div className="col-md-4">
                                    <input type="text" className="form-control form-control-sm" placeholder="Nombre" value={doc.nombre || ''} onChange={e => handleDocumentoChange(index, 'nombre', e.target.value)} />
                                </div>
                                <div className="col-md-6">
                                    <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx,image/*" onChange={e => handleDocumentoChange(index, 'archivo', e.target.files[0] || null)} />
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

                    <div className="d-flex justify-content-end gap-2">
                        <Link href={cancelUrl} className="btn btn-outline-secondary">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-save" className="btn btn-primary">Guardar</SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
