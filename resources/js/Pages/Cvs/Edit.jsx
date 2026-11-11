import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link, router, usePage } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';
import CertificadoPrestacionServicios from '@/Components/CertificadoPrestacionServicios';
import Swal from 'sweetalert2';

const CERTIFICADO_TITULO = 'CERTIFICADO DE PRESTACIÓN DE SERVICIOS';
const INIT_PERIODO = { tipo: 'valorizacion', numero: '1', fecha_inicio: '', fecha_fin: '', dias: '' };
const INIT_ARCHIVO = { nombre_archivo: '', file: null, es_certificado: false, periodos: [] };
const newArchivo = () => ({ ...INIT_ARCHIVO, periodos: [] });

const normalizeDate = (value) => {
    if (!value) return '';
    const raw = String(value);
    if (/^\d{4}-\d{2}-\d{2}/.test(raw)) return raw.slice(0, 10);
    const date = new Date(raw);
    if (Number.isNaN(date.getTime())) return '';
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, '0');
    const d = String(date.getDate()).padStart(2, '0');
    return `${y}-${m}-${d}`;
};

export default function Edit({ cv, operadores = [] }) {
    const { auth } = usePage().props;
    const isAdmin = auth?.user?.role === 'Administrador';

    const [archivos, setArchivos] = useState([newArchivo()]);
    const [archivosExistentes, setArchivosExistentes] = useState([]);

    const { data, setData, post, processing, errors } = useForm({
        _method: 'PUT',
        nombre_candidato: cv.nombre_candidato || '',
        nombre: cv.nombre || '',
    });

    useEffect(() => {
        setArchivosExistentes((cv.files || []).map((f) => ({
            id: f.id,
            nombre_archivo: f.nombre_archivo || '',
            es_certificado: Boolean(f.es_certificado),
            periodos: (f.periodos || []).map((p) => ({
                tipo: p.tipo || 'valorizacion',
                numero: p.numero ?? '',
                fecha_inicio: normalizeDate(p.fecha_inicio),
                fecha_fin: normalizeDate(p.fecha_fin),
                dias: p.dias ?? '',
            })),
        })));
        setArchivos([newArchivo()]);
    }, [cv.id, cv.files]);

    const addArchivo = () => setArchivos((prev) => [...prev, newArchivo()]);
    const removeArchivo = (index) => setArchivos((prev) => prev.filter((_, i) => i !== index));
    const setArchivoNombre = (index, nombre_archivo) => {
        setArchivos((prev) => prev.map((a, i) => (i === index ? { ...a, nombre_archivo } : a)));
    };
    const setArchivoFile = (index, file) => {
        setArchivos((prev) => prev.map((a, i) => (i === index ? { ...a, file } : a)));
    };
    const setArchivoPeriodos = (index, periodos) => {
        setArchivos((prev) => prev.map((a, i) => (i === index ? { ...a, periodos } : a)));
    };
    const toggleCertificado = (index, checked) => {
        setArchivos((prev) => prev.map((a, i) => {
            if (i !== index) return a;
            if (checked) {
                return {
                    ...a,
                    es_certificado: true,
                    nombre_archivo: CERTIFICADO_TITULO,
                    periodos: a.periodos?.length ? a.periodos : [{ ...INIT_PERIODO }],
                };
            }
            return {
                ...a,
                es_certificado: false,
                nombre_archivo: a.nombre_archivo === CERTIFICADO_TITULO ? '' : a.nombre_archivo,
                periodos: [],
            };
        }));
    };
    const setExistentesNombre = (index, nombre_archivo) => {
        setArchivosExistentes((prev) => prev.map((a, i) => (i === index ? { ...a, nombre_archivo } : a)));
    };
    const setExistentesPeriodos = (index, periodos) => {
        setArchivosExistentes((prev) => prev.map((a, i) => (i === index ? { ...a, periodos } : a)));
    };
    const toggleExistentesCertificado = (index, checked) => {
        setArchivosExistentes((prev) => prev.map((a, i) => {
            if (i !== index) return a;
            if (checked) {
                return {
                    ...a,
                    es_certificado: true,
                    nombre_archivo: CERTIFICADO_TITULO,
                    periodos: a.periodos?.length ? a.periodos : [{ ...INIT_PERIODO }],
                };
            }
            return {
                ...a,
                es_certificado: false,
                nombre_archivo: a.nombre_archivo === CERTIFICADO_TITULO ? '' : a.nombre_archivo,
                periodos: [],
            };
        }));
    };

    const appendPeriodos = (formData, prefix, periodos) => {
        (periodos || []).forEach((p, j) => {
            formData.append(`${prefix}[periodos][${j}][tipo]`, p.tipo || 'valorizacion');
            formData.append(`${prefix}[periodos][${j}][numero]`, p.numero ?? '');
            formData.append(`${prefix}[periodos][${j}][fecha_inicio]`, p.fecha_inicio || '');
            formData.append(`${prefix}[periodos][${j}][fecha_fin]`, p.fecha_fin || '');
            formData.append(`${prefix}[periodos][${j}][dias]`, p.dias ?? '');
        });
    };

    const submit = (e) => {
        e.preventDefault();
        if (archivos.some((a) => a.es_certificado && !a.file)) {
            Swal.fire({ icon: 'warning', title: 'Falta el PDF', text: 'Adjunte el archivo PDF del certificado de prestación de servicios.', confirmButtonText: 'Entendido' });
            return;
        }
        const formData = new FormData();
        formData.append('_method', 'PUT');
        formData.append('nombre_candidato', data.nombre_candidato);
        if (data.nombre != null && data.nombre !== '') formData.append('nombre', data.nombre);
        archivosExistentes.forEach((a, i) => {
            const prefix = `archivos_existentes[${i}]`;
            formData.append(`${prefix}[id]`, a.id);
            formData.append(`${prefix}[nombre_archivo]`, a.nombre_archivo);
            formData.append(`${prefix}[es_certificado]`, a.es_certificado ? '1' : '0');
            if (a.es_certificado) {
                appendPeriodos(formData, prefix, a.periodos);
            }
        });
        archivos.forEach((a, i) => {
            if (a.nombre_archivo && a.file) {
                const prefix = `archivos[${i}]`;
                formData.append(`${prefix}[nombre_archivo]`, a.nombre_archivo);
                formData.append(`${prefix}[file]`, a.file);
                if (a.es_certificado) {
                    formData.append(`${prefix}[es_certificado]`, '1');
                    appendPeriodos(formData, prefix, a.periodos);
                }
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

            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body form-card-responsive" style={{ maxWidth: '1200px', margin: '0 auto' }}>
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
                        <div className="mt-3">
                            <label className="form-label fw-medium text-body">NOMBRE (opcional)</label>
                            <input type="text" className="form-control" placeholder="Texto libre para el listado" value={data.nombre || ''} onChange={(e) => setData('nombre', e.target.value)} />
                        </div>
                    </div>

                    {archivosExistentes.length > 0 && (
                        <div className="mb-4">
                            <label className="form-label fw-semibold">Archivos actuales (renombrar si desea)</label>
                            <div className="list-group list-group-flush">
                                {archivosExistentes.map((a, i) => (
                                    <div key={a.id} className="list-group-item px-0">
                                        <div className="d-flex align-items-center gap-2">
                                            <i className="bi bi-file-pdf-fill text-danger"></i>
                                            <input
                                                type="text"
                                                className="form-control form-control-sm"
                                                value={a.nombre_archivo}
                                                onChange={(e) => setExistentesNombre(i, e.target.value)}
                                                placeholder="Nombre del archivo"
                                            />
                                        </div>

                                        <div className="form-check mt-2">
                                            <input
                                                type="checkbox"
                                                className="form-check-input"
                                                id={`certificado-existente-${a.id}`}
                                                checked={Boolean(a.es_certificado)}
                                                onChange={(e) => toggleExistentesCertificado(i, e.target.checked)}
                                            />
                                            <label className="form-check-label fw-semibold" htmlFor={`certificado-existente-${a.id}`}>
                                                CERTIFICADO DE PRESTACIÓN DE SERVICIOS
                                            </label>
                                            <div className="form-text">
                                                Al marcarlo, el título del archivo se reemplaza por «{CERTIFICADO_TITULO}» y podrá registrar los periodos de valorización y suspensión. El título seguirá siendo editable.
                                            </div>
                                        </div>

                                        {a.es_certificado && (
                                            <CertificadoPrestacionServicios
                                                periodos={a.periodos || []}
                                                onChange={(periodos) => setExistentesPeriodos(i, periodos)}
                                            />
                                        )}
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
                        <div className="small text-secondary mb-2">Máx. 25 MB por archivo, formato PDF.</div>
                        {archivos.map((a, i) => (
                            <div key={i} className="border rounded-3 p-2 mb-2 bg-body">
                                <div className="d-flex flex-column flex-md-row gap-2 align-items-stretch align-items-md-center">
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
                                            accept={a.es_certificado ? '.pdf,application/pdf' : '.pdf,.doc,.docx,.xls,.xlsx,.dwg,.zip,.rar,.png,.jpg,.jpeg'}
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

                                <div className="form-check mt-2">
                                    <input
                                        type="checkbox"
                                        className="form-check-input"
                                        id={`certificado-nuevo-${i}`}
                                        checked={Boolean(a.es_certificado)}
                                        onChange={(e) => toggleCertificado(i, e.target.checked)}
                                    />
                                    <label className="form-check-label fw-semibold" htmlFor={`certificado-nuevo-${i}`}>
                                        CERTIFICADO DE PRESTACIÓN DE SERVICIOS
                                    </label>
                                    <div className="form-text">
                                        Al marcarlo, el título del archivo se reemplaza por «{CERTIFICADO_TITULO}» y podrá registrar los periodos de valorización y suspensión. El título seguirá siendo editable.
                                    </div>
                                </div>

                                {a.es_certificado && (
                                    <CertificadoPrestacionServicios
                                        periodos={a.periodos || []}
                                        onChange={(periodos) => setArchivoPeriodos(i, periodos)}
                                    />
                                )}
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
