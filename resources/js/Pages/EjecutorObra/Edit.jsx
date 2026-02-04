import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Edit({ obra }) {
    const { data, setData, put, processing, errors } = useForm({
        titulo: obra.titulo || '',
        entidad: obra.entidad || '',
        especialidad: obra.especialidad || '',
        tipo_obra: obra.tipo_obra || '',
        presupuesto: obra.presupuesto || '',
        estado: obra.estado || 'En Curso',
        modalidad: obra.modalidad || '',
        categoria: obra.categoria || 'Publica',
        clasificacion: obra.clasificacion || '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('ejecutor-obra.update', obra.id), {
            forceFormData: true,
        });
    };

    return (
        <MainLayout>
            <Head title="Editar Ejecutor de Obra" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '800px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Editar Ejecutor de Obra</h3>
                    <p className="text-secondary small">Modificar la información del registro</p>
                </div>
                <form onSubmit={submit}>
                    <div className="row g-4 mb-4">
                        <div className="col-md-12">
                            <label className="form-label fw-medium">Proyecto</label>
                            <input type="text" className={`form-control ${errors.titulo ? 'is-invalid' : ''}`} value={data.titulo} onChange={e => setData('titulo', e.target.value)} required />
                            {errors.titulo && <div className="invalid-feedback">{errors.titulo}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Entidad</label>
                            <input type="text" className={`form-control ${errors.entidad ? 'is-invalid' : ''}`} value={data.entidad} onChange={e => setData('entidad', e.target.value)} required />
                            {errors.entidad && <div className="invalid-feedback">{errors.entidad}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Categoría</label>
                            <select className="form-select" value={data.categoria} onChange={e => setData('categoria', e.target.value)}>
                                <option value="Publica">Pública</option>
                                <option value="Privada">Privada</option>
                            </select>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Especialidad</label>
                            <input type="text" className="form-control" value={data.especialidad} onChange={e => setData('especialidad', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Tipo de Obra</label>
                            <input type="text" className="form-control" value={data.tipo_obra} onChange={e => setData('tipo_obra', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Presupuesto (S/)</label>
                            <div className="input-group">
                                <span className="input-group-text">S/</span>
                                <input type="number" step="0.01" className="form-control" value={data.presupuesto} onChange={e => setData('presupuesto', e.target.value)} />
                            </div>
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
                        <div className="col-md-12">
                            <label className="form-label fw-medium">Tipo / Clasificación</label>
                            <input type="text" className="form-control bg-light" value={data.clasificacion} readOnly />
                        </div>
                    </div>
                    <div className="d-flex justify-content-end mt-5 pt-3 border-top gap-2">
                        <Link href={route('ejecutor-obra.index')} className="btn btn-outline-secondary px-4 rounded-pill">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-save" className="px-5 rounded-pill shadow-sm">Actualizar</SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
