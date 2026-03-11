import React, { useState } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Create({ folderId = null, breadcrumbLabel = '' }) {
    const { data, setData, post, processing, errors } = useForm({
        titulo: '',
        entidad: '',
        especialidad: '',
        presupuesto: '',
        estado: 'En Curso',
        clasificacion: breadcrumbLabel || '',
        modalidad: '',
        consorcio: false,
        nombre_rc: '',
        nombre_consorcio: '',
        consorciados: [{ nombre: '', porcentaje: '' }],
        folder_id: folderId || '',
        documentos: [
            { nombre: 'Bases Integradas', archivo: null },
            { nombre: 'Propuesta Económica', archivo: null },
            { nombre: 'Propuesta Técnica', archivo: null },
            { nombre: 'Contrato', archivo: null },
        ],
    });

    // Helper to handle dynamic consorciados
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
        if (list.length === 0) list.push({ nombre: '', archivo: null });
        setData('documentos', list);
    };

    const handleDocumentoChange = (index, field, value) => {
        const list = [...(data.documentos || [])];
        if (!list[index]) list[index] = { nombre: '', archivo: null };
        list[index][field] = value;
        setData('documentos', list);
    };

    const cancelUrl = folderId ? route('licitaciones.index', { folder_id: folderId }) : route('licitaciones.index');

    const submit = (e) => {
        e.preventDefault();
        post(route('licitaciones.store'), { forceFormData: true });
    };

    return (
        <MainLayout>
            <Head title="Nueva Licitación" />

            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body form-card-responsive" style={{ maxWidth: '1000px', margin: '0 auto' }}>
                <form onSubmit={submit} className="p-4 bg-white rounded-4 shadow-sm">
                    <div className="d-flex justify-content-between align-items-center mb-4">
                        <h5 className="fw-bold mb-0">Detalle de Licitación</h5>
                        <Link href={cancelUrl} className="btn-close" aria-label="Cerrar"></Link>
                    </div>

                    {folderId && <input type="hidden" name="folder_id" value={folderId} />}

                    <div className="row g-3 mb-3">
                        <div className="col-md-12">
                            <label className="form-label fw-bold small text-secondary">Licitación</label>
                            <input
                                type="text"
                                className={`form-control ${errors.titulo ? 'is-invalid' : ''}`}
                                value={data.titulo}
                                onChange={e => setData('titulo', e.target.value)}
                            />
                            {errors.titulo && <div className="invalid-feedback">{errors.titulo}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Proyecto</label>
                            <input
                                type="text"
                                className={`form-control ${errors.titulo ? 'is-invalid' : ''}`}
                                value={data.titulo}
                                onChange={e => setData('titulo', e.target.value)}
                            />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Entidad</label>
                            <input
                                type="text"
                                className={`form-control ${errors.entidad ? 'is-invalid' : ''}`}
                                value={data.entidad}
                                onChange={e => setData('entidad', e.target.value)}
                            />
                            {errors.entidad && <div className="invalid-feedback">{errors.entidad}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Presupuesto</label>
                            <div className="input-group">
                                <span className="input-group-text">S/</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    className={`form-control ${errors.presupuesto ? 'is-invalid' : ''}`}
                                    value={data.presupuesto}
                                    onChange={e => setData('presupuesto', e.target.value)}
                                />
                            </div>
                            {errors.presupuesto && <div className="invalid-feedback">{errors.presupuesto}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Modalidad</label>
                            <input
                                type="text"
                                className="form-control"
                                value={data.modalidad}
                                onChange={e => setData('modalidad', e.target.value)}
                            />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Estado</label>
                            <select
                                className="form-select"
                                value={data.estado}
                                onChange={e => setData('estado', e.target.value)}
                            >
                                <option value="Buena Pro">Buena Pro</option>
                                <option value="Nulo">Nulo</option>
                                <option value="Desierto">Desierto</option>
                                <option value="Perdido">Perdido</option>
                                <option value="En Curso">En Curso</option>
                            </select>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Especialidad</label>
                            <input
                                type="text"
                                className="form-control"
                                value={data.especialidad}
                                onChange={e => setData('especialidad', e.target.value)}
                            />
                        </div>
                        <div className="col-md-12">
                            <label className="form-label fw-bold small text-secondary">Tipo / Clasificación</label>
                            <input
                                type="text"
                                className={`form-control bg-light ${errors.clasificacion ? 'is-invalid' : ''}`}
                                placeholder="Ej: PUBLICAS / CONSULTORIAS DE OBRA / PUENTES"
                                value={data.clasificacion || ''}
                                readOnly
                            />
                            {errors.clasificacion && <div className="invalid-feedback">{errors.clasificacion}</div>}
                        </div>
                    </div>

                    <hr className="my-3" />

                    <div className="mb-3">
                        <label className="form-label fw-bold small text-secondary">Documentación del Proceso (nombre + archivo)</label>
                        <p className="text-secondary small mb-2">Cada documento debe tener nombre y archivo. Use &quot;Agregar más documento&quot; para añadir más.</p>
                        {(data.documentos || []).map((doc, index) => (
                            <div key={index} className="row g-2 align-items-end mb-2 p-2 bg-body-tertiary rounded">
                                <div className="col-md-4">
                                    <input
                                        type="text"
                                        className="form-control form-control-sm"
                                        placeholder="Nombre (ej. Bases Integradas, Contrato)"
                                        value={doc.nombre || ''}
                                        onChange={e => handleDocumentoChange(index, 'nombre', e.target.value)}
                                    />
                                </div>
                                <div className="col-md-6">
                                    <input
                                        type="file"
                                        className="form-control form-control-sm"
                                        accept=".pdf,.doc,.docx"
                                        onChange={e => handleDocumentoChange(index, 'archivo', e.target.files[0] || null)}
                                    />
                                    {doc.archivo && typeof doc.archivo === 'object' && doc.archivo.name && (
                                        <small className="text-success">{doc.archivo.name}</small>
                                    )}
                                </div>
                                <div className="col-md-2">
                                    <button type="button" className="btn btn-outline-danger btn-sm w-100" onClick={() => removeDocumento(index)} title="Eliminar">
                                        <i className="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        ))}
                        <button type="button" className="btn btn-sm btn-outline-primary" onClick={addDocumento}>
                            <i className="bi bi-plus-lg me-1"></i> Agregar más documento
                        </button>
                    </div>

                    <div className="form-check form-switch mb-3">
                        <input
                            className="form-check-input"
                            type="checkbox"
                            checked={data.consorcio}
                            onChange={e => setData('consorcio', e.target.checked)}
                        />
                        <label className="form-check-label fw-bold">Consorcio</label>
                    </div>

                    {data.consorcio && (
                        <div className="p-3 bg-light rounded mb-3">
                            <div className="row g-3 mb-3">
                                <div className="col-md-6">
                                    <label className="form-label fw-bold small text-secondary">R.C.</label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={data.nombre_rc}
                                        onChange={e => setData('nombre_rc', e.target.value)}
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label fw-bold small text-secondary">Consorcio</label>
                                    <input
                                        type="text"
                                        className="form-control"
                                        value={data.nombre_consorcio}
                                        onChange={e => setData('nombre_consorcio', e.target.value)}
                                    />
                                </div>
                            </div>
                            <label className="form-label fw-bold small text-secondary">Consorciados</label>
                            {data.consorciados.map((item, index) => (
                                <div key={index} className="d-flex gap-2 mb-2">
                                    <input
                                        type="text"
                                        className="form-control"
                                        placeholder="Nombre"
                                        value={item.nombre}
                                        onChange={e => handleConsorciadoChange(e, index, 'nombre')}
                                    />
                                    <div className="input-group" style={{ width: '150px' }}>
                                        <input
                                            type="number"
                                            className="form-control"
                                            placeholder="%"
                                            value={item.porcentaje}
                                            onChange={e => handleConsorciadoChange(e, index, 'porcentaje')}
                                        />
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
                                <input
                                    type="file"
                                    className="form-control form-control-sm"
                                    accept=".pdf,.doc,.docx"
                                    onChange={e => setData('promesa_consorcio', e.target.files[0] || null)}
                                />
                            </div>
                        </div>
                    )}

                    <div className="d-flex justify-content-end gap-2">
                        <Link href={cancelUrl} className="btn btn-outline-secondary">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-save" className="btn btn-primary">
                            Guardar
                        </SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
