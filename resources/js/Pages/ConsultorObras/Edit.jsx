import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Edit({ consultorObra }) {
    const existingDocs = consultorObra.documentos || [];
    const { data, setData, put, processing, errors } = useForm({
        titulo: consultorObra.titulo || '',
        entidad: consultorObra.entidad || '',
        categoria: consultorObra.categoria || 'Privada',
        especialidad: consultorObra.especialidad || '',
        tipo_servicio: consultorObra.tipo_servicio || '',
        presupuesto: consultorObra.presupuesto || '',
        estado: consultorObra.estado || 'En Curso',
        duracion: consultorObra.duracion || '',
        modalidad: consultorObra.modalidad || '',
        clasificacion: consultorObra.clasificacion || '',
        documento_delete_ids: [],
        documentos: [],
    });

    const addDocumento = () => setData('documentos', [...data.documentos, { nombre: '', archivo: null }]);

    const removeDocumento = (index) => {
        const list = [...data.documentos];
        list.splice(index, 1);
        setData('documentos', list);
    };

    const removeExistingDoc = (id) => setData('documento_delete_ids', [...(data.documento_delete_ids || []), id]);
    const undoRemoveExisting = (id) => setData('documento_delete_ids', (data.documento_delete_ids || []).filter(x => x !== id));

    const handleDocumentoChange = (index, field, value) => {
        const list = [...data.documentos];
        if (!list[index]) list[index] = { nombre: '', archivo: null };
        list[index][field] = value;
        setData('documentos', list);
    };

    const submit = (e) => {
        e.preventDefault();
        put(route('consultor-obras.update', consultorObra.id), { forceFormData: true });
    };

    const toDelete = data.documento_delete_ids || [];

    return (
        <MainLayout>
            <Head title="Editar Consultoría" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '1000px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Editar Consultoría</h3>
                    <p className="text-secondary small">Modificar detalles del registro.</p>
                </div>

                <form onSubmit={submit}>
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
                            <input type="text" className="form-control" value={data.tipo_servicio} onChange={e => setData('tipo_servicio', e.target.value)} />
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
                            <label className="form-label fw-bold small text-secondary">Tipo / Clasificación</label>
                            <input type="text" className="form-control bg-light" value={data.clasificacion} readOnly />
                        </div>
                    </div>

                    <h5 className="mb-3 fw-bold text-body">Documentación</h5>
                    {existingDocs.length > 0 && (
                        <div className="mb-4">
                            <label className="form-label small text-secondary">Documentos existentes</label>
                            {existingDocs.map((doc) => {
                                const marked = toDelete.includes(doc.id);
                                return (
                                    <div key={doc.id} className={`d-flex align-items-center gap-3 p-3 mb-2 rounded-3 ${marked ? 'bg-danger bg-opacity-10' : 'bg-body-tertiary'}`}>
                                        <span className="fw-medium">{doc.nombre}</span>
                                        <a href={`/storage/${doc.file_path}`} target="_blank" rel="noopener noreferrer" className="small">Ver archivo</a>
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
                    <div className="mb-4">
                        <label className="form-label small text-secondary">Nuevos documentos</label>
                        {(data.documentos || []).map((doc, index) => (
                            <div key={index} className="row g-2 align-items-end mb-2 p-3 bg-body-tertiary rounded-3">
                                <div className="col-md-4">
                                    <input type="text" className="form-control form-control-sm" placeholder="Nombre" value={doc.nombre || ''} onChange={e => handleDocumentoChange(index, 'nombre', e.target.value)} />
                                </div>
                                <div className="col-md-6">
                                    <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx,image/*" onChange={e => handleDocumentoChange(index, 'archivo', e.target.files[0] || null)} />
                                    {doc.archivo && doc.archivo.name && <small className="text-success">{doc.archivo.name}</small>}
                                </div>
                                <div className="col-md-2">
                                    <button type="button" className="btn btn-sm btn-outline-danger w-100" onClick={() => removeDocumento(index)}><i className="bi bi-trash"></i></button>
                                </div>
                            </div>
                        ))}
                        <button type="button" className="btn btn-sm btn-outline-primary rounded-pill" onClick={addDocumento}>
                            <i className="bi bi-plus-lg me-2"></i> Agregar más documento
                        </button>
                    </div>

                    <div className="d-flex justify-content-end mt-5 pt-3 border-top gap-2">
                        <Link href={route('consultor-obras.index')} className="btn btn-outline-secondary">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-save" className="px-5">Actualizar</SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
