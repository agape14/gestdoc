import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';
import ModuleIndexRowDetail from '@/Components/ModuleIndexRowDetail';

export default function Index({ items, filters, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [], anulados = [] }) {
    const getDocumentLinks = (item) => (item.archivo ? [{ label: 'Documento', path: item.archivo }] : []);

    const columns = [
        { header: 'TÍTULO', accessor: 'titulo', render: (item) => (item.titulo ? String(item.titulo).slice(0, 50) + (String(item.titulo).length > 50 ? '…' : '') : '-') },
        { header: 'DESCRIPCIÓN', accessor: 'descripcion', render: (item) => (item.descripcion ? String(item.descripcion).slice(0, 40) + (String(item.descripcion).length > 40 ? '…' : '') : '-') },
        { header: '', accessor: '_expand', render: () => <i className="bi bi-chevron-down text-secondary" title="Ver detalle" /> },
    ];

    const getDetailFields = (item) => [
        { label: 'Título (completo)', value: item.titulo },
        { label: 'Descripción (completa)', value: item.descripcion },
    ];

    const renderDetail = (item, context) => (
        <ModuleIndexRowDetail
            item={item}
            userRole={userRole}
            fields={getDetailFields(item)}
            editHref={route('tecnologia.edit', item.id)}
            deleteRouteName="tecnologia.destroy"
            documentButton={getDocumentLinks(item).length > 0 && context?.openDocumentsModal ? (
                <button type="button" className="btn btn-sm btn-outline-primary" onClick={(e) => { e.stopPropagation(); context.openDocumentsModal(item); }}>
                    <i className="bi bi-file-earmark-pdf me-1"></i> Ver documento
                </button>
            ) : null}
        />
    );

    return (
        <ModuleIndex
            title="Tecnología"
            description="Recursos tecnológicos. Clic en la fila para ver detalle y acciones."
            items={items}
            columns={columns}
            createRoute={currentFolder?.id ? route('tecnologia.create', { folder_id: currentFolder.id }) : route('tecnologia.create')}
            editRoute={(id) => route('tecnologia.edit', id)}
            deleteRoute="tecnologia.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            storeFolderRoute="tecnologia.folders.store"
            indexRoute="tecnologia.index"
            indexTitle="Tecnología"
            operadores={operadores}
            getDocumentLinks={getDocumentLinks}
            anulados={anulados}
            renderDetail={renderDetail}
        />
    );
}
