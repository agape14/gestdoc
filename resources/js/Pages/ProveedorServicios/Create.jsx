import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head } from '@inertiajs/react';
import ExperienciaForm from '@/Components/ExperienciaForm';

export default function Create({ folderId = null, breadcrumbLabel = '' }) {
    const cancelUrl = folderId ? route('proveedor-servicios.index', { folder_id: folderId }) : route('proveedor-servicios.index');

    return (
        <MainLayout>
            <Head title="Nuevo registro - Proveedor de Servicios" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body form-card-responsive" style={{ maxWidth: '900px', margin: '0 auto' }}>
                <ExperienciaForm
                    structure={2}
                    initialData={{ folder_id: folderId || '', clasificacion: breadcrumbLabel || '' }}
                    submitRoute={route('proveedor-servicios.store')}
                    method="POST"
                    cancelUrl={cancelUrl}
                    title="Nuevo Proveedor de Servicios"
                />
            </div>
        </MainLayout>
    );
}
