import React, { useMemo } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link, router } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

const fmtDate = (d) => (!d ? '' : (typeof d === 'string' && d.length >= 10 ? d.substring(0, 10) : d));

const scalarKeys = [
    'nombre_sigla_entidad', 'nomenclatura', 'descripcion_objeto', 'cui', 'numero_contrato',
    'fecha_firma_contrato', 'monto_total', 'plazo', 'fecha_inicio',
    'fecha_suspension', 'fecha_reinicio', 'fecha_final', 'porcentaje_participacion',
    'monto_neto', 'liquidado_recepcionado', 'tiene_suspension',
    'fecha_entrega_terreno', 'fecha_recepcion_obra', 'fecha_aprobacion_liquidacion',
    'tiene_adicional_obra', 'tiene_deductivo_obra', 'tiene_aprobacion_acto_resolutivo',
    'fecha_adicional_obra', 'monto_adicional', 'plazo_adicional',
    'fecha_deductivo_obra', 'monto_deductivo', 'plazo_deductivo',
    'fecha_aprobacion_acto_resolutivo', 'monto_aprobacion_acto_resolutivo', 'plazo_aprobacion_acto_resolutivo',
];
const fileKeys = [
    'archivo_contrato', 'archivo_acta_recepcion', 'archivo_acta_inicio',
    'archivo_acta_suspension', 'archivo_acta_reinicio', 'archivo_acta_entrega_terreno',
    'archivo_acta_adicional', 'archivo_acta_deductivo', 'archivo_aprobacion_acto_resolutivo',
];

const Input = ({ name, label, type = 'text', required = false, className = '', data, setData, errors, ...rest }) => (
    <div className={`${className} w-100`}>
        <label className="form-label fw-bold small text-secondary">{label}{required && ' *'}</label>
        <input
            type={type}
            className={`form-control w-100 ${errors[name] ? 'is-invalid' : ''}`}
            value={data[name] ?? ''}
            onChange={e => setData(name, e.target.value)}
            required={required}
            {...rest}
        />
        {errors[name] && <div className="invalid-feedback">{errors[name]}</div>}
    </div>
);

const FileField = ({ name, label, required = false, obra, data, setData, errors }) => {
    const existingUrl = obra[name + '_url'];
    const hasNewFile = data[name] && typeof data[name] === 'object' && data[name].name;
    return (
        <div className="col-12 col-md-6 w-100">
            <label className="form-label fw-bold small text-secondary">{label}{required && ' *'}</label>
            {existingUrl && (
                <div className="mb-2">
                    <a href={existingUrl} target="_blank" rel="noopener noreferrer" className="btn btn-sm btn-outline-primary me-2">
                        <i className="bi bi-download me-1" /> Descargar actual
                    </a>
                    {hasNewFile && <small className="text-success d-block mt-1">Reemplazar con: {data[name].name}</small>}
                </div>
            )}
            <input
                type="file"
                accept=".pdf,application/pdf"
                className={`form-control w-100 ${errors[name] ? 'is-invalid' : ''}`}
                onChange={e => setData(name, e.target.files[0] || null)}
                required={!existingUrl && required}
            />
            {errors[name] && <div className="invalid-feedback">{errors[name]}</div>}
        </div>
    );
};

const RadioSiNo = ({ label, value, onChange, name }) => (
    <div className="p-3 rounded border bg-info bg-opacity-10 mb-2">
        <label className="form-label fw-bold small text-secondary mb-2 d-block">{label}</label>
        <div className="d-flex gap-4">
            <label className="form-check">
                <input type="radio" name={name} className="form-check-input" value="SI" checked={value === 'SI'} onChange={() => onChange('SI')} />
                <span className="form-check-label">SÍ</span>
            </label>
            <label className="form-check">
                <input type="radio" name={name} className="form-check-input" value="NO" checked={value === 'NO'} onChange={() => onChange('NO')} />
                <span className="form-check-label">NO</span>
            </label>
        </div>
    </div>
);

