import React from 'react';
import ModuleIndex from '@/Components/ModuleIndex';
import ModuleIndexRowDetail from '@/Components/ModuleIndexRowDetail';

export default function Index({ items, filters, userRole, folders = [], currentFolder = null, breadcrumb = [], operadores = [], anulados = [] }) {
    const getDocumentLinks = (item) => {
        if (!item.imagen) return [];
        return [{ label: 'Imagen del proyecto', path: item.imagen, url: item.imagen_url }];
    };

    const columns = [
        { header: 'PROYECTO', accessor: 'titulo', sortable: true, render: (item) => (item.titulo ? String(item.titulo).slice(0, 45) + (String(item.titulo).length > 45 ? '…' : '') : '-') },
        { header: 'UBICACIÓN', accessor: 'ubicacion', sortable: true, render: (item) => (item.ubicacion ? String(item.ubicacion).slice(0, 35) + (String(item.ubicacion).length > 35 ? '…' : '') : '-') },
        { header: 'PRECIO', accessor: 'precio', sortable: true, render: (row) => `S/ ${parseFloat(row.precio || 0).toFixed(2)}` },
        { header: 'ESTADO', accessor: 'estado', sortable: true },
        { header: '', accessor: '_expand', render: () => <i className="bi bi-chevron-down text-secondary" title="Ver detalle" /> },
    ];

    const getDetailFields = (item) => [
        { label: 'Proyecto (completo)', value: item.titulo },
        { label: 'Ubicación (completa)', value: item.ubicacion },
        { label: 'Precio', value: item.precio != null ? `S/ ${parseFloat(item.precio).toFixed(2)}` : '' },
        { label: 'Estado', value: item.estado },
    ];

    const exportExcelUrl = () => {
        const params = new URLSearchParams();
        if (currentFolder?.id) params.set('folder_id', currentFolder.id);
        if (filters?.user_id) params.set('user_id', filters.user_id);
        if (filters?.search) params.set('search', filters.search);
        const qs = params.toString();
        return route('inmobiliaria.export') + (qs ? '?' + qs : '');
    };

    const renderHeader = (
        <a href={exportExcelUrl()} className="btn btn-success shadow-sm rounded-pill px-4" target="_blank" rel="noopener noreferrer">
            <i className="bi bi-file-earmark-excel me-2"></i> Exportar Excel
        </a>
    );

    const renderDetail = (item, context) => (
        <ModuleIndexRowDetail
            item={item}
            userRole={userRole}
            fields={getDetailFields(item)}
            editHref={route('inmobiliaria.edit', item.id)}
            deleteRouteName="inmobiliaria.destroy"
            documentButton={getDocumentLinks(item).length > 0 && context?.openDocumentsModal ? (
                <button type="button" className="btn btn-sm btn-outline-primary" onClick={(e) => { e.stopPropagation(); context.openDocumentsModal(item); }}>
                    <i className="bi bi-paperclip me-1"></i> Ver archivos
                </button>
            ) : null}
        />
    );

    return (
        <ModuleIndex
            title="Inmobiliaria"
            description="Gestión de proyectos inmobiliarios. Clic en la fila para ver detalle y acciones."
            items={items}
            columns={columns}
            createRoute={currentFolder?.id ? route('inmobiliaria.create', { folder_id: currentFolder.id }) : route('inmobiliaria.create')}
            editRoute={(id) => route('inmobiliaria.edit', id)}
            deleteRoute="inmobiliaria.destroy"
            filters={[]}
            routeParams={filters}
            userRole={userRole}
            folders={folders}
            currentFolder={currentFolder}
            breadcrumb={breadcrumb}
            storeFolderRoute="inmobiliaria.folders.store"
            indexRoute="inmobiliaria.index"
            indexTitle="Inmobiliaria"
            moveRouteName="inmobiliaria.move"
            moveBulkRouteName="inmobiliaria.move-bulk"
            operadores={operadores}
            anulados={anulados}
            renderDetail={renderDetail}
            getDocumentLinks={getDocumentLinks}
            renderHeader={renderHeader}
            sortEnabled
        />
    );
}
