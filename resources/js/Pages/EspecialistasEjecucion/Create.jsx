import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Create({ folderId = null, breadcrumbLabel = '' }) {
    const { data, setData, post, processing, errors } = useForm({
        nombre: '',
        especialidad: '',
        tipo: 'Profesional',
        estado: 'Activo',
        documento: null,
        folder_id: folderId || '',
        clasificacion: breadcrumbLabel || '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('especialistas-ejecucion.store'), { forceFormData: true });
    };

    const cancelUrl = folderId ? route('especialistas-ejecucion.index', { folder_id: folderId }) : route('especialistas-ejecucion.index');

    return (
        <MainLayout>
            <Head title="Nuevo Especialista en Ejecución" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '800px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Nuevo Especialista en Ejecución</h3>
                </div>
                <form onSubmit={submit}>
                    <div className="row g-4 mb-4">
                        <div className="col-md-12">
                            <label className="form-label fw-medium">Nombre / Razón Social</label>
                            <input type="text" className={`form-control ${errors.nombre ? 'is-invalid' : ''}`} value={data.nombre} onChange={e => setData('nombre', e.target.value)} required />
                            {errors.nombre && <div className="invalid-feedback">{errors.nombre}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Tipo</label>
                            <select className="form-select" value={data.tipo} onChange={e => setData('tipo', e.target.value)}>
                                <option value="Profesional">Profesional</option>
                                <option value="Empresa">Empresa</option>
                            </select>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Especialidad</label>
                            <input type="text" className="form-control" value={data.especialidad} onChange={e => setData('especialidad', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Estado</label>
                            <select className="form-select" value={data.estado} onChange={e => setData('estado', e.target.value)}>
                                <option value="Activo">Activo</option>
                                <option value="Inactivo">Inactivo</option>
                            </select>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Documento (CV/Brochure)</label>
                            <input type="file" className="form-control" accept=".pdf,.doc,.docx" onChange={e => setData('documento', e.target.files[0])} />
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
