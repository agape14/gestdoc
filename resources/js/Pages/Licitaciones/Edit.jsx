import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Edit({ licitacion }) {
    const existingDocs = licitacion.documentos || [];
    const { data, setData, put, processing, errors } = useForm({
        titulo: licitacion.titulo || '',
        entidad: licitacion.entidad || '',
        presupuesto: licitacion.presupuesto || '',
        estado: licitacion.estado || 'En Curso',
        clasificacion: licitacion.clasificacion || '',
        especialidad: licitacion.especialidad || '',
        modalidad: licitacion.modalidad || '',
        documento_delete_ids: [],
        documentos: [],
    });

    const addDocumento = () => {
        setData('documentos', [...data.documentos, { nombre: '', archivo: null }]);
    };

    const removeDocumento = (index) => {
        const list = [...data.documentos];
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
        const list = [...data.documentos];
        if (!list[index]) list[index] = { nombre: '', archivo: null };
        list[index][field] = value;
        setData('documentos', list);
    };

    const submit = (e) => {
        e.preventDefault();
        put(route('licitaciones.update', licitacion.id), { forceFormData: true });
    };

    const toDelete = data.documento_delete_ids || [];

    return (
        <MainLayout>
            <Head title="Editar Licitación" />

            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '1000px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Editar Licitación</h3>
                    <p className="text-secondary small">Modificar detalles de la licitación.</p>
                </div>

                <form onSubmit={submit}>
                    <div className="mb-4">
                        <label className="form-label fw-medium text-body">Título de Obra / Proyecto</label>
                        <input
                            type="text"
                            className={`form-control form-control-lg ${errors.titulo ? 'is-invalid' : ''}`}
                            value={data.titulo}
                            onChange={e => setData('titulo', e.target.value)}
                        />
                        {errors.titulo && <div className="invalid-feedback">{errors.titulo}</div>}
                    </div>

                    <div className="row g-4 mb-3">
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Entidad Contratante</label>
                            <input
                                type="text"
                                className={`form-control ${errors.entidad ? 'is-invalid' : ''}`}
                                value={data.entidad}
                                onChange={e => setData('entidad', e.target.value)}
                            />
                            {errors.entidad && <div className="invalid-feedback">{errors.entidad}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Presupuesto (S/)</label>
                            <div className="input-group">
                                <span className="input-group-text">S/</span>
                                <input type="number" step="0.01" className="form-control" value={data.presupuesto} onChange={e => setData('presupuesto', e.target.value)} />
                            </div>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Tipo / Clasificación (según carpeta)</label>
                            <input type="text" className="form-control bg-light" value={data.clasificacion} readOnly placeholder="Ej: PUBLICAS / CONSULTORIAS DE OBRA / RIEGO" />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Estado</label>
                            <select className="form-select" value={data.estado} onChange={e => setData('estado', e.target.value)}>
                                <option value="En Curso">En Curso</option>
                                <option value="Buena Pro">Buena Pro</option>
                                <option value="Nulo">Nulo</option>
                                <option value="Desierto">Desierto</option>
                                <option value="Perdido">Perdido</option>
                            </select>
                        </div>
                    </div>

                    <h5 className="mb-3 fw-bold text-body">Documentación del Proceso</h5>
                    {existingDocs.length > 0 && (
                        <div className="mb-4">
                            <label className="form-label small text-secondary">Documentos existentes</label>
                            {existingDocs.map((doc) => {
                                const marked = toDelete.includes(doc.id);
                                return (
                                    <div key={doc.id} className={`d-flex align-items-center gap-3 p-3 mb-2 rounded-3 ${marked ? 'bg-danger bg-opacity-10' : 'bg-body-tertiary'}`}>
                                        <span className="fw-medium">{doc.nombre}</span>
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
                    <div className="mb-4">
                        <label className="form-label small text-secondary">Nuevos documentos</label>
                        {(data.documentos || []).map((doc, index) => (
                            <div key={index} className="row g-2 align-items-end mb-2 p-3 bg-body-tertiary rounded-3">
                                <div className="col-md-4">
                                    <input type="text" className="form-control form-control-sm" placeholder="Nombre del documento" value={doc.nombre || ''} onChange={e => handleDocumentoChange(index, 'nombre', e.target.value)} />
                                </div>
                                <div className="col-md-6">
                                    <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx" onChange={e => handleDocumentoChange(index, 'archivo', e.target.files[0] || null)} />
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

                    <div className="d-flex justify-content-end mt-5 pt-3 border-top">
                        <Link href={route('licitaciones.index')} className="btn btn-outline-secondary me-2">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-arrow-repeat" className="px-5">
                            Actualizar
                        </SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
