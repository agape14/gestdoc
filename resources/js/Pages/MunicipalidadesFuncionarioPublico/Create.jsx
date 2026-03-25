import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head } from '@inertiajs/react';
import ExperienciaForm from '@/Components/ExperienciaForm';

export default function Create({ folderId = null, breadcrumbLabel = '' }) {
    const cancelUrl = folderId
        ? route('municipalidades-funcionario-publico.index', { folder_id: folderId })
        : route('municipalidades-funcionario-publico.index');

    return (
        <MainLayout>
            <Head title="Nuevo registro - Municipalidades y/o Funcionario Publico" />
            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body form-card-responsive" style={{ maxWidth: '900px', margin: '0 auto' }}>
                <ExperienciaForm
                    structure={1}
                    variant="municipalidades-funcionario-publico"
                    initialData={{ folder_id: folderId || '', clasificacion: breadcrumbLabel || '' }}
                    submitRoute={route('municipalidades-funcionario-publico.store')}
                    method="POST"
                    cancelUrl={cancelUrl}
                    title="Nuevo Municipalidades y/o Funcionario Publico"
                />
            </div>
        </MainLayout>
    );
}
