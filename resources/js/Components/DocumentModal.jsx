import React, { useEffect, useState } from 'react';
import { useForm, router } from '@inertiajs/react';
import Swal from 'sweetalert2';

const humanizeValidationMessage = (msg) => {
    if (typeof msg !== 'string') return msg;
    if (msg === 'validation.uploaded') {
        return 'La subida falló antes de validar el PDF (tamaño del archivo o límites del servidor: PHP upload_max_filesize / post_max_size o nginx client_max_body_size). Si el archivo es grande, pida al administrador subir esos límites (p. ej. 32M) o comprima el PDF.';
    }
    return msg;
};

const showValidationErrors = (errors) => {
    if (!errors || typeof errors !== 'object') return;
    const entries = Object.entries(errors);
    if (entries.length === 0) return;
    const html = entries
        .map(([k, v]) => {
            const text = Array.isArray(v) ? v.map(humanizeValidationMessage).join(', ') : humanizeValidationMessage(v);
            return `<strong>${k.replace(/_/g, ' ')}</strong>: ${text}`;
        })
        .join('<br>');
    Swal.fire({
        icon: 'error',
        title: 'Errores de validación',
        html,
        confirmButtonText: 'Entendido',
    });
};

const INIT_ARCHIVO = { nombre_archivo: '', file: null };

