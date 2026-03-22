import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';
import ModuleIndexRowDetail from '@/Components/ModuleIndexRowDetail';

export default function Index({ items, filters, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [], anulados = [] }) {
    const getDocumentLinks = (item) => (item.archivo ? [{ label: 'Archivo plantilla', path: item.archivo, url: item.archivo_url }] : []);

    const exportExcelUrl = () => {
        const params = new URLSearchParams();
        if (currentFolder?.id) params.set('folder_id', currentFolder.id);
        if (filters?.user_id) params.set('user_id', filters.user_id);
        if (filters?.search) params.set('search', filters.search);
        const qs = params.toString();
        return route('plantillas-ing.export') + (qs ? '?' + qs : '');
    };

    const renderHeader = (
        <a href={exportExcelUrl()} className="btn btn-success shadow-sm rounded-pill px-4" target="_blank" rel="noopener noreferrer">
            <i className="bi bi-file-earmark-excel me-2"></i> Exportar Excel
        </a>
    );

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
                    <i className="bi bi-paperclip me-1"></i> Ver archivos
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
            moveRouteName="plantillas-ing.move"
            moveBulkRouteName="plantillas-ing.move-bulk"
            operadores={operadores}
            getDocumentLinks={getDocumentLinks}
            anulados={anulados}
            renderDetail={renderDetail}
            renderHeader={renderHeader}
        />
    );
}
