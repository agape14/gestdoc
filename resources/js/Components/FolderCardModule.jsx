import React from 'react';
import { Link } from '@inertiajs/react';

const ICON_MAP = {
    Lock: 'bi-lock-fill', Globe: 'bi-globe', Package: 'bi-box-seam', Settings: 'bi-gear-fill',
    MoreHorizontal: 'bi-three-dots', Briefcase: 'bi-briefcase-fill', HardHat: 'bi-hammer',
    Droplets: 'bi-droplet-fill', Waves: 'bi-water', School: 'bi-building', Road: 'bi-signpost-fill',
    Bridge: 'bi-bricks', Trophy: 'bi-trophy-fill', FileText: 'bi-file-text-fill', Folder: 'bi-folder-fill',
    Diagram: 'bi-diagram-3-fill', Tools: 'bi-tools', Lightning: 'bi-lightning-charge-fill', Tree: 'bi-tree-fill',
    Shield: 'bi-shield-fill-check', Building: 'bi-building', Archive: 'bi-archive-fill',
    ClipboardCheck: 'bi-clipboard-check-fill', Star: 'bi-star-fill', Calendar: 'bi-calendar-check-fill',
};

/**
 * Tarjeta de carpeta para módulos con icono de editar (solo admin).
 * @param {Object} folder - Carpeta
 * @param {string} indexRoute - Ruta del index (ej: 'licitaciones.index')
 * @param {Object} indexParams - Parámetros para la ruta
 * @param {boolean} isAdmin - Si el usuario es administrador
 * @param {Function} onEdit - Callback al hacer clic en editar
 */
export default function FolderCardModule({ folder, indexRoute, indexParams = {}, isAdmin, onEdit }) {
    const getIconClass = (iconName) => ICON_MAP[iconName] || 'bi-folder-fill';

    return (
        <div className="col-md-6 col-lg-4 col-xl-3 position-relative">
            <Link
                href={route(indexRoute, { ...indexParams, folder_id: folder.id })}
                className="text-decoration-none text-body d-block"
            >
                <div className="card border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative group-folder">
                    <div className="card-header border-0 p-4" style={{ backgroundColor: folder.color || '#EAEAEA', minHeight: '100px' }}>
                        <i className={`bi ${getIconClass(folder.icon)} fs-1 opacity-75`}></i>
                    </div>
                    <div className="card-body p-3">
                        <h6 className="card-title fw-bold mb-0">{folder.name}</h6>
                    </div>
                </div>
            </Link>
            {isAdmin && (
                <button
                    type="button"
                    className="btn btn-sm btn-light position-absolute top-0 end-0 m-2 rounded-circle shadow-sm folder-edit-btn"
                    onClick={(e) => {
                        e.preventDefault();
                        e.stopPropagation();
                        onEdit?.(folder);
                    }}
                    title="Editar carpeta"
                    style={{ zIndex: 5 }}
                >
                    <i className="bi bi-pencil-fill text-primary"></i>
                </button>
            )}
        </div>
    );
}