export default function DocumentModal({ show, onClose, document: doc = null, folderId = null }) {
    const isEditing = !!doc;
    const [archivos, setArchivos] = useState([{ ...INIT_ARCHIVO }]);
    const [archivosExistentes, setArchivosExistentes] = useState([]);
    const [submitting, setSubmitting] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        folder_id: folderId || '',
        numero: '',
        fecha_documento: '',
        asunto: '',
        remitente: '',
        destinatario: '',
        referencia: '',
        observaciones: '',
        folios: '',
    });

    const fechaStr = (d) => {
        if (!d) return '';
        if (typeof d === 'string') return d;
        return d.date || d || '';
    };

    useEffect(() => {
        if (doc) {
            setData({
                numero: doc.numero || '',
                fecha_documento: fechaStr(doc.fecha_documento),
                asunto: doc.asunto || '',
                remitente: doc.remitente || '',
                destinatario: doc.destinatario || '',
                referencia: doc.referencia || '',
                observaciones: doc.observaciones || '',
                folios: doc.folios != null ? String(doc.folios) : '',
            });
            setArchivosExistentes((doc.files || []).map((f) => ({ id: f.id, nombre_archivo: f.nombre_archivo || '' })));
            setArchivos([{ ...INIT_ARCHIVO }]);
        } else {
            reset();
            setData({ ...data, folder_id: folderId || '' });
            setArchivos([{ ...INIT_ARCHIVO }]);
            setArchivosExistentes([]);
        }
    }, [doc, folderId, show]);

    const addArchivo = () => {
        setArchivos((prev) => [...prev, { ...INIT_ARCHIVO }]);
    };

    const removeArchivo = (index) => {
        setArchivos((prev) => prev.filter((_, i) => i !== index));
    };

    const setArchivoNombre = (index, nombre_archivo) => {
        setArchivos((prev) => prev.map((a, i) => (i === index ? { ...a, nombre_archivo } : a)));
    };

    const setArchivoFile = (index, file) => {
        setArchivos((prev) => prev.map((a, i) => (i === index ? { ...a, file } : a)));
    };

    const setExistentesNombre = (index, nombre_archivo) => {
        setArchivosExistentes((prev) => prev.map((a, i) => (i === index ? { ...a, nombre_archivo } : a)));
    };

    const handleSubmit = (e) => {
        e.preventDefault();

        const formData = new FormData();
        if (!isEditing) {
            formData.append('folder_id', data.folder_id);
        }
        formData.append('numero', data.numero);
        formData.append('fecha_documento', data.fecha_documento);
        formData.append('asunto', data.asunto);
        formData.append('remitente', data.remitente);
        formData.append('destinatario', data.destinatario);
        formData.append('referencia', data.referencia);
        formData.append('observaciones', data.observaciones);
        formData.append('folios', data.folios);

        if (isEditing) {
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
            formData.append('_method', 'PUT');
            router.post(route('folders.documents.update', doc.id), formData, {
                preserveScroll: true,
                forceFormData: true,
                onSuccess: () => {
                    onClose();
                    reset();
                },
                onError: (errors) => showValidationErrors(errors),
            });
        } else {
            const conArchivo = archivos.filter((a) => a.nombre_archivo && a.file);
            conArchivo.forEach((a, i) => {
                formData.append(`archivos[${i}][nombre_archivo]`, a.nombre_archivo);
                formData.append(`archivos[${i}][file]`, a.file);
            });
            router.post(route('folders.documents.store', data.folder_id), formData, {
                preserveScroll: true,
                forceFormData: true,
                onSuccess: () => {
                    onClose();
                    reset();
                },
                onError: (errors) => showValidationErrors(errors),
            });
        }
    };

    if (!show) return null;

    return (
        <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
            <div className="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                <div className="modal-content border-0 shadow-lg rounded-4">
                    <div className="modal-header border-0 pb-0">
                        <h5 className="modal-title fw-bold">
                            {isEditing ? 'Editar Documento' : 'Nuevo Documento'}
                        </h5>
                        <button type="button" className="btn-close" onClick={onClose}></button>
                    </div>
                    <form onSubmit={handleSubmit}>
                        <div className="modal-body overflow-y-auto" style={{ maxHeight: '65vh' }}>
                            <div className="row g-3">
                                <div className="col-md-8">
                                    <label className="form-label fw-semibold">Número</label>
                                    <input
                                        type="text"
                                        className={`form-control ${errors.numero ? 'is-invalid' : ''}`}
                                        value={data.numero}
                                        onChange={(e) => setData('numero', e.target.value)}
                                        placeholder="Ej. 001-2025"
                                    />
                                    {errors.numero && <div className="invalid-feedback">{errors.numero}</div>}
                                </div>
                                <div className="col-md-2">
                                    <label className="form-label fw-semibold">Fecha del documento</label>
                                    <input
                                        type="date"
                                        className={`form-control ${errors.fecha_documento ? 'is-invalid' : ''}`}
                                        value={data.fecha_documento}
                                        onChange={(e) => setData('fecha_documento', e.target.value)}
                                    />
                                    {errors.fecha_documento && <div className="invalid-feedback">{errors.fecha_documento}</div>}
                                </div>
                                <div className="col-md-2">
                                    <label className="form-label fw-semibold">Folios <span className="text-danger">*</span></label>
                                    <input
                                        type="number"
                                        min="0"
                                        step="1"
                                        className={`form-control ${errors.folios ? 'is-invalid' : ''}`}
                                        value={data.folios}
                                        onChange={(e) => setData('folios', e.target.value)}
                                        placeholder="Cantidad de folios"
                                    />
                                    {errors.folios && <div className="invalid-feedback">{errors.folios}</div>}
                                </div>
                                <div className="col-12">
                                    <label className="form-label fw-semibold">Asunto</label>
                                    <textarea
                                        className={`form-control ${errors.asunto ? 'is-invalid' : ''}`}
                                        rows={3}
                                        value={data.asunto}
                                        onChange={(e) => setData('asunto', e.target.value)}
                                        placeholder="Asunto o resumen"
                                    />
                                    {errors.asunto && <div className="invalid-feedback">{errors.asunto}</div>}
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label fw-semibold">Remitente</label>
                                    <input
                                        type="text"
                                        className={`form-control ${errors.remitente ? 'is-invalid' : ''}`}
                                        value={data.remitente}
                                        onChange={(e) => setData('remitente', e.target.value)}
                                    />
                                    {errors.remitente && <div className="invalid-feedback">{errors.remitente}</div>}
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label fw-semibold">Destinatario</label>
                                    <input
                                        type="text"
                                        className={`form-control ${errors.destinatario ? 'is-invalid' : ''}`}
                                        value={data.destinatario}
                                        onChange={(e) => setData('destinatario', e.target.value)}
                                    />
                                    {errors.destinatario && <div className="invalid-feedback">{errors.destinatario}</div>}
                                </div>
                                <div className="col-12">
                                    <label className="form-label fw-semibold">Referencia</label>
                                    <textarea
                                        className={`form-control ${errors.referencia ? 'is-invalid' : ''}`}
                                        rows={2}
                                        value={data.referencia}
                                        onChange={(e) => setData('referencia', e.target.value)}
                                    />
                                    {errors.referencia && <div className="invalid-feedback">{errors.referencia}</div>}
                                </div>
                                <div className="col-12">
                                    <label className="form-label fw-semibold">Observaciones</label>
                                    <textarea
                                        className={`form-control ${errors.observaciones ? 'is-invalid' : ''}`}
                                        rows="2"
                                        value={data.observaciones}
                                        onChange={(e) => setData('observaciones', e.target.value)}
                                    />
                                    {errors.observaciones && <div className="invalid-feedback">{errors.observaciones}</div>}
                                </div>

                                {/* Archivos existentes (solo edición) */}
                                {isEditing && archivosExistentes.length > 0 && (
                                    <div className="col-12">
                                        <label className="form-label fw-semibold">Archivos actuales (renombrar o eliminar)</label>
                                        <div className="list-group list-group-flush">
                                            {archivosExistentes.map((a, i) => (
                                                <div key={a.id} className="list-group-item d-flex align-items-center gap-2 flex-wrap">
                                                    <i className="bi bi-file-pdf-fill text-danger"></i>
                                                    <input
                                                        type="text"
                                                        className="form-control form-control-sm flex-grow-1"
                                                        value={a.nombre_archivo}
                                                        onChange={(e) => setExistentesNombre(i, e.target.value)}
                                                        placeholder="Nombre del archivo (solo nombre, no extensión)"
                                                    />
                                                    <button
                                                        type="button"
                                                        className="btn btn-outline-danger btn-sm"
                                                        title="Eliminar archivo"
                                                        onClick={() => {
                                                            if (window.confirm('¿Eliminar este archivo del expediente? No se borrará del almacenamiento.')) {
                                                                router.delete(route('folders.documents.files.destroy', { document: doc.id, file: a.id }), {
                                                                    preserveScroll: true,
                                                                    onSuccess: () => setArchivosExistentes((prev) => prev.filter((x) => x.id !== a.id)),
                                                                });
                                                            }
                                                        }}
                                                    >
                                                        <i className="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                )}

                                {/* Nuevos archivos */}
                                <div className="col-12">
                                    <label className="form-label fw-semibold">
                                        {isEditing ? 'Añadir más archivos PDF' : 'Archivos PDF (nombre + archivo)'}
                                        {!isEditing && <span className="text-secondary ms-1">(opcional)</span>}
                                    </label>
                                    <div className="small text-secondary mb-2">Máx. 25 MB por archivo, formato PDF.</div>
                                    {archivos.map((a, i) => (
                                        <div key={i} className="d-flex gap-2 align-items-center mb-2">
                                            <input
                                                type="text"
                                                className="form-control"
                                                placeholder="Nombre (ej. Carta 001, Anexo)"
                                                value={a.nombre_archivo}
                                                onChange={(e) => setArchivoNombre(i, e.target.value)}
                                            />
                                            <input
                                                type="file"
                                                className="form-control"
                                                accept=".pdf"
                                                onChange={(e) => setArchivoFile(i, e.target.files?.[0] || null)}
                                            />
                                            <button
                                                type="button"
                                                className="btn btn-outline-danger btn-sm"
                                                onClick={() => removeArchivo(i)}
                                                title="Quitar"
                                            >
                                                <i className="bi bi-trash"></i>
                                            </button>
                                        </div>
                                    ))}
                                    <button type="button" className="btn btn-outline-primary btn-sm" onClick={addArchivo}>
                                        <i className="bi bi-plus me-1"></i> Añadir otro archivo
                                    </button>
                                    {errors.archivos && (
                                        <div className="text-danger small mt-1">{errors.archivos}</div>
                                    )}
                                </div>
                            </div>
                        </div>
                        <div className="modal-footer border-0 pt-0">
                            <button type="button" className="btn btn-secondary" onClick={onClose}>
                                Cancelar
                            </button>
                            <button
                                type="submit"
                                className="btn btn-primary"
                                disabled={processing || submitting}
                            >
                                {(processing || submitting) ? (
                                    <>
                                        <span className="spinner-border spinner-border-sm me-2"></span>
                                        Guardando...
                                    </>
                                ) : (
                                    isEditing ? 'Actualizar' : 'Guardar documento'
                                )}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
