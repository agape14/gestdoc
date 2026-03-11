import React, { useMemo } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';
import { formatMonedaPeruana } from '@/Utils/experienciaCalculations';

function toInputDate(val) {
    if (!val) return '';
    const s = String(val).trim();
    const match = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (match) return match[0];
    const ddmmyy = s.match(/^(\d{1,2})[\/\-\.](\d{1,2})[\/\-\.](\d{4})$/);
    if (ddmmyy) {
        const [, d, m, y] = ddmmyy;
        return `${y}-${m.padStart(2, '0')}-${d.padStart(2, '0')}`;
    }
    return s;
}

export default function Edit({ expediente, opcionesTipoUnidad = [] }) {
    const { data, setData, put, processing, errors } = useForm({
        tipo_inversion: expediente?.tipo_inversion ?? '',
        numero: expediente?.numero ?? '',
        etiqueta: expediente?.etiqueta ?? '',
        proyecto: expediente?.proyecto ?? '',
        cui: expediente?.cui ?? '',
        descripcion: expediente?.descripcion ?? '',
        numero_folio: expediente?.numero_folio ?? '',
        tomos: expediente?.tomos ?? '',
        anio: expediente?.anio ?? '',
        tipo_unidad_conservacion: expediente?.tipo_unidad_conservacion ?? '',
        resolucion: expediente?.resolucion ?? '',
        fecha_aprobacion: toInputDate(expediente?.fecha_aprobacion) ?? '',
        monto_o: expediente?.monto_o ?? '',
        monto_p: expediente?.monto_p ?? '',
        monto_r: expediente?.monto_r ?? '',
        monto_s: expediente?.monto_s ?? '',
    });

    const totalMontos = useMemo(() => {
        const o = Number(data.monto_o) || 0;
        const p = Number(data.monto_p) || 0;
        const r = Number(data.monto_r) || 0;
        const s = Number(data.monto_s) || 0;
        return o + p + r + s;
    }, [data.monto_o, data.monto_p, data.monto_r, data.monto_s]);

    const cancelUrl = expediente?.folder_id
        ? route('registro-expedientes.index', { folder_id: expediente.folder_id })
        : route('registro-expedientes.index');

    const submit = (e) => {
        e.preventDefault();
        const payload = { ...data };
        ['monto_o', 'monto_p', 'monto_r', 'monto_s'].forEach(k => {
            if (payload[k] === '' || payload[k] == null) payload[k] = null;
            else payload[k] = Number(payload[k]) || 0;
        });
        if (payload.anio === '') payload.anio = null;
        else payload.anio = parseInt(payload.anio, 10) || null;
        put(route('registro-expedientes.update', expediente.id), payload);
    };

    return (
        <MainLayout>
            <Head title="Editar expediente - Registro de Expedientes" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body form-card-responsive" style={{ maxWidth: '900px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Editar Registro de Expediente</h3>
                    <p className="text-secondary small mb-0">El total de montos se calcula automáticamente (O + P + R + S).</p>
                </div>
                <form onSubmit={submit}>
                    <div className="row g-3 mb-4">
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Tipo de inversión</label>
                            <input type="text" className={`form-control ${errors.tipo_inversion ? 'is-invalid' : ''}`} value={data.tipo_inversion} onChange={e => setData('tipo_inversion', e.target.value)} />
                            {errors.tipo_inversion && <div className="invalid-feedback">{errors.tipo_inversion}</div>}
                        </div>
                        <div className="col-md-3">
                            <label className="form-label fw-medium">N°</label>
                            <input type="text" className={`form-control ${errors.numero ? 'is-invalid' : ''}`} value={data.numero} onChange={e => setData('numero', e.target.value)} />
                            {errors.numero && <div className="invalid-feedback">{errors.numero}</div>}
                        </div>
                        <div className="col-md-3">
                            <label className="form-label fw-medium">Etiqueta</label>
                            <input type="text" className={`form-control ${errors.etiqueta ? 'is-invalid' : ''}`} value={data.etiqueta} onChange={e => setData('etiqueta', e.target.value)} />
                            {errors.etiqueta && <div className="invalid-feedback">{errors.etiqueta}</div>}
                        </div>
                        <div className="col-12">
                            <label className="form-label fw-medium">Proyecto</label>
                            <textarea className={`form-control ${errors.proyecto ? 'is-invalid' : ''}`} rows={3} value={data.proyecto} onChange={e => setData('proyecto', e.target.value)} />
                            {errors.proyecto && <div className="invalid-feedback">{errors.proyecto}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">CUI</label>
                            <input type="text" className={`form-control ${errors.cui ? 'is-invalid' : ''}`} value={data.cui} onChange={e => setData('cui', e.target.value)} />
                            {errors.cui && <div className="invalid-feedback">{errors.cui}</div>}
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-medium">Descripción</label>
                            <input type="text" className={`form-control ${errors.descripcion ? 'is-invalid' : ''}`} value={data.descripcion} onChange={e => setData('descripcion', e.target.value)} />
                            {errors.descripcion && <div className="invalid-feedback">{errors.descripcion}</div>}
                        </div>
                        <div className="col-md-4">
                            <label className="form-label fw-medium">N° de folio</label>
                            <input type="text" className={`form-control ${errors.numero_folio ? 'is-invalid' : ''}`} value={data.numero_folio} onChange={e => setData('numero_folio', e.target.value)} />
                            {errors.numero_folio && <div className="invalid-feedback">{errors.numero_folio}</div>}
                        </div>
                        <div className="col-md-4">
                            <label className="form-label fw-medium">Tomos</label>
                            <input type="text" className={`form-control ${errors.tomos ? 'is-invalid' : ''}`} value={data.tomos} onChange={e => setData('tomos', e.target.value)} />
                            {errors.tomos && <div className="invalid-feedback">{errors.tomos}</div>}
                        </div>
                        <div className="col-md-4">
                            <label className="form-label fw-medium">Año</label>
                            <input type="number" className={`form-control ${errors.anio ? 'is-invalid' : ''}`} value={data.anio} onChange={e => setData('anio', e.target.value)} min="1900" max="2100" />
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
                            {errors.tipo_unidad_conservacion && <div className="invalid-feedback">{errors.tipo_unidad_conservacion}</div>}
                        </div>
                        <div className="col-md-3">
                            <label className="form-label fw-medium">Resolución</label>
                            <input type="text" className={`form-control ${errors.resolucion ? 'is-invalid' : ''}`} value={data.resolucion} onChange={e => setData('resolucion', e.target.value)} />
                            {errors.resolucion && <div className="invalid-feedback">{errors.resolucion}</div>}
                        </div>
                        <div className="col-md-3">
                            <label className="form-label fw-medium">Fecha de aprobación</label>
                            <input type="date" className={`form-control ${errors.fecha_aprobacion ? 'is-invalid' : ''}`} value={data.fecha_aprobacion} onChange={e => setData('fecha_aprobacion', e.target.value)} />
                            {errors.fecha_aprobacion && <div className="invalid-feedback">{errors.fecha_aprobacion}</div>}
                        </div>

                        <div className="col-12 mt-3">
                            <h6 className="fw-bold text-body mb-2">Montos (componentes)</h6>
                        </div>
                        <div className="col-md-3">
                            <label className="form-label fw-medium">Monto O (S/)</label>
                            <input type="number" step="0.01" min="0" className={`form-control ${errors.monto_o ? 'is-invalid' : ''}`} value={data.monto_o} onChange={e => setData('monto_o', e.target.value)} />
                            {errors.monto_o && <div className="invalid-feedback">{errors.monto_o}</div>}
                        </div>
                        <div className="col-md-3">
                            <label className="form-label fw-medium">Monto P (S/)</label>
                            <input type="number" step="0.01" min="0" className={`form-control ${errors.monto_p ? 'is-invalid' : ''}`} value={data.monto_p} onChange={e => setData('monto_p', e.target.value)} />
                            {errors.monto_p && <div className="invalid-feedback">{errors.monto_p}</div>}
                        </div>
                        <div className="col-md-3">
                            <label className="form-label fw-medium">Monto R (S/)</label>
                            <input type="number" step="0.01" min="0" className={`form-control ${errors.monto_r ? 'is-invalid' : ''}`} value={data.monto_r} onChange={e => setData('monto_r', e.target.value)} />
                            {errors.monto_r && <div className="invalid-feedback">{errors.monto_r}</div>}
                        </div>
                        <div className="col-md-3">
                            <label className="form-label fw-medium">Monto S (S/)</label>
                            <input type="number" step="0.01" min="0" className={`form-control ${errors.monto_s ? 'is-invalid' : ''}`} value={data.monto_s} onChange={e => setData('monto_s', e.target.value)} />
                            {errors.monto_s && <div className="invalid-feedback">{errors.monto_s}</div>}
                        </div>
                        <div className="col-12">
                            <div className="p-3 bg-body-tertiary rounded-3">
                                <strong>Total montos:</strong> {formatMonedaPeruana(totalMontos)}
                            </div>
                        </div>
                    </div>
                    <div className="d-flex justify-content-end mt-4 pt-3 border-top gap-2">
                        <Link href={cancelUrl} className="btn btn-outline-secondary px-4 rounded-pill">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-save" className="px-5 rounded-pill shadow-sm">Actualizar</SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
