import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Create({ folderId = null, breadcrumbLabel = '' }) {
    const { data, setData, post, processing, errors } = useForm({
        titulo: '',
        descripcion: '',
        archivo: null,
        folder_id: folderId || '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('tecnologia.store'), { forceFormData: true });
    };

    const cancelUrl = folderId ? route('tecnologia.index', { folder_id: folderId }) : route('tecnologia.index');

    return (
        <MainLayout>
            <Head title="Nuevo Registro de Tecnología" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '800px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Nuevo Registro de Tecnología</h3>
                </div>
                <form onSubmit={submit}>
                    <div className="row g-4 mb-4">
                        <div className="col-md-12">
                            <label className="form-label fw-medium">Título</label>
                            <input type="text" className={`form-control ${errors.titulo ? 'is-invalid' : ''}`} value={data.titulo} onChange={e => setData('titulo', e.target.value)} required />
                            {errors.titulo && <div className="invalid-feedback">{errors.titulo}</div>}
                        </div>
                        <div className="col-md-12">
                            <label className="form-label fw-medium">Descripción</label>
                            <textarea className="form-control" rows="4" value={data.descripcion} onChange={e => setData('descripcion', e.target.value)}></textarea>
                        </div>
                        <div className="col-md-12">
                            <label className="form-label fw-medium">Archivo</label>
                            <input type="file" className="form-control" accept=".pdf,.doc,.docx" onChange={e => setData('archivo', e.target.files[0])} />
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
