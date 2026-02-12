import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link, router, usePage } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';
import Swal from 'sweetalert2';

const INIT_ARCHIVO = { nombre_archivo: '', file: null };

export default function Edit({ cv, operadores = [] }) {
    const { auth } = usePage().props;
    const isAdmin = auth?.user?.role === 'Administrador';

    const [archivos, setArchivos] = useState([{ ...INIT_ARCHIVO }]);
    const [archivosExistentes, setArchivosExistentes] = useState([]);

    const { data, setData, post, processing, errors } = useForm({
        _method: 'PUT',
        nombre_candidato: cv.nombre_candidato || '',
    });

    useEffect(() => {
        setArchivosExistentes((cv.files || []).map((f) => ({ id: f.id, nombre_archivo: f.nombre_archivo || '' })));
        setArchivos([{ ...INIT_ARCHIVO }]);
    }, [cv.id, cv.files]);

    const addArchivo = () => setArchivos((prev) => [...prev, { ...INIT_ARCHIVO }]);
    const removeArchivo = (index) => setArchivos((prev) => prev.filter((_, i) => i !== index));
    const setArchivoNombre = (index, nombre_archivo) => {
        setArchivos((prev) => prev.map((a, i) => (i === index ? { ...a, nombre_archivo } : a)));
    };
    const setArchivoFile = (index, file) => {
        setArchivos((prev) => prev.map((a, i) => (i === index ? { ...a, file } : a)));
    };
    const setExistentesNombre = (index, nombre_archivo) => {
        setArchivosExistentes((prev) => prev.map((a, i) => (i === index ? { ...a, nombre_archivo } : a)));
    };

    const submit = (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('nombre_candidato', data.nombre_candidato);
        archivosExistentes.forEach((a, i) => {
            formData.append(`archivos_existentes[${i}][id]`, a.id);
            formData.append(`archivos_existentes[${i}][nombre_archivo]`, a.nombre_archivo);
        });
        archivos.forEach((a, i) => {
            if (a.nombre_archivo && a.file) {
                formData.append(`archivos[${i}][nombre_archivo]`, a.nombre_archivo);
                formData.append(`archivos[${i}][file]`, a.file);
            }
        });
        router.post(route('cvs.update', cv.id), formData, {
            forceFormData: true,
            onError: (errs) => {
                const html = Object.entries(errs || {}).map(([k, v]) => `<strong>${k}</strong>: ${v}`).join('<br>');
                Swal.fire({ icon: 'error', title: 'Errores de validación', html, confirmButtonText: 'Entendido' });
            },
        });
    };

    return (
        <MainLayout>
            <Head title="Editar CV" />

            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '1200px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Editar CV</h3>
                    <p className="text-secondary small">Actualizar información del candidato.</p>
                </div>

                <form onSubmit={submit}>
                    <div className="mb-4">
                        {isAdmin && operadores.length > 0 && (
                            <div className="mb-3">
                                <label className="form-label fw-medium text-body">Operador (rellenar nombre del candidato)</label>
                                <select
                                    className="form-select form-select-lg"
                                    value={operadores.find(op => op.name === data.nombre_candidato)?.id || (cv.user_id && operadores.find(o => o.id === cv.user_id)?.id) || ''}
                                    onChange={(e) => {
                                        const id = e.target.value;
                                        const op = operadores.find(o => String(o.id) === id);
                                        if (op) setData('nombre_candidato', op.name);
                                    }}
                                >
                                    <option value="">Seleccionar operador...</option>
                                    {operadores.map((op) => (
                                        <option key={op.id} value={op.id}>{op.name}</option>
                                    ))}
                                </select>
                                <div className="form-text">Al elegir un operador se actualiza el nombre del candidato; puede editarlo abajo si desea.</div>
                            </div>
                        )}
                        <label className="form-label fw-medium text-body">Nombre del candidato</label>
                        <input
                            type="text"
                            readOnly
                            className={`form-control form-control-lg bg-light ${errors.nombre_candidato ? 'is-invalid' : ''}`}
                            placeholder={isAdmin ? "Seleccione un operador arriba" : "Ej. Juan Pérez"}
                            value={data.nombre_candidato}
                            onChange={(e) => setData('nombre_candidato', e.target.value)}
                        />
                        {errors.nombre_candidato && <div className="invalid-feedback">{errors.nombre_candidato}</div>}
                    </div>

                    {archivosExistentes.length > 0 && (
                        <div className="mb-4">
                            <label className="form-label fw-semibold">Archivos actuales (renombrar si desea)</label>
                            <div className="list-group list-group-flush">
                                {archivosExistentes.map((a, i) => (
                                    <div key={a.id} className="list-group-item d-flex align-items-center gap-2">
                                        <i className="bi bi-file-pdf-fill text-danger"></i>
                                        <input
                                            type="text"
                                            className="form-control form-control-sm"
                                            value={a.nombre_archivo}
                                            onChange={(e) => setExistentesNombre(i, e.target.value)}
                                            placeholder="Nombre del archivo"
                                        />
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="col-12 mb-4">
                        <label className="form-label fw-semibold">
                            {archivosExistentes.length > 0 ? 'Añadir más archivos PDF' : 'Archivos PDF (nombre + archivo)'}
                            {archivosExistentes.length === 0 && <span className="text-danger ms-1">* al menos uno</span>}
                        </label>
                        <div className="small text-secondary mb-2">Máx. 10 MB por archivo, formato PDF.</div>
                        {archivos.map((a, i) => (
                            <div key={i} className="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-center mb-2">
                                <input
                                    type="text"
                                    className="form-control"
                                    placeholder="Nombre (ej. Curriculum Vitae, Carta de Presentación, Anexo)"
                                    value={a.nombre_archivo}
                                    onChange={(e) => setArchivoNombre(i, e.target.value)}
                                />
                                <div className="d-flex gap-2 align-items-center">
                                    <input
                                        type="file"
                                        className="form-control flex-grow-1"
                                        accept=".pdf"
                                        onChange={(e) => setArchivoFile(i, e.target.files?.[0] || null)}
                                    />
                                    <button
                                        type="button"
                                        className="btn btn-outline-danger btn-sm flex-shrink-0"
                                        onClick={() => removeArchivo(i)}
                                        title="Quitar"
                                    >
                                        <i className="bi bi-trash"></i>
                                    </button>
                                </div>
                            </div>
                        ))}
                        <button type="button" className="btn btn-outline-primary btn-sm" onClick={addArchivo}>
                            <i className="bi bi-plus me-1"></i> Añadir otro archivo
                        </button>
                        {errors.archivos && <div className="text-danger small mt-1">{errors.archivos}</div>}
                    </div>

                    <div className="d-flex justify-content-end mt-5 pt-3 border-top">
                        <Link href={route('cvs.index', cv.folder_id ? { folder_id: cv.folder_id } : {})} className="btn btn-outline-secondary me-2">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-arrow-repeat">
                            Actualizar CV
                        </SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
