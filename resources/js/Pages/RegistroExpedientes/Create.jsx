import React, { useMemo } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';
import { formatMonedaPeruana } from '@/Utils/experienciaCalculations';

const OPCIONES_SI_NO = [{ value: 'SI', label: 'SI' }, { value: 'NO', label: 'NO' }];

export default function Create({ folderId = null, breadcrumbLabel = '', opcionesTipoUnidad = [], opcionesTipoInversion = [], nextNumero = '1', prefillProyecto = '', prefillCui = '' }) {
    const { data, setData, post, processing, errors } = useForm({
        folder_id: folderId || '',
        tipo_inversion: '',
        numero: nextNumero,
        etiqueta: '',
        proyecto: prefillProyecto || '',
        cui: prefillCui || '',
        descripcion: '',
        numero_folio: '',
        tomos: '',
        anio: '',
        tipo_unidad_conservacion: '',
        resolucion: '',
        fecha_aprobacion: '',
        tiene_actualizacion_precios: '',
        tiene_reformulacion: '',
        monto_o: '',
        monto_p: '',
        monto_s: '',
        monto_supervision: '',
        contrato: null,
        resolucion_archivo: null,
        tuvo_suspension: '',
        fecha_suspension: '',
        acta_suspension: null,
        fecha_reinicio: '',
        acta_reinicio: null,
    });

    const totalMontos = useMemo(() => {
        const o = Number(data.monto_o) || 0;
        const p = Number(data.monto_p) || 0;
        const s = Number(data.monto_s) || 0;
        const sup = Number(data.monto_supervision) || 0;
        return o + p + s + sup;
    }, [data.monto_o, data.monto_p, data.monto_s, data.monto_supervision]);

    const cancelUrl = folderId ? route('registro-expedientes.index', { folder_id: folderId }) : route('registro-expedientes.index');

    const submit = (e) => {
        e.preventDefault();
        const payload = { ...data };
        if (payload.folder_id === '') payload.folder_id = null;
        payload.numero = nextNumero;
        ['monto_o', 'monto_p', 'monto_s', 'monto_supervision'].forEach(k => {
            if (payload[k] === '' || payload[k] == null) payload[k] = null;
            else payload[k] = Number(payload[k]) || 0;
        });
        if (payload.anio === '') payload.anio = null;
        else payload.anio = parseInt(payload.anio, 10) || null;
        if (!payload.contrato || !(payload.contrato instanceof File)) delete payload.contrato;
        if (!payload.resolucion_archivo || !(payload.resolucion_archivo instanceof File)) delete payload.resolucion_archivo;
        if (!payload.acta_suspension || !(payload.acta_suspension instanceof File)) delete payload.acta_suspension;
        if (!payload.acta_reinicio || !(payload.acta_reinicio instanceof File)) delete payload.acta_reinicio;
        post(route('registro-expedientes.store'), payload);
    };

    return (
        <MainLayout>
            <Head title="Nuevo expediente - Registro de Expedientes" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body form-card-responsive" style={{ maxWidth: '900px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Nuevo Registro de Expediente</h3>
                    <p className="text-secondary small mb-0">Complete los campos. El total de montos se calcula automáticamente (O + P + S + Supervisión). Si marca «¿TUVO ACTUALIZACIÓN DE PRECIOS?» = SI, al guardar podrá agregar otro registro con el mismo proyecto y CUI.</p>
                </div>
                <form onSubmit={submit}>
                    <div className="row g-3 mb-4">
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Tipo de inversión</label>
                            <select className={`form-select ${errors.tipo_inversion ? 'is-invalid' : ''}`} value={data.tipo_inversion} onChange={e => setData('tipo_inversion', e.target.value)}>
                                <option value="">Seleccione...</option>
                                {opcionesTipoInversion.map((opt, i) => (
                                    <option key={i} value={opt}>{opt}</option>
                                ))}
                            </select>
                            {errors.tipo_inversion && <div className="invalid-feedback">{errors.tipo_inversion}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Etiqueta</label>
                            <input type="text" className={`form-control ${errors.etiqueta ? 'is-invalid' : ''}`} value={data.etiqueta} onChange={e => setData('etiqueta', e.target.value)} placeholder="Orden de listado por etiqueta" />
                            {errors.etiqueta && <div className="invalid-feedback">{errors.etiqueta}</div>}
                        </div>
                        <div className="col-12">
                            <label className="form-label fw-medium">Proyecto</label>
                            <textarea className={`form-control ${errors.proyecto ? 'is-invalid' : ''}`} rows={3} value={data.proyecto} onChange={e => setData('proyecto', e.target.value)} placeholder="Descripción del proyecto" />
                            {errors.proyecto && <div className="invalid-feedback">{errors.proyecto}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">CUI</label>
                            <input type="text" className={`form-control ${errors.cui ? 'is-invalid' : ''}`} value={data.cui} onChange={e => setData('cui', e.target.value)} />
                            {errors.cui && <div className="invalid-feedback">{errors.cui}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Descripción</label>
                            <input type="text" className={`form-control ${errors.descripcion ? 'is-invalid' : ''}`} value={data.descripcion} onChange={e => setData('descripcion', e.target.value)} placeholder="Ej. EXPEDIENTE TECNICO" />
                            {errors.descripcion && <div className="invalid-feedback">{errors.descripcion}</div>}
                        </div>
                        <div className="col-md-4">
                            <label className="form-label fw-medium">N° de folio</label>
                            <input type="text" className={`form-control ${errors.numero_folio ? 'is-invalid' : ''}`} value={data.numero_folio} onChange={e => setData('numero_folio', e.target.value)} />
                            {errors.numero_folio && <div className="invalid-feedback">{errors.numero_folio}</div>}
                        </div>
                        <div className="col-md-4">
                            <label className="form-label fw-medium">Tomos</label>
                            <input type="text" className={`form-control ${errors.tomos ? 'is-invalid' : ''}`} value={data.tomos} onChange={e => setData('tomos', e.target.value)} placeholder="Ej. TOMO I, TOMO II + COPIA" />
                            {errors.tomos && <div className="invalid-feedback">{errors.tomos}</div>}
                        </div>
                        <div className="col-md-4">
                            <label className="form-label fw-medium">Año</label>
                            <input type="number" className={`form-control ${errors.anio ? 'is-invalid' : ''}`} value={data.anio} onChange={e => setData('anio', e.target.value)} min="1900" max="2100" placeholder="Ej. 2024" />
                            {errors.anio && <div className="invalid-feedback">{errors.anio}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Tipo de unidades de conservación</label>
                            <select className={`form-select ${errors.tipo_unidad_conservacion ? 'is-invalid' : ''}`} value={data.tipo_unidad_conservacion} onChange={e => setData('tipo_unidad_conservacion', e.target.value)}>
                                <option value="">Seleccione...</option>
                                {opcionesTipoUnidad.map((opt, i) => (
                                    <option key={i} value={opt}>{opt}</option>
                                ))}
                            </select>
                            <small className="text-muted">Archivadores de Palanca, Paquetes, Empastados, Folderes, Cajas Archivadoras, Archivo Digital u otro.</small>
                            {errors.tipo_unidad_conservacion && <div className="invalid-feedback">{errors.tipo_unidad_conservacion}</div>}
                        </div>
                        <div className="col-md-3">
                            <label className="form-label fw-medium">Resolución</label>
                            <input type="text" className={`form-control ${errors.resolucion ? 'is-invalid' : ''}`} value={data.resolucion} onChange={e => setData('resolucion', e.target.value)} placeholder="Ej. 151-2023" />
                            {errors.resolucion && <div className="invalid-feedback">{errors.resolucion}</div>}
                        </div>
                        <div className="col-md-3">
                            <label className="form-label fw-medium">Fecha de aprobación</label>
                            <input type="date" className={`form-control ${errors.fecha_aprobacion ? 'is-invalid' : ''}`} value={data.fecha_aprobacion} onChange={e => setData('fecha_aprobacion', e.target.value)} />
                            {errors.fecha_aprobacion && <div className="invalid-feedback">{errors.fecha_aprobacion}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">¿TUVO ACTUALIZACIÓN DE PRECIOS?</label>
                            <select className={`form-select ${errors.tiene_actualizacion_precios ? 'is-invalid' : ''}`} value={data.tiene_actualizacion_precios} onChange={e => setData('tiene_actualizacion_precios', e.target.value)}>
                                <option value="">Seleccione...</option>
                                {OPCIONES_SI_NO.map((opt) => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </select>
                            {errors.tiene_actualizacion_precios && <div className="invalid-feedback">{errors.tiene_actualizacion_precios}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">¿TUVO REFORMULACIÓN?</label>
                            <select className={`form-select ${errors.tiene_reformulacion ? 'is-invalid' : ''}`} value={data.tiene_reformulacion} onChange={e => setData('tiene_reformulacion', e.target.value)}>
                                <option value="">Seleccione...</option>
                                {OPCIONES_SI_NO.map((opt) => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </select>
                            {errors.tiene_reformulacion && <div className="invalid-feedback">{errors.tiene_reformulacion}</div>}
                        </div>

                        <div className="col-12 mt-3">
                            <h6 className="fw-bold text-body mb-2">Documentos</h6>
                            <div className="table-responsive">
                                <table className="table table-bordered bg-body mb-0">
                                    <thead>
                                        <tr>
                                            <th className="fw-medium">Tipo de documento</th>
                                            <th className="fw-medium">Adjuntar archivo</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td className="align-middle">Contrato</td>
                                            <td>
                                                <input type="file" className={`form-control form-control-sm ${errors.contrato ? 'is-invalid' : ''}`} accept=".pdf,.doc,.docx" onChange={e => setData('contrato', e.target.files?.[0] || null)} />
                                                <small className="text-muted">PDF o documento (máx. 25 MB)</small>
                                                {errors.contrato && <div className="invalid-feedback d-block">{errors.contrato}</div>}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td className="align-middle">Resolución</td>
                                            <td>
                                                <input type="file" className={`form-control form-control-sm ${errors.resolucion_archivo ? 'is-invalid' : ''}`} accept=".pdf,.doc,.docx" onChange={e => setData('resolucion_archivo', e.target.files?.[0] || null)} />
                                                <small className="text-muted">PDF o documento (máx. 25 MB)</small>
                                                {errors.resolucion_archivo && <div className="invalid-feedback d-block">{errors.resolucion_archivo}</div>}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div className="col-md-6">
                            <label className="form-label fw-medium">¿Tuvo suspensión?</label>
                            <select className={`form-select ${errors.tuvo_suspension ? 'is-invalid' : ''}`} value={data.tuvo_suspension} onChange={e => setData('tuvo_suspension', e.target.value)}>
                                <option value="">Seleccione...</option>
                                {OPCIONES_SI_NO.map((opt) => (
                                    <option key={opt.value} value={opt.value}>{opt.label}</option>
                                ))}
                            </select>
                            {errors.tuvo_suspension && <div className="invalid-feedback">{errors.tuvo_suspension}</div>}
                        </div>

                        {data.tuvo_suspension === 'SI' && (
                            <>
                                <div className="col-md-6">
                                    <label className="form-label fw-medium">FECHA DE SUSPENSIÓN *</label>
                                    <input type="date" className={`form-control ${errors.fecha_suspension ? 'is-invalid' : ''}`} value={data.fecha_suspension} onChange={e => setData('fecha_suspension', e.target.value)} />
                                    {errors.fecha_suspension && <div className="invalid-feedback">{errors.fecha_suspension}</div>}
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label fw-medium">Subir Acta de Suspensión de Obra (PDF) *</label>
                                    <input type="file" className={`form-control ${errors.acta_suspension ? 'is-invalid' : ''}`} accept=".pdf" onChange={e => setData('acta_suspension', e.target.files?.[0] || null)} />
                                    {errors.acta_suspension && <div className="invalid-feedback">{errors.acta_suspension}</div>}
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label fw-medium">FECHA DE REINICIO *</label>
                                    <input type="date" className={`form-control ${errors.fecha_reinicio ? 'is-invalid' : ''}`} value={data.fecha_reinicio} onChange={e => setData('fecha_reinicio', e.target.value)} />
                                    {errors.fecha_reinicio && <div className="invalid-feedback">{errors.fecha_reinicio}</div>}
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label fw-medium">Subir Acta de Reinicio de Obra (PDF) *</label>
                                    <input type="file" className={`form-control ${errors.acta_reinicio ? 'is-invalid' : ''}`} accept=".pdf" onChange={e => setData('acta_reinicio', e.target.files?.[0] || null)} />
                                    {errors.acta_reinicio && <div className="invalid-feedback">{errors.acta_reinicio}</div>}
                                </div>
                            </>
                        )}

                        <div className="col-12 mt-3">
                            <h6 className="fw-bold text-body mb-2">Montos (componentes)</h6>
                            <p className="small text-secondary mb-2">Ingrese los componentes. El total se calcula automáticamente (sin REFORMULACIÓN).</p>
                        </div>
                        <div className="col-md-4 col-lg">
                            <label className="form-label fw-medium">EXPEDIENTE TECNICO (S/)</label>
                            <input type="number" step="0.01" min="0" className={`form-control ${errors.monto_o ? 'is-invalid' : ''}`} value={data.monto_o} onChange={e => setData('monto_o', e.target.value)} />
                            {errors.monto_o && <div className="invalid-feedback">{errors.monto_o}</div>}
                        </div>
                        <div className="col-md-4 col-lg">
                            <label className="form-label fw-medium">EVALUACION (S/)</label>
                            <input type="number" step="0.01" min="0" className={`form-control ${errors.monto_p ? 'is-invalid' : ''}`} value={data.monto_p} onChange={e => setData('monto_p', e.target.value)} />
                            {errors.monto_p && <div className="invalid-feedback">{errors.monto_p}</div>}
                        </div>
                        <div className="col-md-4 col-lg">
                            <label className="form-label fw-medium">PPTO DE OBRA (S/)</label>
                            <input type="number" step="0.01" min="0" className={`form-control ${errors.monto_s ? 'is-invalid' : ''}`} value={data.monto_s} onChange={e => setData('monto_s', e.target.value)} />
                            {errors.monto_s && <div className="invalid-feedback">{errors.monto_s}</div>}
                        </div>
                        <div className="col-md-4 col-lg">
                            <label className="form-label fw-medium">SUPERVISION (S/)</label>
                            <input type="number" step="0.01" min="0" className={`form-control ${errors.monto_supervision ? 'is-invalid' : ''}`} value={data.monto_supervision} onChange={e => setData('monto_supervision', e.target.value)} />
                            {errors.monto_supervision && <div className="invalid-feedback">{errors.monto_supervision}</div>}
                        </div>
                        <div className="col-12">
                            <div className="p-3 bg-body-tertiary rounded-3">
                                <strong>Total montos:</strong> {formatMonedaPeruana(totalMontos)}
                            </div>
                        </div>
                    </div>
                    <div className="d-flex justify-content-end mt-4 pt-3 border-top gap-2">
                        <Link href={cancelUrl} className="btn btn-outline-secondary px-4 rounded-pill">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-save" className="px-5 rounded-pill shadow-sm">Guardar</SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
