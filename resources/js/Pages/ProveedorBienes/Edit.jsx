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

export default function Edit({ bien }) {
    const initialData = {
        ...bien,
        fecha_inicio: bien.fecha_inicio ? toDDMMYYYY(bien.fecha_inicio) : '',
        fecha_culminacion: bien.fecha_culminacion ? toDDMMYYYY(bien.fecha_culminacion) : '',
    };
    const cancelUrl = bien?.folder_id
        ? route('proveedor-bienes.index', { folder_id: bien.folder_id })
        : route('proveedor-bienes.index');

    return (
        <MainLayout>
            <Head title="Editar registro - Proveedor de Bienes" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body form-card-responsive" style={{ maxWidth: '900px', margin: '0 auto' }}>
                <ExperienciaForm
                    key={bien?.id ?? 'edit'}
                    structure={3}
                    variant="proveedor-bienes"
                    initialData={initialData}
                    submitRoute={bien?.id != null ? `/proveedor-bienes/${bien.id}` : '#'}
                    method="PUT"
                    cancelUrl={cancelUrl}
                    title="Editar Proveedor de Bienes"
                />
            </div>
        </MainLayout>
    );
}
