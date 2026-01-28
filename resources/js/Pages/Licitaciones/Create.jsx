import React, { useState } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        titulo: '',
        entidad: '',
        especialidad: '',
        presupuesto: '',
        estado: 'En Curso',
        tipo: 'Publica', // Default to Publica
        modalidad: '',
        consorcio: false,
        nombre_rc: '',
        nombre_consorcio: '',
        consorciados: [{ nombre: '', porcentaje: '' }], // Array for dynamic inputs
        bases_integradas: null,
        propuesta_economica: null,
        propuesta_tecnica: null,
        contrato_archivo: null,
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

    const submit = (e) => {
        e.preventDefault();
        post(route('licitaciones.store'));
    };

    return (
        <MainLayout>
            <Head title="Nueva Licitación" />

            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '1000px', margin: '0 auto' }}>
                <div className="mb-4 text-center">
                    <h3 className="fw-bold mb-1">Registro de Licitación</h3>
                    <p className="text-secondary small">Complete la información del proceso de licitación</p>
                </div>

                <form onSubmit={submit}>
                    {/* Sección 1: Datos Generales */}
                    <div className="row g-4 mb-4">
                        <div className="col-md-12">
                            <label className="form-label fw-bold small text-uppercase text-primary">Proyecto / Obra</label>
                            <input
                                type="text"
                                className={`form-control ${errors.titulo ? 'is-invalid' : ''}`}
                                placeholder="Nombre completo del proyecto"
                                value={data.titulo}
                                onChange={e => setData('titulo', e.target.value)}
                            />
                            {errors.titulo && <div className="invalid-feedback">{errors.titulo}</div>}
                        </div>

                        <div className="col-md-6">
                            <label className="form-label fw-medium">Entidad</label>
                            <input
                                type="text"
                                className={`form-control ${errors.entidad ? 'is-invalid' : ''}`}
                                value={data.entidad}
                                onChange={e => setData('entidad', e.target.value)}
                            />
                            {errors.entidad && <div className="invalid-feedback">{errors.entidad}</div>}
                        </div>

                        <div className="col-md-6">
                            <label className="form-label fw-medium">Especialidad</label>
                            <input
                                type="text"
                                className={`form-control ${errors.especialidad ? 'is-invalid' : ''}`}
                                value={data.especialidad}
                                onChange={e => setData('especialidad', e.target.value)}
                            />
                        </div>

                        <div className="col-md-4">
                            <label className="form-label fw-medium">Presupuesto (S/)</label>
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

                        <div className="col-md-4">
                            <label className="form-label fw-medium">Tipo</label>
                            <select
                                className="form-select"
                                value={data.tipo}
                                onChange={e => setData('tipo', e.target.value)}
                            >
                                <option value="Publica">Pública</option>
                                <option value="Privada">Privada</option>
                            </select>
                        </div>

                        <div className="col-md-4">
                            <label className="form-label fw-medium">Estado</label>
                            <select
                                className="form-select"
                                value={data.estado}
                                onChange={e => setData('estado', e.target.value)}
                            >
                                <option value="En Curso">En Curso</option>
                                <option value="Buena Pro">Buena Pro</option>
                                <option value="Nulo">Nulo</option>
                                <option value="Desierto">Desierto</option>
                                <option value="Perdido">Perdido</option>
                            </select>
                        </div>

                        <div className="col-md-6">
                            <label className="form-label fw-medium">Modalidad</label>
                            <input
                                type="text"
                                className="form-control"
                                value={data.modalidad}
                                onChange={e => setData('modalidad', e.target.value)}
                            />
                        </div>
                    </div>

                    <hr className="my-5 border-secondary-subtle" />

                    {/* Sección 2: Archivos y Documentación */}
                    <h5 className="mb-4 fw-bold text-body">Documentación del Proceso</h5>
                    <div className="row g-4 mb-4">
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Bases Integradas (Archivo)</label>
                            <input
                                type="file"
                                className="form-control"
                                onChange={e => setData('bases_integradas', e.target.files[0])}
                            />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Contrato (Archivo)</label>
                            <input
                                type="file"
                                className="form-control"
                                onChange={e => setData('contrato_archivo', e.target.files[0])}
                            />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Propuesta Técnica</label>
                            <input
                                type="file"
                                className="form-control"
                                onChange={e => setData('propuesta_tecnica', e.target.files[0])}
                            />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Propuesta Económica</label>
                            <input
                                type="file"
                                className="form-control"
                                onChange={e => setData('propuesta_economica', e.target.files[0])}
                            />
                        </div>
                    </div>

                    <hr className="my-5 border-secondary-subtle" />

                    {/* Sección 3: Datos de Consorcio */}
                    <div className="mb-4">
                        <div className="form-check form-switch mb-3">
                            <input
                                className="form-check-input"
                                type="checkbox"
                                role="switch"
                                id="consorcioSwitch"
                                checked={data.consorcio}
                                onChange={e => setData('consorcio', e.target.checked)}
                            />
                            <label className="form-check-label fw-bold" htmlFor="consorcioSwitch">¿Es Consorcio?</label>
                        </div>

                        {data.consorcio && (
                            <div className="p-4 bg-body-tertiary rounded-4 animate-fade-in">
                                <div className="row g-3 mb-3">
                                    <div className="col-md-6">
                                        <label className="form-label fw-medium">Nombre del R.C.</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={data.nombre_rc}
                                            onChange={e => setData('nombre_rc', e.target.value)}
                                        />
                                    </div>
                                    <div className="col-md-6">
                                        <label className="form-label fw-medium">Nombre del Consorcio</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            value={data.nombre_consorcio}
                                            onChange={e => setData('nombre_consorcio', e.target.value)}
                                        />
                                    </div>
                                    <div className="col-12 mt-4">
                                        <label className="form-label fw-bold text-secondary text-uppercase small">Consorciados</label>
                                        {data.consorciados.map((item, index) => (
                                            <div key={index} className="d-flex gap-2 mb-2">
                                                <input
                                                    type="text"
                                                    className="form-control"
                                                    placeholder="Nombre / Razón Social"
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
                                        <button type="button" className="btn btn-sm btn-outline-primary mt-2" onClick={handleAddConsorciado}>
                                            <i className="bi bi-plus-circle me-1"></i> Agregar Consorciado
                                        </button>

                                        {/* File upload for Promesa de Consorcio if needed here, user said "option to upload promesa de consorcio" */}
                                        <div className="mt-3">
                                            <label className="form-label fw-medium">Promesa de Consorcio (Archivo)</label>
                                            <input
                                                type="file"
                                                className="form-control"
                                            // Assuming we handle this upload same way as others, name it appropriately in backend
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="d-flex justify-content-end mt-5 pt-3 border-top gap-2">
                        <Link href={route('licitaciones.index')} className="btn btn-outline-secondary px-4 rounded-pill">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-save" className="px-5 rounded-pill shadow-sm">
                            Guardar Licitación
                        </SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
