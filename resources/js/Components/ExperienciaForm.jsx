import React, { useMemo, useState, useRef } from 'react';
import { useForm } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';
import {
    parseDateDDMMYYYY,
    formatDateToDDMMYYYY,
    calcTotalDias,
    calcTotalMeses,
    calcTotalDiasSinTraslape,
    calcPlazoSum,
} from '@/Utils/experienciaCalculations';

const FILE_ACCEPT = '.pdf,.jpg,.jpeg,.png';
const MAX_FILE_SIZE_MB = 25;

const explainValidationError = (message, fallback) => {
    if (!message) return '';
    const msg = String(message);
    if (msg.includes('validation.max.file') || msg.includes('The ') || msg.includes('kilobytes')) {
        return fallback || `El archivo excede el tamaño máximo permitido (${MAX_FILE_SIZE_MB} MB).`;
    }
    return msg;
};

function formatDateForInput(dateStrOrDate) {
    if (!dateStrOrDate) return '';
    if (dateStrOrDate instanceof Date) return dateStrOrDate.toISOString().slice(0, 10);
    const d = parseDateDDMMYYYY(dateStrOrDate);
    return d ? formatDateToDDMMYYYY(d).split('/').reverse().join('-') : '';
}

/** Convierte ISO (yyyy-mm-dd) o DD/MM/YYYY a DD/MM/YYYY para estado y backend. */
function toDDMMYYYY(isoOrDDMMYYYY) {
    if (!isoOrDDMMYYYY) return '';
    const s = String(isoOrDDMMYYYY).trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(s)) {
        const [y, m, d] = s.split('-');
        return `${d}/${m}/${y}`;
    }
    return s;
}

/** Valor para input type="date": siempre yyyy-mm-dd. */
function toInputDate(value) {
    if (!value) return '';
    const isoOrPartial = String(value).trim();
    if (/^\d{0,4}(-\d{0,2})?(-\d{0,2})?$/.test(isoOrPartial)) return isoOrPartial;
    const d = parseDateDDMMYYYY(value);
    if (d) return formatDateToDDMMYYYY(d).split('/').reverse().join('-');
    return '';
}

/**
 * structure: 1 = Especialistas (15 cols, CUI, O/S, Fecha Susp/Reinicio)
 *            2 = Proveedor Servicios (14 cols, no CUI, O/S)
 *            3 = Proveedor Bienes (12 cols, O/C, no Fecha Susp/Reinicio)
 */
const TIPOS_DOCUMENTO = [
    { value: 'CONTRATO', label: 'CONTRATO' },
    { value: 'COMPROBANTE_DE_PAGO', label: 'COMPROBANTE DE PAGO' },
    { value: 'CONFORMIDAD_DE_SERVICIO', label: 'CONFORMIDAD DE SERVICIO' },
    { value: 'OTROS', label: 'Otros' },
];

