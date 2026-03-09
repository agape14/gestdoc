import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head } from '@inertiajs/react';
import ExperienciaForm from '@/Components/ExperienciaForm';

/** Convierte fecha del backend (ISO o yyyy-mm-dd) a DD/MM/YYYY para el formulario. */
function toDDMMYYYY(val) {
    if (!val) return '';
    const s = String(val).trim();
    const isoMatch = s.match(/^(\d{4})-(\d{2})-(\d{2})/);
    if (isoMatch) {
        const [, y, m, d] = isoMatch;
        return `${d}/${m}/${y}`;
    }
    if (/^\d{1,2}\/\d{1,2}\/\d{4}$/.test(s)) return s;
    return s;
}

export default function Edit({ servicio }) {
    const initialData = {
        ...servicio,
        fecha_inicio: servicio.fecha_inicio ? toDDMMYYYY(servicio.fecha_inicio) : '',
        fecha_suspension: servicio.fecha_suspension ? toDDMMYYYY(servicio.fecha_suspension) : '',
        fecha_reinicio: servicio.fecha_reinicio ? toDDMMYYYY(servicio.fecha_reinicio) : '',
        fecha_culminacion: servicio.fecha_culminacion ? toDDMMYYYY(servicio.fecha_culminacion) : '',
    };
    const cancelUrl = servicio.folder_id
        ? route('proveedor-servicios.index', { folder_id: servicio.folder_id })
        : route('proveedor-servicios.index');

    return (
        <MainLayout>
            <Head title="Editar registro - Proveedor de Servicios" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '900px', margin: '0 auto' }}>
                <ExperienciaForm
                    key={servicio?.id ?? 'edit'}
                    structure={2}
                    initialData={initialData}
                    submitRoute={servicio?.id != null ? `/proveedor-servicios/${servicio.id}` : '#'}
                    method="PUT"
                    cancelUrl={cancelUrl}
                    title="Editar Proveedor de Servicios"
                />
            </div>
        </MainLayout>
    );
}
