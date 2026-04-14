import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head } from '@inertiajs/react';
import ExperienciaForm from '@/Components/ExperienciaForm';

/** Convierte fecha del backend (ISO o yyyy-mm-dd) a DD/MM/YYYY. */
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

export default function Edit({ especialista }) {
    const initialData = {
        ...especialista,
        fecha_inicio: especialista.fecha_inicio ? toDDMMYYYY(especialista.fecha_inicio) : '',
        fecha_suspension: especialista.fecha_suspension ? toDDMMYYYY(especialista.fecha_suspension) : '',
        fecha_reinicio: especialista.fecha_reinicio ? toDDMMYYYY(especialista.fecha_reinicio) : '',
        fecha_culminacion: especialista.fecha_culminacion ? toDDMMYYYY(especialista.fecha_culminacion) : '',
    };
    const cancelUrl = especialista?.folder_id
        ? route('especialistas-consultoria.index', { folder_id: especialista.folder_id })
        : route('especialistas-consultoria.index');

    return (
        <MainLayout>
            <Head title="Editar registro - Especialistas en Consultoría" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body form-card-responsive" style={{ maxWidth: '900px', margin: '0 auto' }}>
                <ExperienciaForm
                    key={especialista?.id ?? 'edit'}
                    structure={1}
                    variant="especialistas-consultoria"
                    initialData={initialData}
                    submitRoute={especialista?.id != null ? `/especialistas-consultoria/${especialista.id}` : '#'}
                    method="PUT"
                    cancelUrl={cancelUrl}
                    title="Editar Especialista en Consultoría de Obra"
                />
            </div>
        </MainLayout>
    );
}
