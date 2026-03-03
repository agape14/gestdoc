import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Edit({ item }) {
    const { data, setData, post, processing, errors } = useForm({
        _method: 'PUT',
        titulo: item.titulo || '',
        ubicacion: item.ubicacion || '',
        precio: item.precio || '',
        estado: item.estado || 'Disponible',
        imagen: null,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('inmobiliaria.update', item.id), { forceFormData: true });
    };

    return (
        <MainLayout>
            <Head title="Editar Proyecto Inmobiliario" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '800px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Editar Proyecto Inmobiliario</h3>
                </div>
                <form onSubmit={submit}>
                    <div className="row g-4 mb-4">
                        <div className="col-md-12">
                            <label className="form-label fw-medium">Título</label>
                            <input type="text" className={`form-control ${errors.titulo ? 'is-invalid' : ''}`} value={data.titulo} onChange={e => setData('titulo', e.target.value)} required />
                            {errors.titulo && <div className="invalid-feedback">{errors.titulo}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Ubicación</label>
                            <input type="text" className="form-control" value={data.ubicacion} onChange={e => setData('ubicacion', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Precio (S/)</label>
                            <div className="input-group">
                                <span className="input-group-text">S/</span>
                                <input type="number" step="0.01" className="form-control" value={data.precio} onChange={e => setData('precio', e.target.value)} />
                            </div>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Estado</label>
                            <select className="form-select" value={data.estado} onChange={e => setData('estado', e.target.value)}>
                                <option value="Disponible">Disponible</option>
                                <option value="Vendido">Vendido</option>
                                <option value="Reservado">Reservado</option>
                            </select>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Imagen</label>
                            <input type="file" className="form-control" accept="image/*" onChange={e => setData('imagen', e.target.files[0])} />
                            {item.imagen && (
                                <a href={item.imagen_url || `/storage/${item.imagen}`} target="_blank" className="small text-primary mt-1 d-block">
                                    <i className="bi bi-image"></i> Ver imagen actual
                                </a>
                            )}
                        </div>
                    </div>
                    <div className="d-flex justify-content-end mt-5 pt-3 border-top gap-2">
                        <Link href={route('inmobiliaria.index')} className="btn btn-outline-secondary px-4 rounded-pill">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-save" className="px-5 rounded-pill shadow-sm">Actualizar</SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