export default function ExperienciaForm({
    structure,
    variant,
    initialData = {},
    submitRoute,
    method = 'POST',
    cancelUrl,
    title,
}) {
    const isEspecialistasConEstadoContrato = ['especialistas-ejecucion', 'especialistas-consultoria', 'municipalidades-funcionario-publico'].includes(variant);
    const isMunicipalidades = variant === 'municipalidades-funcionario-publico';
    const isProveedorExperiencia = ['proveedor-bienes', 'proveedor-servicios'].includes(variant);
    const showFechaContratoYEstado = isEspecialistasConEstadoContrato || isProveedorExperiencia;
    const allowDeleteExistingDocuments = variant === 'municipalidades-funcionario-publico' && method === 'PUT';
    const isProveedorBienes = structure === 3;
    const isProveedorServicios = structure === 2;
    const hasCUI = structure === 1;
    const useMultiDocumentos = hasCUI || isProveedorBienes || isProveedorServicios;
    const showSuspensionReinicioPdf = isProveedorServicios || hasCUI;
    const hasSuspensionReinicio = structure === 1 || structure === 2;
    const numeroContratoLabel = structure === 3
        ? 'N° CONTRATO / O/C / COMPROBANTE DE PAGO'
        : 'N° CONTRATO / O/S / COMPROBANTE DE PAGO';

    const getInitialFormData = () => {
        const base = {
            cliente: initialData.cliente ?? '',
            objeto_del_contrato: initialData.objeto_del_contrato ?? '',
            fecha_inicio: initialData.fecha_inicio ? toDDMMYYYY(initialData.fecha_inicio) : '',
            fecha_culminacion: initialData.fecha_culminacion ? toDDMMYYYY(initialData.fecha_culminacion) : '',
            traslape: initialData.traslape != null ? Number(initialData.traslape) : 0,
            monto_neto: initialData.monto_neto ?? '',
            folder_id: initialData.folder_id ?? '',
            clasificacion: initialData.clasificacion ?? '',
            tipo_documento_adjunto: initialData.tipo_documento_adjunto ?? '',
            archivo_contrato: null,
        };
        if (showFechaContratoYEstado) {
            base.fecha_contrato_cp = initialData.fecha_contrato_cp ? toDDMMYYYY(initialData.fecha_contrato_cp) : '';
            if (isProveedorExperiencia) {
                base.estado_contrato = initialData.estado_contrato ?? 'EN CURSO';
            } else {
                base.estado = initialData.estado ?? 'EN CURSO';
            }
        }
        if (useMultiDocumentos) {
            base.documentos = Array.isArray(initialData.documentos) && initialData.documentos.length > 0
                ? initialData.documentos.map(d => ({ tipo_documento_adjunto: d.tipo_documento_adjunto ?? '', nombre_otro: d.nombre_otro ?? '', archivo: null }))
                : [{ tipo_documento_adjunto: '', nombre_otro: '', archivo: null }];
        }
        if (allowDeleteExistingDocuments) {
            base.documentos_eliminar_ids = [];
        }
        if (isProveedorServicios || hasCUI) {
            base.archivo_suspension = null;
            base.archivo_reinicio = null;
        }
        if (hasCUI) base.cui = initialData.cui ?? '';
        if (structure === 3) base.numero_contrato_oc_comprobante = initialData.numero_contrato_oc_comprobante ?? '';
        else base.numero_contrato_os_comprobante = initialData.numero_contrato_os_comprobante ?? '';
        if (hasSuspensionReinicio) {
            base.tiene_fecha_suspension = initialData.tiene_fecha_suspension ?? (initialData.fecha_suspension ? 'SI' : 'NO');
            base.tiene_fecha_reinicio = initialData.tiene_fecha_reinicio ?? (initialData.fecha_reinicio ? 'SI' : 'NO');
            base.fecha_suspension = initialData.fecha_suspension ? toDDMMYYYY(initialData.fecha_suspension) : '';
            base.fecha_reinicio = initialData.fecha_reinicio ? toDDMMYYYY(initialData.fecha_reinicio) : '';
        }
        return base;
    };

    const initial = getInitialFormData();
    if (method === 'PUT') initial._method = 'PUT';
    const { data, setData, post, processing, errors } = useForm(initial);

    const [documentosExistentesVista, setDocumentosExistentesVista] = useState(() =>
        allowDeleteExistingDocuments && Array.isArray(initialData.documentos_existentes)
            ? [...initialData.documentos_existentes]
            : null
    );
    const documentosEliminarRef = useRef([]);

    const documentosActualesList = allowDeleteExistingDocuments && documentosExistentesVista != null
        ? documentosExistentesVista
        : (Array.isArray(initialData.documentos_existentes) ? initialData.documentos_existentes : []);

    const quitarDocumentoExistente = (id) => {
        if (!allowDeleteExistingDocuments) return;
        setDocumentosExistentesVista((prev) => (prev || []).filter((d) => d.id !== id));
        documentosEliminarRef.current = [...documentosEliminarRef.current, id];
        setData('documentos_eliminar_ids', documentosEliminarRef.current);
    };

    const totalDias = useMemo(() => {
        const d = calcTotalDias(data.fecha_inicio, data.fecha_culminacion);
        return d;
    }, [data.fecha_inicio, data.fecha_culminacion]);

    const totalMeses = useMemo(() => calcTotalMeses(totalDias), [totalDias]);
    const plazoSum = useMemo(() => calcPlazoSum(totalMeses, totalDias), [totalMeses, totalDias]);
    const totalDiasSinTraslape = useMemo(
        () => {
            if (hasCUI || isProveedorBienes || isProveedorServicios) return totalDias;
            return calcTotalDiasSinTraslape(totalDias, data.traslape);
        },
        [totalDias, data.traslape, hasCUI, isProveedorBienes, isProveedorServicios]
    );

    const addDocumentoRow = () => {
        setData('documentos', [...(data.documentos || []), { tipo_documento_adjunto: '', nombre_otro: '', archivo: null }]);
    };

    const updateDocumento = (index, field, value) => {
        const next = [...(data.documentos || [])];
        if (!next[index]) next[index] = { tipo_documento_adjunto: '', nombre_otro: '', archivo: null };
        next[index] = { ...next[index], [field]: value };
        setData('documentos', next);
    };

    const removeDocumentoRow = (index) => {
        const next = (data.documentos || []).filter((_, i) => i !== index);
        setData('documentos', next.length ? next : [{ tipo_documento_adjunto: '', nombre_otro: '', archivo: null }]);
    };

    const handleFechaChange = (field, value) => {
        setData(field, value);
    };

    const handleTraslapeChange = (value) => {
        const n = parseFloat(value);
        setData('traslape', isNaN(n) ? 0 : Math.max(0, n));
    };

    const submit = (e) => {
        e.preventDefault();
        // Usar siempre POST con FormData para que PHP reciba los campos (PUT + multipart no rellena $_POST).
        // En edición, data ya incluye _method: 'PUT' para que Laravel enrute al update.
        post(submitRoute, { forceFormData: true });
    };

    return (
        <form onSubmit={submit} className="experiencia-form">
            <div className="mb-4">
                <h3 className="fw-bold mb-1">{title}</h3>
            </div>

            <div className="row g-3">
                <div className="col-12">
                    <label className="form-label fw-medium">CLIENTE *</label>
                    <input
                        type="text"
                        className={`form-control ${errors.cliente ? 'is-invalid' : ''}`}
                        value={data.cliente}
                        onChange={e => setData('cliente', e.target.value)}
                        required
                    />
                    {errors.cliente && <div className="invalid-feedback">{errors.cliente}</div>}
                </div>
                <div className="col-12">
                    <label className="form-label fw-medium">OBJETO DEL CONTRATO *</label>
                    <textarea
                        className={`form-control ${errors.objeto_del_contrato ? 'is-invalid' : ''}`}
                        rows={3}
                        value={data.objeto_del_contrato}
                        onChange={e => setData('objeto_del_contrato', e.target.value)}
                        required
                    />
                    {errors.objeto_del_contrato && <div className="invalid-feedback">{errors.objeto_del_contrato}</div>}
                </div>
                {hasCUI && (
                    <div className="col-md-6">
                        <label className="form-label fw-medium">CUI</label>
                        <input
                            type="text"
                            className={`form-control ${errors.cui ? 'is-invalid' : ''}`}
                            value={data.cui}
                            onChange={e => setData('cui', e.target.value)}
                        />
                        {errors.cui && <div className="invalid-feedback">{errors.cui}</div>}
                    </div>
                )}
                <div className={hasCUI ? 'col-md-6' : 'col-12'}>
                    <label className="form-label fw-medium">{numeroContratoLabel} *</label>
                    <input
                        type="text"
                        className={`form-control ${errors.numero_contrato_os_comprobante || errors.numero_contrato_oc_comprobante ? 'is-invalid' : ''}`}
                        value={structure === 3 ? (data.numero_contrato_oc_comprobante ?? '') : (data.numero_contrato_os_comprobante ?? '')}
                        onChange={e => structure === 3 ? setData('numero_contrato_oc_comprobante', e.target.value) : setData('numero_contrato_os_comprobante', e.target.value)}
                        required
                    />
                    {(errors.numero_contrato_os_comprobante || errors.numero_contrato_oc_comprobante) && (
                        <div className="invalid-feedback">{errors.numero_contrato_os_comprobante || errors.numero_contrato_oc_comprobante}</div>
                    )}
                </div>
                {showFechaContratoYEstado && (
                    <>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">FECHA DE CONTRATO O CP</label>
                            <input
                                type="date"
                                className={`form-control ${errors.fecha_contrato_cp ? 'is-invalid' : ''}`}
                                value={toInputDate(data.fecha_contrato_cp)}
                                onChange={e => setData('fecha_contrato_cp', e.target.value)}
                            />
                            {errors.fecha_contrato_cp && <div className="invalid-feedback">{errors.fecha_contrato_cp}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">ESTADO</label>
                            <select
                                className={`form-select ${isProveedorExperiencia ? (errors.estado_contrato ? 'is-invalid' : '') : (errors.estado ? 'is-invalid' : '')}`}
                                value={isProveedorExperiencia ? (data.estado_contrato || 'EN CURSO') : (data.estado || 'EN CURSO')}
                                onChange={e => setData(isProveedorExperiencia ? 'estado_contrato' : 'estado', e.target.value)}
                            >
                                <option value="COMPLETO">COMPLETO</option>
                                <option value="INCOMPLETO">INCOMPLETO</option>
                                <option value="EN CURSO">EN CURSO</option>
                                <option value="ARCHIVADO">ARCHIVADO</option>
                            </select>
                            {(isProveedorExperiencia ? errors.estado_contrato : errors.estado) && (
                                <div className="invalid-feedback">{isProveedorExperiencia ? errors.estado_contrato : errors.estado}</div>
                            )}
                        </div>
                    </>
                )}
                {hasSuspensionReinicio && (
                    <>
                        <div className="col-md-6">
                            <div className="p-3 rounded bg-light border border-opacity-25">
                                <label className="form-label fw-medium mb-2">FECHA DE SUSPENSIÓN</label>
                                <div className="d-flex gap-4 mb-0">
                                    <label className="form-check">
                                        <input
                                            type="radio"
                                            name="tiene_fecha_suspension"
                                            className="form-check-input"
                                            value="SI"
                                            checked={data.tiene_fecha_suspension === 'SI'}
                                            onChange={() => setData('tiene_fecha_suspension', 'SI')}
                                        />
                                        <span className="form-check-label">Si</span>
                                    </label>
                                    <label className="form-check">
                                        <input
                                            type="radio"
                                            name="tiene_fecha_suspension"
                                            className="form-check-input"
                                            value="NO"
                                            checked={data.tiene_fecha_suspension === 'NO'}
                                            onChange={() => { setData('tiene_fecha_suspension', 'NO'); setData('fecha_suspension', ''); if (showSuspensionReinicioPdf) setData('archivo_suspension', null); }}
                                        />
                                        <span className="form-check-label">No</span>
                                    </label>
                                </div>
                                {data.tiene_fecha_suspension === 'SI' && (
                                    <div className="mt-3">
                                        <input
                                            type="date"
                                            className="form-control"
                                            value={toInputDate(data.fecha_suspension)}
                                            onChange={e => setData('fecha_suspension', e.target.value)}
                                        />
                                        {showSuspensionReinicioPdf && (
                                            <div className="mt-3">
                                                <label className="form-label small mb-1">{hasCUI ? 'Adjuntar PDF (suspensión)' : 'Adjuntar documento (suspensión)'}</label>
                                                <input
                                                    type="file"
                                                    name="archivo_suspension"
                                                    accept={hasCUI ? '.pdf' : FILE_ACCEPT}
                                                    className={`form-control form-control-sm ${errors.archivo_suspension ? 'is-invalid' : ''}`}
                                                    onChange={e => setData('archivo_suspension', e.target.files?.[0] || null)}
                                                />
                                                {method === 'PUT' && initialData.archivo_suspension_url && (
                                                    <div className="mt-2 small">
                                                        <a href={initialData.archivo_suspension_url} target="_blank" rel="noopener noreferrer" className="text-decoration-none">
                                                            Ver archivo actual <i className="bi bi-box-arrow-up-right ms-1"></i>
                                                        </a>
                                                    </div>
                                                )}
                                                {errors.archivo_suspension && <div className="invalid-feedback d-block">{explainValidationError(errors.archivo_suspension, `El archivo de suspensión excede el tamaño máximo permitido (${MAX_FILE_SIZE_MB} MB).`)}</div>}
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                        <div className="col-md-6">
                            <div className="p-3 rounded bg-light border border-opacity-25">
                                <label className="form-label fw-medium mb-2">FECHA DE REINICIO</label>
                                <div className="d-flex gap-4 mb-0">
                                    <label className="form-check">
                                        <input
                                            type="radio"
                                            name="tiene_fecha_reinicio"
                                            className="form-check-input"
                                            value="SI"
                                            checked={data.tiene_fecha_reinicio === 'SI'}
                                            onChange={() => setData('tiene_fecha_reinicio', 'SI')}
                                        />
                                        <span className="form-check-label">Si</span>
                                    </label>
                                    <label className="form-check">
                                        <input
                                            type="radio"
                                            name="tiene_fecha_reinicio"
                                            className="form-check-input"
                                            value="NO"
                                            checked={data.tiene_fecha_reinicio === 'NO'}
                                            onChange={() => { setData('tiene_fecha_reinicio', 'NO'); setData('fecha_reinicio', ''); if (showSuspensionReinicioPdf) setData('archivo_reinicio', null); }}
                                        />
                                        <span className="form-check-label">No</span>
                                    </label>
                                </div>
                                {data.tiene_fecha_reinicio === 'SI' && (
                                    <div className="mt-3">
                                        <input
                                            type="date"
                                            className="form-control"
                                            value={toInputDate(data.fecha_reinicio)}
                                            onChange={e => setData('fecha_reinicio', e.target.value)}
                                        />
                                        {showSuspensionReinicioPdf && (
                                            <div className="mt-3">
                                                <label className="form-label small mb-1">{hasCUI ? 'Adjuntar PDF (reinicio)' : 'Adjuntar documento (reinicio)'}</label>
                                                <input
                                                    type="file"
                                                    name="archivo_reinicio"
                                                    accept={hasCUI ? '.pdf' : FILE_ACCEPT}
                                                    className={`form-control form-control-sm ${errors.archivo_reinicio ? 'is-invalid' : ''}`}
                                                    onChange={e => setData('archivo_reinicio', e.target.files?.[0] || null)}
                                                />
                                                {method === 'PUT' && initialData.archivo_reinicio_url && (
                                                    <div className="mt-2 small">
                                                        <a href={initialData.archivo_reinicio_url} target="_blank" rel="noopener noreferrer" className="text-decoration-none">
                                                            Ver archivo actual <i className="bi bi-box-arrow-up-right ms-1"></i>
                                                        </a>
                                                    </div>
                                                )}
                                                {errors.archivo_reinicio && <div className="invalid-feedback d-block">{explainValidationError(errors.archivo_reinicio, `El archivo de reinicio excede el tamaño máximo permitido (${MAX_FILE_SIZE_MB} MB).`)}</div>}
                                            </div>
                                        )}
                                    </div>
                                )}
                            </div>
                        </div>
                    </>
                )}
                <>
                        <div className={isMunicipalidades ? 'col-md-4' : 'col-md-6'}>
                            <label className="form-label fw-medium">FECHA DE INICIO *</label>
                            <input
                                type="date"
                                className={`form-control ${errors.fecha_inicio ? 'is-invalid' : ''}`}
                                value={toInputDate(data.fecha_inicio)}
                                onChange={e => handleFechaChange('fecha_inicio', e.target.value)}
                                required
                            />
                            {errors.fecha_inicio && <div className="invalid-feedback">{errors.fecha_inicio}</div>}
                        </div>
                        <div className={isMunicipalidades ? 'col-md-4' : 'col-md-6'}>
                            <label className="form-label fw-medium">FECHA DE CULMINACION *</label>
                            <input
                                type="date"
                                className={`form-control ${errors.fecha_culminacion ? 'is-invalid' : ''}`}
                                value={toInputDate(data.fecha_culminacion)}
                                onChange={e => handleFechaChange('fecha_culminacion', e.target.value)}
                                required
                            />
                            {errors.fecha_culminacion && <div className="invalid-feedback">{errors.fecha_culminacion}</div>}
                        </div>
                        {isMunicipalidades && (
                            <div className="col-md-4">
                                <label className="form-label fw-medium">PLAZO</label>
                                <input type="text" className="form-control bg-light" value={plazoSum} readOnly />
                            </div>
                        )}
                        <div className="col-md-4">
                            <label className="form-label fw-medium">TOTAL DE MESES</label>
                            <input type="text" className="form-control bg-light" value={totalMeses != null ? Number(totalMeses).toFixed(2) : ''} readOnly />
                        </div>
                        <div className="col-md-4">
                            <label className="form-label fw-medium">TOTAL DE DIAS</label>
                            <input type="text" className="form-control bg-light" value={totalDias ?? ''} readOnly />
                        </div>
                        {!isProveedorBienes && !isProveedorServicios && !hasCUI && (
                            <>
                                <div className="col-md-4">
                                    <label className="form-label fw-medium">TRASLAPE</label>
                                    <input
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        className="form-control"
                                        value={data.traslape}
                                        onChange={e => handleTraslapeChange(e.target.value)}
                                    />
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label fw-medium">TOTAL DE DIAS SIN TRASLAPE</label>
                                    <input type="text" className="form-control bg-light" value={totalDiasSinTraslape != null ? `${totalDiasSinTraslape} Dias Calendario` : ''} readOnly />
                                </div>
                            </>
                        )}
                </>
                <div className="col-md-6">
                    <label className="form-label fw-medium">{isEspecialistasConEstadoContrato ? 'Monto Contratado *' : 'Monto Neto *'}</label>
                    <input
                        type="text"
                        placeholder="S/. 1,000.00"
                        className={`form-control ${errors.monto_neto ? 'is-invalid' : ''}`}
                        value={data.monto_neto}
                        onChange={e => setData('monto_neto', e.target.value)}
                        required
                    />
                    {errors.monto_neto && <div className="invalid-feedback">{errors.monto_neto}</div>}
                </div>
            </div>

            {useMultiDocumentos ? (
                <div className="row g-3 mt-2">
                    <div className="col-12 d-flex flex-wrap align-items-center justify-content-between gap-2">
                        <div>
                            <label className="form-label fw-medium mb-0">{isProveedorServicios ? 'Nuevos documentos' : 'Documentos'}</label>
                            <p className="small text-secondary mb-0">Agregue uno o más documentos. Si el tipo es &quot;Otros&quot;, indique el nombre del archivo.</p>
                        </div>
                    </div>
                    {documentosActualesList.length > 0 && (
                        <div className="col-12">
                            <div className="p-3 rounded border bg-light bg-opacity-50">
                                <div className="small fw-semibold mb-2">Documentos actuales</div>
                                <div className="d-flex flex-column gap-1">
                                    {documentosActualesList.map((doc) => (
                                        <div key={doc.id} className="d-flex flex-wrap align-items-center gap-2">
                                            <a href={doc.url} target="_blank" rel="noopener noreferrer" className="small text-decoration-none">
                                                {doc.nombre} <i className="bi bi-box-arrow-up-right ms-1"></i>
                                            </a>
                                            {allowDeleteExistingDocuments && (
                                                <button
                                                    type="button"
                                                    className="btn btn-outline-danger btn-sm py-0 px-2"
                                                    title="Quitar documento"
                                                    onClick={() => quitarDocumentoExistente(doc.id)}
                                                >
                                                    <i className="bi bi-trash"></i>
                                                </button>
                                            )}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>
                    )}
                    {hasCUI && method === 'PUT' && documentosActualesList.length === 0 && initialData.archivo_contrato_url && (
                        <div className="col-12">
                            <div className="p-3 rounded border bg-light bg-opacity-50">
                                <div className="small fw-semibold mb-2">Archivo principal actual</div>
                                <a href={initialData.archivo_contrato_url} target="_blank" rel="noopener noreferrer" className="small text-decoration-none">
                                    {(TIPOS_DOCUMENTO.find(t => t.value === initialData.tipo_documento_adjunto)?.label) || 'Documento adjunto'}
                                    <i className="bi bi-box-arrow-up-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    )}
                    {(data.documentos || []).map((doc, index) => (
                        <div key={index} className="col-12 border rounded-3 p-3 bg-light bg-opacity-50">
                            <div className="row g-2 align-items-end">
                                <div className="col-md-4">
                                    <label className="form-label small mb-1">Tipo de documento</label>
                                    <select
                                        className={`form-select form-select-sm ${errors[`documentos.${index}.tipo_documento_adjunto`] ? 'is-invalid' : ''}`}
                                        value={doc.tipo_documento_adjunto}
                                        onChange={e => updateDocumento(index, 'tipo_documento_adjunto', e.target.value)}
                                    >
                                        <option value="">Seleccione...</option>
                                        {TIPOS_DOCUMENTO.map(t => (
                                            <option key={t.value} value={t.value}>{t.label}</option>
                                        ))}
                                    </select>
                                    {errors[`documentos.${index}.tipo_documento_adjunto`] && <div className="invalid-feedback d-block">{errors[`documentos.${index}.tipo_documento_adjunto`]}</div>}
                                </div>
                                {doc.tipo_documento_adjunto === 'OTROS' && (
                                    <div className="col-md-3">
                                        <label className="form-label small mb-1">Nombre del documento *</label>
                                        <input
                                            type="text"
                                            className={`form-control form-control-sm ${errors[`documentos.${index}.nombre_otro`] ? 'is-invalid' : ''}`}
                                            placeholder="Ej. Acta de entrega"
                                            value={doc.nombre_otro}
                                            onChange={e => updateDocumento(index, 'nombre_otro', e.target.value)}
                                        />
                                        {errors[`documentos.${index}.nombre_otro`] && <div className="invalid-feedback d-block">{errors[`documentos.${index}.nombre_otro`]}</div>}
                                    </div>
                                )}
                                <div className="col-md-4">
                                    <label className="form-label small mb-1">Adjuntar archivo</label>
                                    <input
                                        type="file"
                                        accept={FILE_ACCEPT}
                                        className={`form-control form-control-sm ${errors[`documentos.${index}.archivo`] ? 'is-invalid' : ''}`}
                                        onChange={e => updateDocumento(index, 'archivo', e.target.files[0] || null)}
                                    />
                                    {errors[`documentos.${index}.archivo`] && <div className="invalid-feedback d-block">{explainValidationError(errors[`documentos.${index}.archivo`], `El archivo adjunto excede el tamaño máximo permitido (${MAX_FILE_SIZE_MB} MB).`)}</div>}
                                </div>
                                <div className="col-md-1 text-end">
                                    <button type="button" className="btn btn-outline-danger btn-sm" onClick={() => removeDocumentoRow(index)} title="Quitar" disabled={(data.documentos || []).length <= 1}>
                                        <i className="bi bi-dash-lg"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    ))}
                    <div className="col-12">
                        <button type="button" className="btn btn-outline-primary btn-sm rounded-pill" onClick={addDocumentoRow}>
                            <i className="bi bi-plus-lg me-1"></i> {isProveedorServicios ? 'Agregar más documento' : 'Agregar documento'}
                        </button>
                    </div>
                </div>
            ) : (
                <div className="row g-3 mt-2">
                    <div className="col-md-6">
                        <label className="form-label fw-medium">Tipo de documento</label>
                        <select
                            className={`form-select ${errors.tipo_documento_adjunto ? 'is-invalid' : ''}`}
                            value={data.tipo_documento_adjunto}
                            onChange={e => setData('tipo_documento_adjunto', e.target.value)}
                        >
                            <option value="">Seleccione...</option>
                            {TIPOS_DOCUMENTO.map(t => (
                                <option key={t.value} value={t.value}>{t.label}</option>
                            ))}
                        </select>
                        {errors.tipo_documento_adjunto && <div className="invalid-feedback">{errors.tipo_documento_adjunto}</div>}
                    </div>
                    <div className="col-md-6">
                        <label className="form-label fw-medium">Adjuntar archivo</label>
                        {method === 'PUT' && initialData.archivo_contrato_url && (
                            <div className="mb-2 p-2 rounded bg-light border border-opacity-25">
                                <span className="small fw-medium text-muted d-block mb-1">Archivo actual:</span>
                                <a href={initialData.archivo_contrato_url} target="_blank" rel="noopener noreferrer" className="text-decoration-none">
                                    {(TIPOS_DOCUMENTO.find(t => t.value === initialData.tipo_documento_adjunto)?.label) || 'Documento adjunto'}
                                    <i className="bi bi-box-arrow-up-right ms-1 small"></i>
                                </a>
                            </div>
                        )}
                        <input
                            type="file"
                            accept={FILE_ACCEPT}
                            className={`form-control ${errors.archivo_contrato ? 'is-invalid' : ''}`}
                            onChange={e => setData('archivo_contrato', e.target.files[0] || null)}
                        />
                        {method === 'PUT' && initialData.archivo_contrato_url && (
                            <div className="form-text">Dejar vacío para mantener el archivo actual, o elegir otro para reemplazarlo.</div>
                        )}
                        {errors.archivo_contrato && <div className="invalid-feedback">{explainValidationError(errors.archivo_contrato, `El archivo excede el tamaño máximo permitido (${MAX_FILE_SIZE_MB} MB).`)}</div>}
                    </div>
                </div>
            )}

            <div className="d-flex justify-content-end mt-4 pt-3 border-top gap-2">
                <a href={cancelUrl} className="btn btn-outline-secondary px-4 rounded-pill">Cancelar</a>
                <SubmitButton processing={processing} icon="bi-save" className="px-5 rounded-pill shadow-sm">Guardar</SubmitButton>
            </div>
        </form>
    );
}
