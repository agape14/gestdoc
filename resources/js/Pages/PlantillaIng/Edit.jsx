import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Edit({ item }) {
    const { data, setData, post, processing, errors } = useForm({
        _method: 'PUT',
        titulo: item.titulo || '',
        especialidad: item.especialidad || '',
        archivo: null,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('plantillas-ing.update', item.id), { forceFormData: true });
    };

    return (
        <MainLayout>
            <Head title="Editar Plantilla de Ingeniería" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body form-card-responsive" style={{ maxWidth: '800px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Editar Plantilla de Ingeniería</h3>
                </div>
                <form onSubmit={submit}>
                    <div className="row g-4 mb-4">
                        <div className="col-md-12">
                            <label className="form-label fw-medium">Título</label>
                            <input type="text" className={`form-control ${errors.titulo ? 'is-invalid' : ''}`} value={data.titulo} onChange={e => setData('titulo', e.target.value)} required />
                            {errors.titulo && <div className="invalid-feedback">{errors.titulo}</div>}
                        </div>
                        <div className="col-md-12">
                            <label className="form-label fw-medium">Especialidad</label>
                            <input type="text" className="form-control" value={data.especialidad} onChange={e => setData('especialidad', e.target.value)} />
                        </div>
                        <div className="col-md-12">
                            <label className="form-label fw-medium">Archivo</label>
                            <input type="file" className="form-control" accept=".pdf,.doc,.docx" onChange={e => setData('archivo', e.target.files[0])} />
                            {item.archivo && (
                                <a href={item.archivo_url || `/storage/${item.archivo}`} target="_blank" className="small text-primary mt-1 d-block">
                                    <i className="bi bi-file-earmark-pdf"></i> Ver archivo actual
                                </a>
                            )}
                        </div>
                    </div>
                    <div className="d-flex justify-content-end mt-5 pt-3 border-top gap-2">
                        <Link href={route('plantillas-ing.index')} className="btn btn-outline-secondary px-4 rounded-pill">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-save" className="px-5 rounded-pill shadow-sm">Actualizar</SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
