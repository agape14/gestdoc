import React from 'react';

/**
 * Parte superior común para todos los formularios de EXPERIENCIA EN LA ESPECIALIDAD.
 */
export default function ExperienciaFormHeader() {
    return (
        <div className="mb-4 p-3 bg-light rounded small">
            <div className="fw-bold">ING. VICTOR ROBERTH JAIMES ACUÑA</div>
            <div>INGENIERO CIVIL - TOPOGRAFO</div>
            <div>REGISTRO CIP N°: 237698</div>
            <div>RNP CONSULTOR DE OBRAS: C115373</div>
            <div className="mt-3">
                Señores<br />
                COMITÉ DE SELECCIÓN<br />
                ADJUDICACIÓN SIMPLIFICADA N° 09-2021-MDSM/CS PRIMERA CONVOCATORIA<br />
                Presente.-
            </div>
            <p className="mt-3 mb-0">
                Mediante el presente, el suscrito detalla la siguiente <strong>EXPERIENCIA EN LA ESPECIALIDAD:</strong>
            </p>
        </div>
    );
}
