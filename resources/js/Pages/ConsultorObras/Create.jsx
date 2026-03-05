import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Create({ folderId = null, breadcrumbLabel = '' }) {
    const { data, setData, post, processing, errors } = useForm({
        titulo: '',
        entidad: '',
        categoria: 'Privada',
        especialidad: '',
        tipo_servicio: '',
        presupuesto: '',
        estado: 'En Curso',
        duracion: '',
        modalidad: '',
        clasificacion: breadcrumbLabel || '',
        folder_id: folderId || '',
        objeto_contrato: '',
        cui: '',
        numero_contrato_os_comprobante: '',
        fecha_contrato_cp: '',
        fecha_conformidad: '',
        experiencia_proveniente_de: '',
        moneda: 'Soles',
        monto_contratado: '',
        consorciado: false,
        porcentaje_participacion: '',
        importe: '',
        tipo_cambio_venta: '',
        monto_facturado_acumulado: '',
        numero_resolucion: '',
        fecha_aprobacion: '',
        documentos: [
            { nombre: 'Contrato', archivo: null },
            { nombre: 'TDR', archivo: null },
        ],
    });

    const addDocumento = () => {
        setData('documentos', [...(data.documentos || []), { nombre: '', archivo: null }]);
    };

    const removeDocumento = (index) => {
        const list = data.documentos ? [...data.documentos] : [];
        list.splice(index, 1);
        if (list.length === 0) list.push({ nombre: '', archivo: null });
        setData('documentos', list);
    };

    const handleDocumentoChange = (index, field, value) => {
        const list = [...(data.documentos || [])];
        if (!list[index]) list[index] = { nombre: '', archivo: null };
        list[index][field] = value;
        setData('documentos', list);
    };

    const cancelUrl = folderId ? route('consultor-obras.index', { folder_id: folderId }) : route('consultor-obras.index');

    const submit = (e) => {
        e.preventDefault();
        post(route('consultor-obras.store'), { forceFormData: true });
    };

    return (
        <MainLayout>
            <Head title="Nuevo Registro - Consultor de Obras" />

            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '1000px', margin: '0 auto' }}>
                <form onSubmit={submit} className="p-4 bg-white rounded-4 shadow-sm">
                    <div className="d-flex justify-content-between align-items-center mb-4">
                        <h5 className="fw-bold mb-0">Detalle de Consultoría</h5>
                        <Link href={cancelUrl} className="btn-close" aria-label="Cerrar"></Link>
                    </div>

                    {folderId && <input type="hidden" name="folder_id" value={folderId} />}

                    <div className="row g-3 mb-3">
                        <div className="col-md-12">
                            <label className="form-label fw-bold small text-secondary">Proyecto / Título</label>
                            <textarea className={`form-control ${errors.titulo ? 'is-invalid' : ''}`} rows={3} value={data.titulo} onChange={e => setData('titulo', e.target.value)} />
                            {errors.titulo && <div className="invalid-feedback">{errors.titulo}</div>}
                        </div>
                        <div className="col-md-12">
                            <label className="form-label fw-bold small text-secondary">CLIENTE</label>
                            <input type="text" className={`form-control ${errors.entidad ? 'is-invalid' : ''}`} value={data.entidad} onChange={e => setData('entidad', e.target.value)} placeholder="Entidad cliente" />
                            {errors.entidad && <div className="invalid-feedback">{errors.entidad}</div>}
                        </div>
                        <div className="col-md-12">
                            <label className="form-label fw-bold small text-secondary">OBJETO DE CONTRATO</label>
                            <textarea className="form-control" rows={2} value={data.objeto_contrato || ''} onChange={e => setData('objeto_contrato', e.target.value)} placeholder="Descripción del objeto del contrato" />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">CUI</label>
                            <input type="text" className="form-control" value={data.cui || ''} onChange={e => setData('cui', e.target.value)} placeholder="Código único" />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">N° CONTRATO / O/S / COMPROBANTE DE PAGO</label>
                            <input type="text" className="form-control" value={data.numero_contrato_os_comprobante || ''} onChange={e => setData('numero_contrato_os_comprobante', e.target.value)} placeholder="Ej: 21/2018, S/N" />
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
                                    <label className="form-label fw-bold small text-secondary">IMPORTE</label>
                                    <div className="input-group">
                                        <span className="input-group-text">{data.moneda === 'Dólares' ? 'US$' : 'S/'}</span>
                                        <input type="number" step="0.01" className="form-control" value={data.monto_contratado || ''} onChange={e => {
                                            const val = e.target.value;
                                            setData('monto_contratado', val);
                                            const m = parseFloat(val) || 0;
                                            const p = parseFloat(data.porcentaje_participacion) || 0;
                                            setData('importe', (m * p / 100) || '');
                                        }} />
                                    </div>
                                </div>
                                <div className="col-md-6">
                                    <label className="form-label fw-bold small text-secondary">% DE PARTICIPACIÓN</label>
                                    <div className="input-group">
                                        <input type="number" step="0.01" min="0" max="100" className="form-control" value={data.porcentaje_participacion || ''} onChange={e => {
                                            const val = e.target.value;
                                            setData('porcentaje_participacion', val);
                                            const m = parseFloat(data.monto_contratado) || 0;
                                            const p = parseFloat(val) || 0;
                                            setData('importe', (m * p / 100) || '');
                                        }} />
                                        <span className="input-group-text">%</span>
                                    </div>
                                </div>
                            </>
                        )}
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">IMPORTE</label>
                            <div className="input-group">
                                <span className="input-group-text">{data.moneda === 'Dólares' ? 'US$' : 'S/'}</span>
                                <input type="number" step="0.01" className="form-control" value={data.importe || ''} onChange={e => setData('importe', e.target.value)} readOnly={data.consorciado} />
                            </div>
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">TIPO DE CAMBIO VENTA</label>
                            <input type="number" step="0.0001" className="form-control" value={data.tipo_cambio_venta || ''} onChange={e => setData('tipo_cambio_venta', e.target.value)} />
                        </div>
                        <div className="col-md-6">
                            <label className="form-label fw-bold small text-secondary">N° DE RESOLUCIÓN</label>
                            <input type="text" className="form-control" value={data.numero_resolucion || ''} onChange={e => setData('numero_resolucion', e.target.value)} placeholder="Ej: 185-2025" />
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
                            <label className="form-label fw-bold small text-secondary">Tipo / Clasificación (miga de pan)</label>
                            <input type="text" className="form-control bg-light" value={data.clasificacion || ''} readOnly />
                        </div>
                    </div>

                    <hr className="my-3" />

                    <div className="mb-3">
                        <label className="form-label fw-bold small text-secondary">Documentación (nombre + archivo)</label>
                        <p className="text-secondary small mb-2">Cada documento con nombre y archivo. Use &quot;Agregar más documento&quot; para añadir.</p>
                        {(data.documentos || []).map((doc, index) => (
                            <div key={index} className="row g-2 align-items-end mb-2 p-2 bg-body-tertiary rounded">
                                <div className="col-md-4">
                                    <input type="text" className="form-control form-control-sm" placeholder="Nombre" value={doc.nombre || ''} onChange={e => handleDocumentoChange(index, 'nombre', e.target.value)} />
                                </div>
                                <div className="col-md-6">
                                    <input type="file" className="form-control form-control-sm" accept=".pdf,.doc,.docx,image/*" onChange={e => handleDocumentoChange(index, 'archivo', e.target.files[0] || null)} />
                                    {doc.archivo && typeof doc.archivo === 'object' && doc.archivo.name && <small className="text-success">{doc.archivo.name}</small>}
                                </div>
                                <div className="col-md-2">
                                    <button type="button" className="btn btn-outline-danger btn-sm w-100" onClick={() => removeDocumento(index)} title="Eliminar"><i className="bi bi-trash"></i></button>
                                </div>
                            </div>
                        ))}
                        <button type="button" className="btn btn-sm btn-outline-primary" onClick={addDocumento}>
                            <i className="bi bi-plus-lg me-1"></i> Agregar más documento
                        </button>
                    </div>

                    <div className="d-flex justify-content-end gap-2">
                        <Link href={cancelUrl} className="btn btn-outline-secondary">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-save" className="btn btn-primary">Guardar</SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
