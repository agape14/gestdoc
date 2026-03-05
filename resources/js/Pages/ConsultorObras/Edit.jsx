import React, { useState } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link, router } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';
import Swal from 'sweetalert2';

export default function Edit({ consultorObra, folderId = null, canDelete = false }) {
    const existingDocs = consultorObra.documentos || [];
    const fmtDate = (d) => (!d ? '' : (typeof d === 'string' && d.length >= 10 ? d.substring(0, 10) : d));
    const [sending, setSending] = useState(false);

    const { data, setData, errors } = useForm({
        titulo: consultorObra.titulo || '',
        entidad: consultorObra.entidad || '',
        categoria: consultorObra.categoria || 'Privada',
        especialidad: consultorObra.especialidad || '',
        tipo_servicio: consultorObra.tipo_servicio || '',
        presupuesto: consultorObra.presupuesto || '',
        estado: consultorObra.estado || 'En Curso',
        duracion: consultorObra.duracion || '',
        modalidad: consultorObra.modalidad || '',
        clasificacion: consultorObra.clasificacion || '',
        objeto_contrato: consultorObra.objeto_contrato || '',
        cui: consultorObra.cui || '',
        numero_contrato_os_comprobante: consultorObra.numero_contrato_os_comprobante || '',
        fecha_contrato_cp: fmtDate(consultorObra.fecha_contrato_cp),
        fecha_conformidad: fmtDate(consultorObra.fecha_conformidad),
        experiencia_proveniente_de: consultorObra.experiencia_proveniente_de || '',
        moneda: consultorObra.moneda || 'Soles',
        monto_contratado: consultorObra.monto_contratado ?? '',
        consorciado: !!consultorObra.consorciado,
        porcentaje_participacion: consultorObra.porcentaje_participacion ?? '',
        importe: consultorObra.importe ?? '',
        tipo_cambio_venta: consultorObra.tipo_cambio_venta ?? '',
        monto_facturado_acumulado: consultorObra.monto_facturado_acumulado ?? '',
        numero_resolucion: consultorObra.numero_resolucion || '',
        fecha_aprobacion: fmtDate(consultorObra.fecha_aprobacion),
        documento_delete_ids: [],
        documentos: [],
    });

    const addDocumento = () => setData('documentos', [...data.documentos, { nombre: '', archivo: null }]);

    const removeDocumento = (index) => {
        const list = [...data.documentos];
        list.splice(index, 1);
        setData('documentos', list);
    };

    const removeExistingDoc = (id) => setData('documento_delete_ids', [...(data.documento_delete_ids || []), id]);
    const undoRemoveExisting = (id) => setData('documento_delete_ids', (data.documento_delete_ids || []).filter(x => x !== id));

    const handleDocumentoChange = (index, field, value) => {
        const list = [...data.documentos];
        if (!list[index]) list[index] = { nombre: '', archivo: null };
        list[index][field] = value;
        setData('documentos', list);
    };

    const submit = (e) => {
        e.preventDefault();
        const id = consultorObra?.id ?? consultorObra?.consultor_obra_id;
        if (!id) {
            console.error('Edit: falta id del registro');
            return;
        }
        setSending(true);
        const formData = new FormData();
        const scalarKeys = [
            'titulo', 'entidad', 'categoria', 'especialidad', 'tipo_servicio', 'presupuesto', 'estado',
            'duracion', 'modalidad', 'clasificacion', 'objeto_contrato', 'cui', 'numero_contrato_os_comprobante',
            'fecha_contrato_cp', 'fecha_conformidad', 'experiencia_proveniente_de', 'moneda', 'monto_contratado',
            'consorciado', 'porcentaje_participacion', 'importe', 'tipo_cambio_venta', 'monto_facturado_acumulado',
            'numero_resolucion', 'fecha_aprobacion',
        ];
        scalarKeys.forEach((key) => {
            const val = data[key];
            if (val !== undefined && val !== null)
                formData.append(key, typeof val === 'boolean' ? (val ? '1' : '0') : String(val));
        });
        (data.documento_delete_ids || []).forEach((docId, i) => formData.append(`documento_delete_ids[${i}]`, docId));
        (data.documentos || []).forEach((doc, i) => {
            if (doc.archivo && (doc.nombre || doc.archivo.name)) {
                formData.append(`documentos[${i}][nombre]`, doc.nombre || doc.archivo.name || '');
                formData.append(`documentos[${i}][archivo]`, doc.archivo);
            }
        });
        router.post(route('consultor-obras.update.post', id), formData, {
            forceFormData: true,
            onFinish: () => setSending(false),
        });
    };

    const toDelete = data.documento_delete_ids || [];

    return (
        <MainLayout>
            <Head title="Editar Consultoría" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '1000px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Editar Consultoría</h3>
                    <p className="text-secondary small">Modificar detalles del registro.</p>
                </div>

                <form onSubmit={submit}>
                    <div className="row g-3 mb-3">
                        <div className="col-md-12">
                            <label className="form-label fw-bold small text-secondary">Proyecto / Título</label>
                            <textarea className={`form-control ${errors.titulo ? 'is-invalid' : ''}`} rows={3} value={data.titulo} onChange={e => setData('titulo', e.target.value)} />
                            {errors.titulo && <div className="invalid-feedback">{errors.titulo}</div>}
                        </div>
                        <div className="col-md-12">
                            <label className="form-label fw-bold small text-secondary">CLIENTE</label>
                            <input type="text" className={`form-control ${errors.entidad ? 'is-invalid' : ''}`} value={data.entidad} onChange={e => setData('entidad', e.target.value)} />
                            {errors.entidad && <div className="invalid-feedback">{errors.entidad}</div>}
                        </div>
                        <div className="col-md-12">
                            <label className="form-label fw-bold small text-secondary">OBJETO DE CONTRATO</label>
                            <textarea className="form-control" rows={2} value={data.objeto_contrato || ''} onChange={e => setData('objeto_contrato', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">CUI</label>
                            <input type="text" className="form-control" value={data.cui || ''} onChange={e => setData('cui', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">N° CONTRATO / O/S / COMPROBANTE DE PAGO</label>
                            <input type="text" className="form-control" value={data.numero_contrato_os_comprobante || ''} onChange={e => setData('numero_contrato_os_comprobante', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">FECHA DE CONTRATO O CP</label>
                            <input type="date" className="form-control" value={data.fecha_contrato_cp || ''} onChange={e => setData('fecha_contrato_cp', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">FECHA DE LA CONFORMIDAD DE SER EL CASO</label>
                            <input type="date" className="form-control" value={data.fecha_conformidad || ''} onChange={e => setData('fecha_conformidad', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">MONEDA</label>
                            <select className="form-select" value={data.moneda || 'Soles'} onChange={e => setData('moneda', e.target.value)}>
                                <option value="Soles">Soles</option>
                                <option value="Dólares">Dólares</option>
                            </select>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">CONSORCIADO</label>
                            <select className="form-select" value={data.consorciado ? '1' : '0'} onChange={e => setData('consorciado', e.target.value === '1')}>
                                <option value="0">No</option>
                                <option value="1">Sí</option>
                            </select>
                        </div>
                        {data.consorciado && (
                            <>
                                <div className="col-md-12">
                                    <label className="form-label fw-bold small text-secondary">EXPERIENCIA PROVENIENTE DE</label>
                                    <input type="text" className="form-control" value={data.experiencia_proveniente_de || ''} onChange={e => setData('experiencia_proveniente_de', e.target.value)} />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label fw-bold small text-secondary">MONTO CONTRATADO</label>
                                    <div className="input-group">
                                        <span className="input-group-text">{data.moneda === 'Dólares' ? 'US$' : 'S/'}</span>
                                        <input type="number" step="0.01" className="form-control bg-light" value={data.monto_contratado ?? ''} readOnly />
                                    </div>
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label fw-bold small text-secondary">% DE PARTICIPACIÓN</label>
                                    <div className="input-group">
                                        <input type="number" step="0.01" min="0" max="100" className="form-control" value={data.porcentaje_participacion ?? ''} onChange={e => {
                                            const val = e.target.value;
                                            setData('porcentaje_participacion', val);
                                            const m = parseFloat(data.monto_contratado) || 0;
                                            const p = parseFloat(val) || 0;
                                            setData('importe', (m * p / 100) || '');
                                        }} />
                                        <span className="input-group-text">%</span>
                                    </div>
                                </div>
                                <div className="col-md-12">
                                    <label className="form-label fw-bold small text-secondary">RESULTADO (Monto contratado × % Participación / 100)</label>
                                    <div className="input-group">
                                        <span className="input-group-text">{data.moneda === 'Dólares' ? 'US$' : 'S/'}</span>
                                        <input type="text" className="form-control bg-light fw-bold" value={(() => { const m = parseFloat(data.monto_contratado) || 0; const p = parseFloat(data.porcentaje_participacion) || 0; return (m * p / 100).toFixed(2); })()} readOnly />
                                    </div>
                                </div>
                            </>
                        )}
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">IMPORTE</label>
                            <div className="input-group">
                                <span className="input-group-text">{data.moneda === 'Dólares' ? 'US$' : 'S/'}</span>
                                <input type="number" step="0.01" className="form-control" value={data.importe ?? ''} onChange={e => setData('importe', e.target.value)} readOnly={data.consorciado} />
                            </div>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">TIPO DE CAMBIO VENTA</label>
                            <input type="number" step="0.0001" className="form-control" value={data.tipo_cambio_venta ?? ''} onChange={e => setData('tipo_cambio_venta', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">N° DE RESOLUCIÓN</label>
                            <input type="text" className="form-control" value={data.numero_resolucion || ''} onChange={e => setData('numero_resolucion', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">FECHA DE APROBACIÓN</label>
                            <input type="date" className="form-control" value={data.fecha_aprobacion || ''} onChange={e => setData('fecha_aprobacion', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Especialidad</label>
                            <input type="text" className="form-control" value={data.especialidad} onChange={e => setData('especialidad', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">Duración</label>
                            <div className="input-group">
                                <input type="number" step="1" min="0" className="form-control" value={data.duracion} onChange={e => setData('duracion', e.target.value)} />
                                <span className="input-group-text">días calendario</span>
                            </div>
                        </div>
                        <div className="col-md-12">
                            <label className="form-label fw-bold small text-secondary">Tipo / Clasificación</label>
                            <input type="text" className="form-control bg-light" value={data.clasificacion} readOnly />
                        </div>
                    </div>

                    <h5 className="mb-3 fw-bold text-body">Documentación</h5>
                    {existingDocs.length > 0 && (
                        <div className="mb-4">
                            <label className="form-label small text-secondary">Documentos existentes</label>
                            {existingDocs.map((doc) => {
                                const marked = toDelete.includes(doc.id);
                                return (
                                    <div key={doc.id} className={`d-flex align-items-center gap-3 p-3 mb-2 rounded-3 ${marked ? 'bg-danger bg-opacity-10' : 'bg-body-tertiary'}`}>
                                        <span className="fw-medium">{doc.nombre}</span>
                                        <a href={doc.url || `/storage/${doc.file_path}`} target="_blank" rel="noopener noreferrer" className="small">Ver archivo</a>
                                        {marked ? (
                                            <button type="button" className="btn btn-sm btn-outline-secondary" onClick={() => undoRemoveExisting(doc.id)}>Deshacer</button>
                                        ) : (
                                            <button type="button" className="btn btn-sm btn-outline-danger" onClick={() => removeExistingDoc(doc.id)}>Eliminar</button>
                                        )}
                                    </div>
                                );
                            })}
                        </div>
                    )}
                    <div className="mb-4">
                        <label className="form-label small text-secondary">Nuevos documentos</label>
                        {(data.documentos || []).map((doc, index) => (
                            <div key={index} className="row g-2 align-items-end mb-2 p-3 bg-body-tertiary rounded-3">
                                <div className="col-md-4">
                                    <input type="text" className="form-control form-control-sm" placeholder="Nombre" value={doc.nombre || ''} onChange={e => handleDocumentoChange(index, 'nombre', e.target.value)} />
                                </div>
                                <div className="col-md-6">
                                    <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx,image/*" onChange={e => handleDocumentoChange(index, 'archivo', e.target.files[0] || null)} />
                                    {doc.archivo && doc.archivo.name && <small className="text-success">{doc.archivo.name}</small>}
                                </div>
                                <div className="col-md-2">
                                    <button type="button" className="btn btn-sm btn-outline-danger w-100" onClick={() => removeDocumento(index)}><i className="bi bi-trash"></i></button>
                                </div>
                            </div>
                        ))}
                        <button type="button" className="btn btn-sm btn-outline-primary rounded-pill" onClick={addDocumento}>
                            <i className="bi bi-plus-lg me-2"></i> Agregar más documento
                        </button>
                    </div>

                    <div className="d-flex justify-content-between align-items-center mt-5 pt-3 border-top flex-wrap gap-2">
                        <div>
                            {canDelete && (
                                <button
                                    type="button"
                                    className="btn btn-outline-danger"
                                    onClick={(e) => {
                                        e.preventDefault();
                                        e.stopPropagation();
                                        const id = consultorObra?.id ?? consultorObra?.consultor_obra_id;
                                        if (!id) {
                                            Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo obtener el ID del registro.' });
                                            return;
                                        }
                                        Swal.fire({
                                            title: '¿Anular este registro?',
                                            text: 'El registro se marcará como anulado. Esta acción no se puede deshacer.',
                                            icon: 'warning',
                                            showCancelButton: true,
                                            confirmButtonColor: '#dc3545',
                                            cancelButtonColor: '#6c757d',
                                            confirmButtonText: 'Sí, anular'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                router.delete(route('consultor-obras.destroy', id), {
                                                    onSuccess: () => Swal.fire({ icon: 'success', title: 'Listo', text: 'Registro anulado.' }),
                                                    onError: () => Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo anular el registro.' }),
                                                });
                                            }
                                        });
                                    }}
                                >
                                    <i className="bi bi-trash me-1" /> Anular registro
                                </button>
                            )}
                        </div>
                        <div className="d-flex gap-2">
                            <Link href={folderId ? route('consultor-obras.index', { folder_id: folderId }) : route('consultor-obras.index')} className="btn btn-outline-secondary">Cancelar</Link>
                            <SubmitButton processing={sending} icon="bi-save" className="px-5">Actualizar</SubmitButton>
                        </div>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
