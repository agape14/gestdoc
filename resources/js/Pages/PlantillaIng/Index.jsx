import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';
import ModuleIndexRowDetail from '@/Components/ModuleIndexRowDetail';

export default function Index({ items, filters, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [], anulados = [] }) {
    const getDocumentLinks = (item) => (item.archivo ? [{ label: 'Documento', path: item.archivo }] : []);

    const columns = [
        { header: 'PLANTILLA', accessor: 'titulo', render: (item) => (item.titulo ? String(item.titulo).slice(0, 45) + (String(item.titulo).length > 45 ? '…' : '') : '-') },
        { header: 'ESPECIALIDAD', accessor: 'especialidad' },
        { header: '', accessor: '_expand', render: () => <i className="bi bi-chevron-down text-secondary" title="Ver detalle" /> },
    ];

    const getDetailFields = (item) => [
        { label: 'Plantilla (completo)', value: item.titulo },
        { label: 'Especialidad', value: item.especialidad },
    ];

    const renderDetail = (item, context) => (
        <ModuleIndexRowDetail
            item={item}
            userRole={userRole}
            fields={getDetailFields(item)}
            editHref={route('plantillas-ing.edit', item.id)}
            deleteRouteName="plantillas-ing.destroy"
            documentButton={getDocumentLinks(item).length > 0 && context?.openDocumentsModal ? (
                <button type="button" className="btn btn-sm btn-outline-primary" onClick={(e) => { e.stopPropagation(); context.openDocumentsModal(item); }}>
                    <i className="bi bi-file-earmark-pdf me-1"></i> Ver documento
                </button>
            ) : null}
        />
    );

    return (
        <ModuleIndex
            title="Plantillas de Ingeniería"
            description="Repositorio de plantillas. Clic en la fila para ver detalle y acciones."
            items={items}
            columns={columns}
            createRoute={currentFolder?.id ? route('plantillas-ing.create', { folder_id: currentFolder.id }) : route('plantillas-ing.create')}
            editRoute={(id) => route('plantillas-ing.edit', id)}
            deleteRoute="plantillas-ing.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            storeFolderRoute="plantillas-ing.folders.store"
            indexRoute="plantillas-ing.index"
            indexTitle="Plantillas de Ingeniería"
            operadores={operadores}
            getDocumentLinks={getDocumentLinks}
            anulados={anulados}
            renderDetail={renderDetail}
        />
    );
}
