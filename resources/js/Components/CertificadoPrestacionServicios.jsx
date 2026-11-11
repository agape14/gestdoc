import React from 'react';

const TIPOS = [
    { value: 'valorizacion', label: 'Valorización' },
    { value: 'suspension', label: 'Suspensión' },
];

const parseDate = (value) => {
    if (!value) return null;
    const [y, m, d] = String(value).split('-').map(Number);
    if (!y || !m || !d) return null;
    const date = new Date(y, m - 1, d);
    return Number.isNaN(date.getTime()) ? null : date;
};

const countDays = (start, end) => {
    const from = parseDate(start);
    const to = parseDate(end);
    if (!from || !to) return null;
    const diff = Math.floor((to.getTime() - from.getTime()) / 86400000) + 1;
    return diff > 0 ? diff : 0;
};

const emptyRow = (numero = '') => ({
    tipo: 'valorizacion',
    numero,
    fecha_inicio: '',
    fecha_fin: '',
    dias: '',
});

const nextNumero = (rows, tipo) => {
    const used = rows
        .filter((r) => (r.tipo || 'valorizacion') === tipo)
        .map((r) => parseInt(r.numero, 10))
        .filter((n) => !Number.isNaN(n));
    return String((used.length ? Math.max(...used) : 0) + 1);
};

export default function CertificadoPrestacionServicios({ periodos = [], onChange }) {
    const rows = Array.isArray(periodos) ? periodos : [];

    const updateRow = (index, patch) => {
        const next = rows.map((row, i) => {
            if (i !== index) return row;
            const merged = { ...row, ...patch };
            if ('fecha_inicio' in patch || 'fecha_fin' in patch) {
                const dias = countDays(merged.fecha_inicio, merged.fecha_fin);
                if (dias !== null) merged.dias = String(dias);
            }
            return merged;
        });
        onChange(next);
    };

    const addRow = () => {
        const tipo = 'valorizacion';
        onChange([...rows, emptyRow(nextNumero(rows, tipo))]);
    };

    const addSuspension = () => {
        const tipo = 'suspension';
        onChange([...rows, { ...emptyRow(nextNumero(rows, tipo)), tipo }]);
    };

    const removeRow = (index) => onChange(rows.filter((_, i) => i !== index));

    const totales = rows.reduce(
        (acc, row) => {
            const dias = Number(row.dias) || 0;
            acc.calendario += dias;
            if ((row.tipo || 'valorizacion') === 'suspension') acc.suspension += dias;
            return acc;
        },
        { calendario: 0, suspension: 0 }
    );
    const efectivos = Math.max(0, totales.calendario - totales.suspension);

    return (
        <div className="border rounded-3 bg-light-subtle p-3 mt-2">
            <div className="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-2">
                <div className="fw-semibold">
                    <i className="bi bi-calendar3 me-1"></i>
                    Periodos del certificado
                </div>
                <div className="d-flex gap-2">
                    <button type="button" className="btn btn-outline-primary btn-sm" onClick={addRow}>
                        <i className="bi bi-plus me-1"></i> Valorización
                    </button>
                    <button type="button" className="btn btn-outline-warning btn-sm" onClick={addSuspension}>
                        <i className="bi bi-pause-circle me-1"></i> Suspensión
                    </button>
                </div>
            </div>

            {rows.length === 0 ? (
                <div className="text-secondary small">
                    Añada al menos un periodo de valorización o suspensión.
                </div>
            ) : (
                <>
                <div className="small text-secondary mb-1">
                    Los días calendario (D.C.) se calculan automáticamente según las fechas y pueden editarse manualmente.
                </div>
                <div className="table-responsive">
                    <table className="table table-sm align-middle mb-2">
                        <thead>
                            <tr className="small text-secondary">
                                <th style={{ minWidth: '150px' }}>Tipo</th>
                                <th style={{ width: '90px' }}>N°</th>
                                <th style={{ minWidth: '130px' }}>Fecha inicio</th>
                                <th style={{ minWidth: '130px' }}>Fecha fin</th>
                                <th style={{ width: '110px' }} title="Días calendario reales (puede editarlos)">
                                    D.C.
                                </th>
                                <th style={{ width: '50px' }}></th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row, index) => (
                                <tr key={index}>
                                    <td>
                                        <select
                                            className="form-select form-select-sm"
                                            value={row.tipo || 'valorizacion'}
                                            onChange={(e) => {
                                                const tipo = e.target.value;
                                                updateRow(index, {
                                                    tipo,
                                                    numero: row.numero || nextNumero(rows.filter((_, i) => i !== index), tipo),
                                                });
                                            }}
                                        >
                                            {TIPOS.map((t) => (
                                                <option key={t.value} value={t.value}>{t.label}</option>
                                            ))}
                                        </select>
                                    </td>
                                    <td>
                                        <input
                                            type="text"
                                            className="form-control form-control-sm text-center"
                                            value={row.numero || ''}
                                            onChange={(e) => updateRow(index, { numero: e.target.value })}
                                            placeholder="1"
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="date"
                                            className="form-control form-control-sm"
                                            value={row.fecha_inicio || ''}
                                            onChange={(e) => updateRow(index, { fecha_inicio: e.target.value })}
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="date"
                                            className="form-control form-control-sm"
                                            value={row.fecha_fin || ''}
                                            onChange={(e) => updateRow(index, { fecha_fin: e.target.value })}
                                        />
                                    </td>
                                    <td>
                                        <input
                                            type="number"
                                            min="0"
                                            className="form-control form-control-sm text-center"
                                            value={row.dias ?? ''}
                                            onChange={(e) => updateRow(index, { dias: e.target.value })}
                                            title="Días calendario (D.C.)"
                                        />
                                    </td>
                                    <td className="text-end">
                                        <button
                                            type="button"
                                            className="btn btn-outline-danger btn-sm"
                                            onClick={() => removeRow(index)}
                                            title="Quitar periodo"
                                        >
                                            <i className="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
                </>
            )}

            <div className="row g-2 small">
                <div className="col-md-4">
                    <div className="border rounded-3 bg-body p-2 h-100">
                        <div className="text-secondary">Total días calendario</div>
                        <div className="fw-bold fs-5">{totales.calendario}</div>
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="border rounded-3 bg-body p-2 h-100">
                        <div className="text-secondary">Total días de suspensión</div>
                        <div className="fw-bold fs-5 text-warning">{totales.suspension}</div>
                    </div>
                </div>
                <div className="col-md-4">
                    <div className="border rounded-3 bg-body p-2 h-100">
                        <div className="text-secondary">Total días efectivos</div>
                        <div className="fw-bold fs-5 text-success">{efectivos}</div>
                    </div>
                </div>
            </div>
        </div>
    );
}
