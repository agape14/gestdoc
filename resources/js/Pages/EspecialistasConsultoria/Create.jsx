import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head } from '@inertiajs/react';
import ExperienciaForm from '@/Components/ExperienciaForm';

export default function Create({ folderId = null, breadcrumbLabel = '' }) {
    const cancelUrl = folderId ? route('especialistas-consultoria.index', { folder_id: folderId }) : route('especialistas-consultoria.index');

    return (
        <MainLayout>
            <Head title="Nuevo registro - Especialistas en Consultoría" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body form-card-responsive" style={{ maxWidth: '900px', margin: '0 auto' }}>
                <ExperienciaForm
                    structure={1}
                    variant="especialistas-consultoria"
                    initialData={{ folder_id: folderId || '', clasificacion: breadcrumbLabel || '' }}
                    submitRoute={route('especialistas-consultoria.store')}
                    method="POST"
                    cancelUrl={cancelUrl}
                    title="Nuevo Especialista en Consultoría de Obra"
                />
            </div>
        </MainLayout>
    );
}
