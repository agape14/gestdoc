import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Edit({ servicio }) {
    const { data, setData, put, processing, errors } = useForm({
        titulo: servicio.titulo || '',
        entidad: servicio.entidad || '',
        especialidad: servicio.especialidad || '',
        tipo_servicio: servicio.tipo_servicio || '',
        presupuesto: servicio.presupuesto || '',
        estado: servicio.estado || 'En Curso',
        modalidad: servicio.modalidad || '',
        duracion: servicio.duracion || '',
        });

    const submit = (e) => {
        e.preventDefault();
        put(route('proveedor-servicios.update', servicio.id), {
            forceFormData: true,
        });
    };

    return (
        <MainLayout>
            <Head title="Editar Proveedor de Servicios" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '800px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Editar Proveedor de Servicios</h3>
                    <p className="text-secondary small">Modificar la información del registro</p>
                </div>
                <form onSubmit={submit}>
                    <div className="row g-4 mb-4">
                        <div className="col-md-12">
                            <label className="form-label fw-medium">Servicio</label>
                            <input type="text" className={`form-control ${errors.titulo ? 'is-invalid' : ''}`} value={data.titulo} onChange={e => setData('titulo', e.target.value)} required />
                            {errors.titulo && <div className="invalid-feedback">{errors.titulo}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Entidad</label>
                            <input type="text" className={`form-control ${errors.entidad ? 'is-invalid' : ''}`} value={data.entidad} onChange={e => setData('entidad', e.target.value)} required />
                            {errors.entidad && <div className="invalid-feedback">{errors.entidad}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Especialidad</label>
                            <input type="text" className="form-control" value={data.especialidad} onChange={e => setData('especialidad', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Tipo de Servicio</label>
                            <input type="text" className="form-control" value={data.tipo_servicio} onChange={e => setData('tipo_servicio', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Presupuesto (S/)</label>
                            <div className="input-group">
                                <span className="input-group-text">S/</span>
                                <input type="number" step="0.01" className="form-control" value={data.presupuesto} onChange={e => setData('presupuesto', e.target.value)} />
                            </div>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Duración</label>
                            <input type="text" className="form-control" value={data.duracion} onChange={e => setData('duracion', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Estado</label>
                            <select className="form-select" value={data.estado} onChange={e => setData('estado', e.target.value)}>
                                <option value="En Curso">En Curso</option>
                                <option value="Finalizado">Finalizado</option>
                                <option value="Cancelado">Cancelado</option>
                            </select>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Modalidad</label>
                            <input type="text" className="form-control" value={data.modalidad} onChange={e => setData('modalidad', e.target.value)} />
                        </div>
                    </div>
                    <div className="d-flex justify-content-end mt-5 pt-3 border-top gap-2">
                        <Link href={route('proveedor-servicios.index')} className="btn btn-outline-secondary px-4 rounded-pill">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-save" className="px-5 rounded-pill shadow-sm">Actualizar</SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
