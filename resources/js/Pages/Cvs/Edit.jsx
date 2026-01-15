import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Edit({ cv }) {
    const { data, setData, post, processing, errors } = useForm({
        _method: 'PUT', // Needed for file upload with PUT in Inertia/Laravel
        nombre_candidato: cv.nombre_candidato || '',
        especialidad: cv.especialidad || '',
        archivo_cv: null,
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('cvs.update', cv.id));
    };

    return (
        <MainLayout>
            <Head title="Editar CV" />

            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '800px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Editar CV</h3>
                    <p className="text-secondary small">Actualizar información del candidato.</p>
                </div>

                <form onSubmit={submit}>
                    <div className="mb-4">
                        <label className="form-label fw-medium text-body">Nombre del Candidato</label>
                        <input
                            type="text"
                            className={`form-control form-control-lg ${errors.nombre_candidato ? 'is-invalid' : ''}`}
                            value={data.nombre_candidato}
                            onChange={e => setData('nombre_candidato', e.target.value)}
                        />
                        {errors.nombre_candidato && <div className="invalid-feedback">{errors.nombre_candidato}</div>}
                    </div>

                    <div className="row g-4">
                        <div className="col-md-6 mb-3">
                            <label className="form-label fw-medium">Especialidad</label>
                            <select
                                className={`form-select ${errors.especialidad ? 'is-invalid' : ''}`}
                                value={data.especialidad}
                                onChange={e => setData('especialidad', e.target.value)}
                            >
                                <option value="">Seleccionar...</option>
                                <option value="Arquitecto">Arquitecto</option>
                                <option value="Ingeniero Civil">Ingeniero Civil</option>
                                <option value="Ingeniero Eléctrico">Ingeniero Eléctrico</option>
                                <option value="Seguridad">Seguridad</option>
                                <option value="Maestro de Obra">Maestro de Obra</option>
                                <option value="Otro">Otro</option>
                            </select>
                            {errors.especialidad && <div className="invalid-feedback">{errors.especialidad}</div>}
                        </div>
                        <div className="col-md-6 mb-3">
                            <label className="form-label fw-medium">Actualizar Archivo CV (PDF)</label>
                            <input
                                type="file"
                                className={`form-control ${errors.archivo_cv ? 'is-invalid' : ''}`}
                                accept=".pdf"
                                onChange={e => setData('archivo_cv', e.target.files[0])}
                            />
                            <div className="form-text">Dejar vacío para mantener el archivo actual.</div>
                            {errors.archivo_cv && <div className="invalid-feedback">{errors.archivo_cv}</div>}
                        </div>
                    </div>

                    <div className="d-flex justify-content-end mt-5 pt-3 border-top">
                        <Link href={route('cvs.index')} className="btn btn-outline-secondary me-2">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-arrow-repeat">
                            Actualizar CV
                        </SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
