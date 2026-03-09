import React from 'react';
import { calcTiempoTotalSinTraslape, formatMonedaPeruana } from '@/Utils/experienciaCalculations';

/**
 * Pie de tabla: TIEMPO TOTAL SIN TRASLAPE (días, meses, años).
 * totalDiasSinTraslape = suma de (total_dias - traslape) de todos los registros.
 */
export default function ExperienciaPieTabla({ totalDiasSinTraslape = 0, totalMontoAcumulado = 0, showMonto = true }) {
    const { dias, meses, años } = calcTiempoTotalSinTraslape(totalDiasSinTraslape);
    return (
        <div className="d-flex flex-wrap align-items-center gap-4 mt-3 p-3 border rounded bg-light">
            <div className="fw-bold">TIEMPO TOTAL SIN TRASLAPE</div>
            <div className="d-flex gap-3 flex-wrap">
                <span><strong>{typeof dias === 'number' ? dias.toFixed(2) : dias}</strong> DIAS</span>
                <span><strong>{typeof meses === 'number' ? meses.toFixed(2) : meses}</strong> MESES</span>
                <span><strong>{typeof años === 'number' ? años.toFixed(2) : años}</strong> AÑOS</span>
            </div>
            {showMonto && (
                <div className="ms-auto">
                    Monto total: <strong>{formatMonedaPeruana(totalMontoAcumulado)}</strong>
                </div>
            )}
        </div>
    );
}
