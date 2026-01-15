import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Edit({ contract }) {
    const { data, setData, post, processing, errors } = useForm({
        _method: 'PUT',
        nombre_proyecto: contract.licitacion?.titulo || '',
        cliente: contract.licitacion?.entidad || '',
        monto: contract.licitacion?.presupuesto || '',
        fecha_inicio: contract.fecha_firma || '',
        archivo: null,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('contracts.update', contract.id));
    };

    return (
        <MainLayout>
            <Head title="Editar Contrato" />

            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '800px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Editar Contrato</h3>
                    <p className="text-secondary small">Modificar información del contrato.</p>
                </div>

                <form onSubmit={submit}>
                    <div className="mb-4">
                        <label className="form-label fw-medium text-body">Nombre del Proyecto</label>
                        <input
                            type="text"
                            className={`form-control form-control-lg ${errors.nombre_proyecto ? 'is-invalid' : ''}`}
                            value={data.nombre_proyecto}
                            onChange={e => setData('nombre_proyecto', e.target.value)}
                        />
                        {errors.nombre_proyecto && <div className="invalid-feedback">{errors.nombre_proyecto}</div>}
                    </div>

                    <div className="row g-4">
                        <div className="col-md-6 mb-3">
                            <label className="form-label fw-medium">Cliente</label>
                            <input
                                type="text"
                                className={`form-control ${errors.cliente ? 'is-invalid' : ''}`}
                                value={data.cliente}
                                onChange={e => setData('cliente', e.target.value)}
                            />
                            {errors.cliente && <div className="invalid-feedback">{errors.cliente}</div>}
                        </div>
                        <div className="col-md-6 mb-3">
                            <label className="form-label fw-medium">Monto (PEN - Soles)</label>
                            <div className="input-group">
                                <span className="input-group-text bg-body-tertiary border-end-0 fw-bold text-muted">S/</span>
                                <input
                                    type="number"
                                    className={`form-control border-start-0 ${errors.monto ? 'is-invalid' : ''}`}
                                    step="0.01"
                                    value={data.monto}
                                    onChange={e => setData('monto', e.target.value)}
                                />
                                {errors.monto && <div className="invalid-feedback">{errors.monto}</div>}
                            </div>
                        </div>
                    </div>

                    <div className="row g-4">
                        <div className="col-md-6 mb-3">
                            <label className="form-label fw-medium">Fecha de Inicio</label>
                            <input
                                type="date"
                                className={`form-control ${errors.fecha_inicio ? 'is-invalid' : ''}`}
                                value={data.fecha_inicio}
                                onChange={e => setData('fecha_inicio', e.target.value)}
                            />
                            {errors.fecha_inicio && <div className="invalid-feedback">{errors.fecha_inicio}</div>}
                        </div>
                        <div className="col-md-6 mb-3">
                            <label className="form-label fw-medium">Actualizar Contrato (PDF)</label>
                            <input
                                type="file"
                                className={`form-control ${errors.archivo ? 'is-invalid' : ''}`}
                                accept=".pdf"
                                onChange={e => setData('archivo', e.target.files[0])}
                            />
                            <div className="form-text">Dejar vacío para mantener el archivo actual.</div>
                            {errors.archivo && <div className="invalid-feedback">{errors.archivo}</div>}
                        </div>
                    </div>

                    <div className="d-flex justify-content-end mt-5 pt-3 border-top">
                        <Link href={route('contracts.index')} className="btn btn-outline-secondary me-2">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-arrow-repeat">
                            Actualizar Contrato
                        </SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
