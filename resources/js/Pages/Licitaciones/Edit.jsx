import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Edit({ licitacion }) {
    const { data, setData, put, processing, errors } = useForm({
        titulo: licitacion.titulo || '',
        entidad: licitacion.entidad || '',
        presupuesto: licitacion.presupuesto || '',
        estado: licitacion.estado || 'En Curso',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('licitaciones.update', licitacion.id));
    };

    return (
        <MainLayout>
            <Head title="Editar Licitación" />

            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '800px', margin: '0 auto' }}>
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

                    <div className="row g-4">
                        <div className="col-md-6 mb-3">
                            <label className="form-label fw-medium">Entidad Contratante</label>
                            <input
                                type="text"
                                className={`form-control ${errors.entidad ? 'is-invalid' : ''}`}
                                value={data.entidad}
                                onChange={e => setData('entidad', e.target.value)}
                            />
                            {errors.entidad && <div className="invalid-feedback">{errors.entidad}</div>}
                        </div>
                        <div className="col-md-6 mb-3">
                            <label className="form-label fw-medium">Presupuesto (PEN - Soles)</label>
                            <div className="input-group">
                                <span className="input-group-text bg-body-tertiary border-end-0 fw-bold text-muted">S/</span>
                                <input
                                    type="number"
                                    className={`form-control border-start-0 ${errors.presupuesto ? 'is-invalid' : ''}`}
                                    step="0.01"
                                    value={data.presupuesto}
                                    onChange={e => setData('presupuesto', e.target.value)}
                                />
                                {errors.presupuesto && <div className="invalid-feedback">{errors.presupuesto}</div>}
                            </div>
                        </div>
                    </div>

                    <div className="mb-3">
                        <label className="form-label fw-medium">Estado</label>
                        <select
                            className="form-select"
                            value={data.estado}
                            onChange={e => setData('estado', e.target.value)}
                        >
                            <option value="En Curso">En Curso</option>
                            <option value="Adjudicada">Adjudicada</option>
                            <option value="Cancelada">Cancelada</option>
                            <option value="Finalizada">Finalizada</option>
                        </select>
                        {errors.estado && <div className="invalid-feedback">{errors.estado}</div>}
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
