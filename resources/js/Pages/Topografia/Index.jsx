import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';
import ModuleIndexRowDetail from '@/Components/ModuleIndexRowDetail';

export default function Index({ items, filters, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [], anulados = [] }) {
    const getDocumentLinks = (item) => (item.archivo ? [{ label: 'Archivo adjunto', path: item.archivo, url: item.archivo_url }] : []);

    const exportExcelUrl = () => {
        const params = new URLSearchParams();
        if (currentFolder?.id) params.set('folder_id', currentFolder.id);
        if (filters?.user_id) params.set('user_id', filters.user_id);
        if (filters?.search) params.set('search', filters.search);
        const qs = params.toString();
        return route('topografia.export') + (qs ? '?' + qs : '');
    };

    const renderHeader = (
        <a href={exportExcelUrl()} className="btn btn-success shadow-sm rounded-pill px-4" target="_blank" rel="noopener noreferrer">
            <i className="bi bi-file-earmark-excel me-2"></i> Exportar Excel
        </a>
    );

    const columns = [
        { header: 'TÍTULO', accessor: 'titulo', sortable: true, render: (item) => (item.titulo ? String(item.titulo).slice(0, 50) + (String(item.titulo).length > 50 ? '…' : '') : '-') },
        { header: 'DESCRIPCIÓN', accessor: 'descripcion', sortable: true, render: (item) => (item.descripcion ? String(item.descripcion).slice(0, 40) + (String(item.descripcion).length > 40 ? '…' : '') : '-') },
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
            editHref={route('topografia.edit', item.id)}
            deleteRouteName="topografia.destroy"
            documentButton={getDocumentLinks(item).length > 0 && context?.openDocumentsModal ? (
                <button type="button" className="btn btn-sm btn-outline-primary" onClick={(e) => { e.stopPropagation(); context.openDocumentsModal(item); }}>
                    <i className="bi bi-paperclip me-1"></i> Ver archivos
                </button>
            ) : null}
        />
    );

    return (
        <ModuleIndex
            title="Topografía"
            description="Documentos y recursos de topografía. Clic en la fila para ver detalle y acciones."
            items={items}
            columns={columns}
            createRoute={currentFolder?.id ? route('topografia.create', { folder_id: currentFolder.id }) : route('topografia.create')}
            editRoute={(id) => route('topografia.edit', id)}
            deleteRoute="topografia.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            storeFolderRoute="topografia.folders.store"
            indexRoute="topografia.index"
            indexTitle="Topografía"
            moveRouteName="topografia.move"
            moveBulkRouteName="topografia.move-bulk"
            operadores={operadores}
            getDocumentLinks={getDocumentLinks}
            anulados={anulados}
            renderDetail={renderDetail}
            renderHeader={renderHeader}
            sortEnabled
        />
    );
}
