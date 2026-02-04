import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Create({ folderId = null, breadcrumbLabel = '' }) {
    const { data, setData, post, processing, errors } = useForm({
        titulo: '',
        entidad: '',
        estado: 'En Stock',
        costo: '',
        folder_id: folderId || '',
        clasificacion: breadcrumbLabel || '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('proveedor-bienes.store'), {
            forceFormData: true,
        });
    };

    const cancelUrl = folderId ? route('proveedor-bienes.index', { folder_id: folderId }) : route('proveedor-bienes.index');

    return (
        <MainLayout>
            <Head title="Nuevo Proveedor de Bienes" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '800px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Nuevo Proveedor de Bienes</h3>
                    <p className="text-secondary small">Complete la información básica</p>
                </div>
                <form onSubmit={submit}>
                    <div className="row g-4 mb-4">
                        <div className="col-md-12">
                            <label className="form-label fw-medium">Item / Bien</label>
                            <input type="text" className={`form-control ${errors.titulo ? 'is-invalid' : ''}`} value={data.titulo} onChange={e => setData('titulo', e.target.value)} required />
                            {errors.titulo && <div className="invalid-feedback">{errors.titulo}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Entidad</label>
                            <input type="text" className="form-control" value={data.entidad} onChange={e => setData('entidad', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Costo (S/)</label>
                            <div className="input-group">
                                <span className="input-group-text">S/</span>
                                <input type="number" step="0.01" className="form-control" value={data.costo} onChange={e => setData('costo', e.target.value)} />
                            </div>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Estado</label>
                            <select className="form-select" value={data.estado} onChange={e => setData('estado', e.target.value)}>
                                <option value="En Stock">En Stock</option>
                                <option value="Agotado">Agotado</option>
                                <option value="Reservado">Reservado</option>
                            </select>
                        </div>
                    </div>
                    <div className="d-flex justify-content-end mt-5 pt-3 border-top gap-2">
                        <Link href={cancelUrl} className="btn btn-outline-secondary px-4 rounded-pill">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-save" className="px-5 rounded-pill shadow-sm">Guardar</SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
