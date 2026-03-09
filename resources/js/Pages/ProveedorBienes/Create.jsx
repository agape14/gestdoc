import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head } from '@inertiajs/react';
import ExperienciaForm from '@/Components/ExperienciaForm';

export default function Create({ folderId = null, breadcrumbLabel = '' }) {
    const cancelUrl = folderId ? route('proveedor-bienes.index', { folder_id: folderId }) : route('proveedor-bienes.index');

    return (
        <MainLayout>
            <Head title="Nuevo registro - Proveedor de Bienes" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '900px', margin: '0 auto' }}>
                <ExperienciaForm
                    structure={3}
                    initialData={{ folder_id: folderId || '', clasificacion: breadcrumbLabel || '' }}
                    submitRoute={route('proveedor-bienes.store')}
                    method="POST"
                    cancelUrl={cancelUrl}
                    title="Nuevo Proveedor de Bienes"
                />
            </div>
        </MainLayout>
    );
}
