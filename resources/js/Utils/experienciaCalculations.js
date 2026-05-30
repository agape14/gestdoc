/**
 * Reglas de cálculo para EXPERIENCIA EN LA ESPECIALIDAD (Gestdoc).
 * Fechas: DD/MM/YYYY. Moneda: S/. 1,000.00 (miles coma, decimales punto).
 */

const DIAS_POR_MES = 30;

/**
 * Parsea fecha en DD/MM/YYYY a Date. Retorna null si no válida.
 */
export function parseDateDDMMYYYY(str) {
    if (!str || typeof str !== 'string') return null;
    const trimmed = str.trim();
    const parts = trimmed.split(/[/\-.]/);
    if (parts.length !== 3) return null;
    const day = parseInt(parts[0], 10);
    const month = parseInt(parts[1], 10) - 1;
    const year = parseInt(parts[2], 10);
    if (isNaN(day) || isNaN(month) || isNaN(year)) return null;
    const d = new Date(year, month, day);
    if (d.getFullYear() !== year || d.getMonth() !== month || d.getDate() !== day) return null;
    return d;
}

/**
 * Parsea fecha en DD/MM/YYYY o ISO (YYYY-MM-DD) a Date.
 */
export function parseDateFlexible(str) {
    if (!str || typeof str !== 'string') return null;
    const trimmed = str.trim();
    if (/^\d{4}-\d{2}-\d{2}$/.test(trimmed)) {
        const [year, month, day] = trimmed.split('-').map(Number);
        const d = new Date(year, month - 1, day);
        if (d.getFullYear() !== year || d.getMonth() !== month - 1 || d.getDate() !== day) return null;
        return d;
    }
    return parseDateDDMMYYYY(trimmed);
}

/**
 * Formatea fecha ISO (Y-m-d o con hora) o Date a DD/MM/YYYY para mostrar.
 */
export function formatDateDisplay(value) {
    if (!value) return '-';
    let d;
    if (typeof value === 'string') {
        const trimmed = value.trim();
        if (/^\d{4}-\d{2}-\d{2}/.test(trimmed)) {
            d = trimmed.length > 10 ? new Date(trimmed) : new Date(trimmed + 'T12:00:00');
        } else {
            d = new Date(value);
        }
    } else {
        d = value instanceof Date ? value : new Date(value);
    }
    if (isNaN(d.getTime())) return '-';
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

/**
 * Formatea Date a DD/MM/YYYY.
 */
export function formatDateToDDMMYYYY(date) {
    if (!date) return '';
    const d = date instanceof Date ? date : new Date(date);
    if (isNaN(d.getTime())) return '';
    const day = String(d.getDate()).padStart(2, '0');
    const month = String(d.getMonth() + 1).padStart(2, '0');
    const year = d.getFullYear();
    return `${day}/${month}/${year}`;
}

/**
 * INPUT: fechaInicio, fechaCulminacion (string DD/MM/YYYY o Date).
 * TOTAL DE DIAS = FECHA CULMINACION - FECHA INICIO + 1
 */
export function calcTotalDias(fechaInicio, fechaCulminacion) {
    const start = typeof fechaInicio === 'string' ? parseDateFlexible(fechaInicio) : (fechaInicio instanceof Date ? fechaInicio : null);
    const end = typeof fechaCulminacion === 'string' ? parseDateFlexible(fechaCulminacion) : (fechaCulminacion instanceof Date ? fechaCulminacion : null);
    if (!start || !end) return null;
    const diff = Math.round((end - start) / (1000 * 60 * 60 * 24));
    return diff + 1;
}

/**
 * Suma total de meses y total de días para el campo PLAZO.
 */
export function calcPlazoSum(totalMeses, totalDias) {
    if (totalMeses == null && totalDias == null) return '';
    const meses = totalMeses != null && totalMeses !== '' ? Number(totalMeses) : 0;
    const dias = totalDias != null && totalDias !== '' ? Number(totalDias) : 0;
    if (isNaN(meses) || isNaN(dias)) return '';
    const suma = meses + dias;
    return Number.isInteger(suma) ? String(suma) : suma.toFixed(2);
}

/**
 * TOTAL DE MESES = TOTAL DE DIAS / 30 (2 decimales).
 */
export function calcTotalMeses(totalDias) {
    if (totalDias == null || totalDias === '') return null;
    const n = Number(totalDias);
    if (isNaN(n)) return null;
    return Math.round((n / DIAS_POR_MES) * 100) / 100;
}

/**
 * TOTAL DE DIAS SIN TRASLAPE = TOTAL DE DIAS - TRASLAPE
 */
export function calcTotalDiasSinTraslape(totalDias, traslape) {
    if (totalDias == null || totalDias === '') return null;
    const d = Number(totalDias);
    const t = Number(traslape) || 0;
    if (isNaN(d)) return null;
    return Math.max(0, Math.round(d - t));
}

/**
 * Dados totalDiasSinTraslape (número), retorna { dias, meses, años }.
 */
export function calcTiempoTotalSinTraslape(totalDiasSinTraslape) {
    if (totalDiasSinTraslape == null || totalDiasSinTraslape === '') {
        return { dias: 0, meses: 0, años: 0 };
    }
    const d = Number(totalDiasSinTraslape);
    if (isNaN(d)) return { dias: 0, meses: 0, años: 0 };
    const meses = Math.round((d / DIAS_POR_MES) * 100) / 100;
    const años = Math.round((d / 365.25) * 100) / 100;
    return { dias: d, meses, años };
}

/**
 * Formato moneda peruana: S/. 1,000.00 (miles con coma, decimales con punto).
 */
export function formatMonedaPeruana(value) {
    if (value == null || value === '') return 'S/. 0.00';
    const n = Number(value);
    if (isNaN(n)) return 'S/. 0.00';
    const fixed = n.toFixed(2);
    const [intPart, decPart] = fixed.split('.');
    const withCommas = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    return `S/. ${withCommas}.${decPart}`;
}

/**
 * Parsea valor de moneda (acepta "1,000.00" o "1000") a número.
 */
export function parseMonedaToNumber(str) {
    if (str == null || str === '') return null;
    const cleaned = String(str).replace(/[^\d.]/g, '').replace(/,/g, '').trim();
    if (cleaned === '' || cleaned === '.') return 0;
    const n = parseFloat(cleaned);
    return isNaN(n) ? null : n;
}