function inferSiNo(obra, flag) {
    if (flag === 'adicional') {
        return obra.fecha_adicional_obra || obra.archivo_acta_adicional || obra.monto_adicional != null || obra.plazo_adicional != null ? 'SI' : 'NO';
    }
    if (flag === 'deductivo') {
        return obra.fecha_deductivo_obra || obra.archivo_acta_deductivo || obra.monto_deductivo != null || obra.plazo_deductivo != null ? 'SI' : 'NO';
    }
    if (flag === 'aprobacion') {
        return obra.fecha_aprobacion_acto_resolutivo || obra.archivo_aprobacion_acto_resolutivo || obra.monto_aprobacion_acto_resolutivo != null || obra.plazo_aprobacion_acto_resolutivo != null ? 'SI' : 'NO';
    }
    return 'NO';
}

export default function Edit({ obra, folderId = null, canDelete = false }) {
    const hasSuspension = !!obra.fecha_suspension || !!obra.fecha_reinicio;
    const docsLiq = obra.documentos_liquidacion || obra.documentosLiquidacion || [];

    const { data, setData, processing, errors } = useForm({
        nombre_sigla_entidad: obra.nombre_sigla_entidad || '',
        nomenclatura: obra.nomenclatura || '',
        descripcion_objeto: obra.descripcion_objeto || '',
        cui: obra.cui || '',
        numero_contrato: obra.numero_contrato || '',
        fecha_firma_contrato: fmtDate(obra.fecha_firma_contrato),
        monto_total: obra.monto_total ?? '',
        plazo: obra.plazo ?? '',
        fecha_inicio: fmtDate(obra.fecha_inicio),
        tiene_adicional_obra: inferSiNo(obra, 'adicional'),
        fecha_adicional_obra: fmtDate(obra.fecha_adicional_obra),
        monto_adicional: obra.monto_adicional ?? '',
        plazo_adicional: obra.plazo_adicional ?? '',
        tiene_deductivo_obra: inferSiNo(obra, 'deductivo'),
        fecha_deductivo_obra: fmtDate(obra.fecha_deductivo_obra),
        monto_deductivo: obra.monto_deductivo ?? '',
        plazo_deductivo: obra.plazo_deductivo ?? '',
        tiene_aprobacion_acto_resolutivo: inferSiNo(obra, 'aprobacion'),
        fecha_aprobacion_acto_resolutivo: fmtDate(obra.fecha_aprobacion_acto_resolutivo),
        monto_aprobacion_acto_resolutivo: obra.monto_aprobacion_acto_resolutivo ?? '',
        plazo_aprobacion_acto_resolutivo: obra.plazo_aprobacion_acto_resolutivo ?? '',
        tiene_suspension: hasSuspension ? 'SI' : 'NO',
        fecha_suspension: fmtDate(obra.fecha_suspension),
        fecha_reinicio: fmtDate(obra.fecha_reinicio),
        fecha_final: fmtDate(obra.fecha_final),
        porcentaje_participacion: obra.porcentaje_participacion ?? '',
        monto_neto: obra.monto_neto ?? '',
        liquidado_recepcionado: !!obra.liquidado_recepcionado,
        fecha_entrega_terreno: fmtDate(obra.fecha_entrega_terreno),
        fecha_recepcion_obra: fmtDate(obra.fecha_recepcion_obra),
        fecha_aprobacion_liquidacion: fmtDate(obra.fecha_aprobacion_liquidacion),
        archivo_contrato: null,
        archivo_acta_recepcion: null,
        archivo_acta_inicio: null,
        archivo_acta_adicional: null,
        archivo_acta_deductivo: null,
        archivo_aprobacion_acto_resolutivo: null,
        archivo_acta_suspension: null,
        archivo_acta_reinicio: null,
        archivo_acta_entrega_terreno: null,
        documentos: [{ nombre: '', archivo: null }],
        documento_delete_ids: [],
    });

    const tieneSuspension = data.tiene_suspension === 'SI';
    const tieneAdicional = data.tiene_adicional_obra === 'SI';
    const tieneDeductivo = data.tiene_deductivo_obra === 'SI';
    const tieneAprobacionResolutivo = data.tiene_aprobacion_acto_resolutivo === 'SI';

    const montoNetoCalculado = useMemo(() => {
        const m = parseFloat(data.monto_total) || 0;
        const p = parseFloat(data.porcentaje_participacion) || 0;
        return (m * p / 100).toFixed(2);
    }, [data.monto_total, data.porcentaje_participacion]);

    const addDocumentoRow = () => {
        setData('documentos', [...(data.documentos || []), { nombre: '', archivo: null }]);
    };

    const removeDocumentoRow = (index) => {
        const next = (data.documentos || []).filter((_, i) => i !== index);
        setData('documentos', next.length ? next : [{ nombre: '', archivo: null }]);
    };

    const setDocumento = (index, field, value) => {
        const next = [...(data.documentos || [])];
        next[index] = { ...next[index], [field]: value };
        setData('documentos', next);
    };

    const markDocDeleted = (id) => {
        setData('documento_delete_ids', [...(data.documento_delete_ids || []), id]);
    };

    const docsLiqVisible = (docsLiq || []).filter((d) => !(data.documento_delete_ids || []).includes(d.id));

    const submit = (e) => {
        e.preventDefault();
        const formData = new FormData();
        formData.append('_method', 'PUT');
        scalarKeys.forEach((key) => {
            const val = data[key];
            if (val !== undefined && val !== null && val !== '')
                formData.append(key, typeof val === 'boolean' ? (val ? '1' : '0') : String(val));
        });
        fileKeys.forEach((key) => {
            const file = data[key];
            if (file && typeof file === 'object' && file instanceof File) formData.append(key, file);
        });
        (data.documento_delete_ids || []).forEach((id) => {
            formData.append('documento_delete_ids[]', id);
        });
        let idx = 0;
        (data.documentos || []).forEach((doc) => {
            if (doc.archivo && doc.archivo instanceof File) {
                formData.append(`documentos[${idx}][nombre]`, doc.nombre || '');
                formData.append(`documentos[${idx}][archivo]`, doc.archivo);
                idx++;
            }
        });
        router.post(route('ejecutor-obra.update', obra.id), formData, { forceFormData: true });
    };

    return (
        <MainLayout>
            <Head title="Editar Ejecutor de Obra" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body form-card-responsive" style={{ maxWidth: '1000px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Editar Ejecutor de Obra</h3>
                    <p className="text-secondary small">Modificar la información del registro.</p>
                </div>
                <form onSubmit={submit}>
                    <div className="row g-3 mb-3">
                        <div className="col-12">
                            <Input name="nombre_sigla_entidad" label="Nombre o Sigla de la Entidad" required data={data} setData={setData} errors={errors} />
                        </div>
                        <div className="col-12">
                            <Input name="nomenclatura" label="Nomenclatura" required data={data} setData={setData} errors={errors} />
                        </div>
                        <div className="col-12">
                            <label className="form-label fw-bold small text-secondary">Descripción de Objeto *</label>
                            <textarea
                                className={`form-control w-100 ${errors.descripcion_objeto ? 'is-invalid' : ''}`}
                                rows={3}
                                value={data.descripcion_objeto ?? ''}
                                onChange={e => setData('descripcion_objeto', e.target.value)}
                                required
                            />
                            {errors.descripcion_objeto && <div className="invalid-feedback">{errors.descripcion_objeto}</div>}
                        </div>
                        <div className="col-12 col-md-6">
                            <Input name="cui" label="CUI (Código Único de Inversión)" required data={data} setData={setData} errors={errors} />
                        </div>
                        <div className="col-12 col-md-6">
                            <Input name="numero_contrato" label="# CONTRATO" required data={data} setData={setData} errors={errors} />
                        </div>
                        <div className="col-12 col-md-6">
                            <Input name="fecha_firma_contrato" label="FECHA DE FIRMA DE CONTRATO" type="date" required data={data} setData={setData} errors={errors} />
                        </div>
                        <div className="col-12 col-md-6">
                            <FileField name="archivo_contrato" label="Subir Contrato PDF" required={!obra.archivo_contrato} obra={obra} data={data} setData={setData} errors={errors} />
                        </div>
                        <div className="col-12 col-md-6">
                            <label className="form-label fw-bold small text-secondary">Monto Total *</label>
                            <div className="input-group">
                                <span className="input-group-text">S/</span>
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    className={`form-control ${errors.monto_total ? 'is-invalid' : ''}`}
                                    value={data.monto_total ?? ''}
                                    onChange={e => setData('monto_total', e.target.value)}
                                    required
                                />
                            </div>
                            {errors.monto_total && <div className="invalid-feedback d-block">{errors.monto_total}</div>}
                        </div>
                        <div className="col-12 col-md-6">
                            <Input name="plazo" label="PLAZO (días)" type="number" min="0" required data={data} setData={setData} errors={errors} />
                        </div>

                        <div className="col-12 col-md-6 p-3 rounded border border-primary border-opacity-25 bg-light">
                            <Input name="fecha_inicio" label="Fecha de inicio de obra" type="date" data={data} setData={setData} errors={errors} />
                        </div>
                        <div className="col-12 col-md-6 p-3 rounded border border-primary border-opacity-25 bg-light">
                            <FileField name="archivo_acta_inicio" label="Subir acta de inicio de obra (PDF)" obra={obra} data={data} setData={setData} errors={errors} />
                        </div>
                        <div className="col-12 col-md-6 p-3 rounded border border-primary border-opacity-25 bg-light">
                            <Input name="fecha_recepcion_obra" label="FECHA DE LA RECEPCION DE OBRA" type="date" data={data} setData={setData} errors={errors} />
                        </div>
                        <div className="col-12 col-md-6 p-3 rounded border border-primary border-opacity-25 bg-light">
                            <FileField name="archivo_acta_recepcion" label="Subir acta de recepción de obra (PDF)" obra={obra} data={data} setData={setData} errors={errors} />
                        </div>

                        <div className="col-12">
                            <RadioSiNo
                                label="¿Tuvo adicional de obra?"
                                value={data.tiene_adicional_obra}
                                onChange={(v) => {
                                    setData('tiene_adicional_obra', v);
                                    if (v === 'NO') {
                                        setData('fecha_adicional_obra', '');
                                        setData('monto_adicional', '');
                                        setData('plazo_adicional', '');
                                        setData('archivo_acta_adicional', null);
                                    }
                                }}
                                name="tiene_adicional_obra"
                            />
                        </div>
                        {tieneAdicional && (
                            <div className="row g-3 mb-2 p-3 bg-light rounded w-100 mx-0">
                                <div className="col-12 col-md-6">
                                    <Input name="fecha_adicional_obra" label="Fecha (adicional)" type="date" required data={data} setData={setData} errors={errors} />
                                </div>
                                <div className="col-12 col-md-6">
                                    <FileField name="archivo_acta_adicional" label="Subir acta (adicional) PDF" required={tieneAdicional && !obra.archivo_acta_adicional} obra={obra} data={data} setData={setData} errors={errors} />
                                </div>
                                <div className="col-12 col-md-6">
                                    <label className="form-label fw-bold small text-secondary">Monto adicional (S/)</label>
                                    <div className="input-group">
                                        <span className="input-group-text">S/</span>
                                        <input type="number" step="0.01" min="0" className="form-control" value={data.monto_adicional ?? ''} onChange={e => setData('monto_adicional', e.target.value)} />
                                    </div>
                                </div>
                                <div className="col-12 col-md-6">
                                    <Input name="plazo_adicional" label="Plazo adicional (días)" type="number" min="0" data={data} setData={setData} errors={errors} />
                                </div>
                            </div>
                        )}

                        <div className="col-12">
                            <RadioSiNo
                                label="¿Tuvo deductivo de obra?"
                                value={data.tiene_deductivo_obra}
                                onChange={(v) => {
                                    setData('tiene_deductivo_obra', v);
                                    if (v === 'NO') {
                                        setData('fecha_deductivo_obra', '');
                                        setData('monto_deductivo', '');
                                        setData('plazo_deductivo', '');
                                        setData('archivo_acta_deductivo', null);
                                    }
                                }}
                                name="tiene_deductivo_obra"
                            />
                        </div>
                        {tieneDeductivo && (
                            <div className="row g-3 mb-2 p-3 bg-light rounded w-100 mx-0">
                                <div className="col-12 col-md-6">
                                    <Input name="fecha_deductivo_obra" label="Fecha (deductivo)" type="date" required data={data} setData={setData} errors={errors} />
                                </div>
                                <div className="col-12 col-md-6">
                                    <FileField name="archivo_acta_deductivo" label="Subir acta (deductivo) PDF" required={tieneDeductivo && !obra.archivo_acta_deductivo} obra={obra} data={data} setData={setData} errors={errors} />
                                </div>
                                <div className="col-12 col-md-6">
                                    <label className="form-label fw-bold small text-secondary">Monto deductivo (S/)</label>
                                    <div className="input-group">
                                        <span className="input-group-text">S/</span>
                                        <input type="number" step="0.01" min="0" className="form-control" value={data.monto_deductivo ?? ''} onChange={e => setData('monto_deductivo', e.target.value)} />
                                    </div>
                                </div>
                                <div className="col-12 col-md-6">
                                    <Input name="plazo_deductivo" label="Plazo deductivo (días)" type="number" min="0" data={data} setData={setData} errors={errors} />
                                </div>
                            </div>
                        )}
                    </div>

                    <div className="border-top pt-3 mt-3">
                        <label className="form-label fw-bold small text-secondary">¿Tuvo suspensión?</label>
                        <div className="d-flex gap-4">
                            <label className="form-check">
                                <input type="radio" name="tiene_suspension" className="form-check-input" value="SI" checked={data.tiene_suspension === 'SI'} onChange={() => setData('tiene_suspension', 'SI')} />
                                <span className="form-check-label">SÍ</span>
                            </label>
                            <label className="form-check">
                                <input type="radio" name="tiene_suspension" className="form-check-input" value="NO" checked={data.tiene_suspension === 'NO'} onChange={() => setData('tiene_suspension', 'NO')} />
                                <span className="form-check-label">NO</span>
                            </label>
                        </div>
                    </div>

                    {tieneSuspension && (
                        <div className="row g-3 mb-3 mt-2 p-3 bg-light rounded">
                            <div className="col-12 col-md-6">
                                <Input name="fecha_suspension" label="FECHA DE SUSPENSION" type="date" required data={data} setData={setData} errors={errors} />
                            </div>
                            <div className="col-12 col-md-6">
                                <FileField name="archivo_acta_suspension" label="Subir Acta de Suspensión de Obra (PDF)" required obra={obra} data={data} setData={setData} errors={errors} />
                            </div>
                            <div className="col-12 col-md-6">
                                <Input name="fecha_reinicio" label="FECHA DE REINICIO" type="date" required data={data} setData={setData} errors={errors} />
                            </div>
                            <div className="col-12 col-md-6">
                                <FileField name="archivo_acta_reinicio" label="Subir Acta de Reinicio de Obra (PDF)" required obra={obra} data={data} setData={setData} errors={errors} />
                            </div>
                        </div>
                    )}

                    <div className="col-12 mt-3">
                        <RadioSiNo
                            label="¿Tuvo aprobación mediante acto resolutivo?"
                            value={data.tiene_aprobacion_acto_resolutivo}
                            onChange={(v) => {
                                setData('tiene_aprobacion_acto_resolutivo', v);
                                if (v === 'NO') {
                                    setData('fecha_aprobacion_acto_resolutivo', '');
                                    setData('monto_aprobacion_acto_resolutivo', '');
                                    setData('plazo_aprobacion_acto_resolutivo', '');
                                    setData('archivo_aprobacion_acto_resolutivo', null);
                                }
                            }}
                            name="tiene_aprobacion_acto_resolutivo"
                        />
                    </div>
                    {tieneAprobacionResolutivo && (
                        <div className="row g-3 mb-3 p-3 bg-light rounded border border-primary border-opacity-25 mx-0">
                            <div className="col-12 col-md-6">
                                <Input name="fecha_aprobacion_acto_resolutivo" label="Fecha de aprobación (acto resolutivo)" type="date" required data={data} setData={setData} errors={errors} />
                            </div>
                            <div className="col-12 col-md-6">
                                <FileField name="archivo_aprobacion_acto_resolutivo" label="Subir resolución (PDF)" required={tieneAprobacionResolutivo && !obra.archivo_aprobacion_acto_resolutivo} obra={obra} data={data} setData={setData} errors={errors} />
                            </div>
                            <div className="col-12 col-md-6">
                                <label className="form-label fw-bold small text-secondary">Monto (S/)</label>
                                <div className="input-group">
                                    <span className="input-group-text">S/</span>
                                    <input type="number" step="0.01" min="0" className="form-control" value={data.monto_aprobacion_acto_resolutivo ?? ''} onChange={e => setData('monto_aprobacion_acto_resolutivo', e.target.value)} />
                                </div>
                            </div>
                            <div className="col-12 col-md-6">
                                <Input name="plazo_aprobacion_acto_resolutivo" label="Plazo (días)" type="number" min="0" data={data} setData={setData} errors={errors} />
                            </div>
                        </div>
                    )}

                    <div className="row g-3 mb-3">
                        <div className="col-12 col-md-3">
                            <Input name="fecha_final" label="Fecha Final" type="date" data={data} setData={setData} errors={errors} />
                        </div>
                        <div className="col-12 col-md-3">
                            <label className="form-label fw-bold small text-secondary">% Participación</label>
                            <div className="input-group">
                                <input
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    max="100"
                                    className="form-control"
                                    value={data.porcentaje_participacion ?? ''}
                                    onChange={e => setData('porcentaje_participacion', e.target.value)}
                                />
                                <span className="input-group-text">%</span>
                            </div>
                        </div>
                        <div className="col-12 col-md-6">
                            <label className="form-label fw-bold small text-secondary">Monto Neto (calculado)</label>
                            <div className="input-group">
                                <span className="input-group-text">S/</span>
                                <input type="text" className="form-control bg-light" value={montoNetoCalculado} readOnly />
                            </div>
                        </div>
                        <div className="col-12">
                            <label className="form-check">
                                <input type="checkbox" className="form-check-input" checked={!!data.liquidado_recepcionado} onChange={e => setData('liquidado_recepcionado', e.target.checked)} />
                                <span className="form-check-label fw-bold small text-secondary">Liquidado y/o recepcionado</span>
                            </label>
                        </div>
                        <div className="col-12 col-md-6">
                            <Input name="fecha_entrega_terreno" label="FECHA DE ENTREGA DE TERRENO" type="date" data={data} setData={setData} errors={errors} />
                        </div>
                        <div className="col-12 col-md-6">
                            <FileField name="archivo_acta_entrega_terreno" label="Subir Acta de Entrega de Terreno (PDF)" obra={obra} data={data} setData={setData} errors={errors} />
                        </div>
                        <div className="col-12 col-md-6">
                            <Input name="fecha_aprobacion_liquidacion" label="FECHA DE LA APROBACION DE LIQUIDACION DE OBRA" type="date" data={data} setData={setData} errors={errors} />
                        </div>
                        <div className="col-12">
                            <label className="form-label fw-bold small text-secondary">Resolución de liquidación (nombre + PDF)</label>
                            {docsLiqVisible.length > 0 && (
                                <ul className="list-group mb-2">
                                    {docsLiqVisible.map((doc) => (
                                        <li key={doc.id} className="list-group-item d-flex justify-content-between align-items-center">
                                            <span>
                                                <a href={doc.url} target="_blank" rel="noopener noreferrer" className="me-2">{doc.nombre || 'Documento'}</a>
                                            </span>
                                            <button type="button" className="btn btn-sm btn-outline-danger" onClick={() => markDocDeleted(doc.id)}>Quitar</button>
                                        </li>
                                    ))}
                                </ul>
                            )}
                            {(data.documentos || []).map((doc, index) => (
                                <div key={index} className="row g-2 align-items-end mb-2 p-2 border rounded bg-white">
                                    <div className="col-12 col-md-4">
                                        <label className="form-label small mb-0">Nombre / detalle</label>
                                        <input
                                            type="text"
                                            className="form-control"
                                            placeholder="Ej. Resolución N°..."
                                            value={doc.nombre || ''}
                                            onChange={e => setDocumento(index, 'nombre', e.target.value)}
                                        />
                                    </div>
                                    <div className="col-12 col-md-5">
                                        <input
                                            type="file"
                                            accept=".pdf,application/pdf"
                                            className="form-control"
                                            onChange={e => setDocumento(index, 'archivo', e.target.files[0] || null)}
                                        />
                                        {doc.archivo?.name && <small className="text-success">{doc.archivo.name}</small>}
                                    </div>
                                    <div className="col-12 col-md-3">
                                        <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => removeDocumentoRow(index)} disabled={(data.documentos || []).length <= 1}>
                                            Quitar fila
                                        </button>
                                    </div>
                                </div>
                            ))}
                            <button type="button" className="btn btn-outline-primary btn-sm rounded-pill" onClick={addDocumentoRow}>
                                <i className="bi bi-plus-lg me-1" /> Agregar archivo
                            </button>
                        </div>
                    </div>

                    <div className="d-flex justify-content-end mt-4 pt-3 border-top gap-2">
                        <Link href={route('ejecutor-obra.index', folderId ? { folder_id: folderId } : {})} className="btn btn-outline-secondary px-4 rounded-pill">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-save" className="px-5 rounded-pill shadow-sm">Actualizar</SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
