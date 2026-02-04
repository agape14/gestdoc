import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';

const COLOR_OPTIONS = [
    '#EAEAEA', '#FFE5E5', '#E5F5FF', '#FFEAA7', '#FDCB6E',
    '#74B9FF', '#A29BFE', '#DFE6E9', '#55EFC4', '#00B894',
    '#FAB1A0', '#636E72', '#6C5CE7', '#00CEC9',
];

const ICON_MAP = {
    Lock: 'bi-lock-fill', Globe: 'bi-globe', Package: 'bi-box-seam', Settings: 'bi-gear-fill',
    MoreHorizontal: 'bi-three-dots', Briefcase: 'bi-briefcase-fill', HardHat: 'bi-hammer',
    Droplets: 'bi-droplet-fill', Waves: 'bi-water', School: 'bi-building', Road: 'bi-signpost-fill',
    Bridge: 'bi-bricks', Trophy: 'bi-trophy-fill', Folder: 'bi-folder-fill', FileText: 'bi-file-text-fill',
    Diagram: 'bi-diagram-3-fill', Tools: 'bi-tools', Lightning: 'bi-lightning-charge-fill', Tree: 'bi-tree-fill',
    Shield: 'bi-shield-fill-check', Star: 'bi-star-fill', Calendar: 'bi-calendar-check-fill',
    Archive: 'bi-archive-fill', ClipboardCheck: 'bi-clipboard-check-fill', Building: 'bi-building',
};

const ICON_OPTIONS = [
    'Folder', 'Lock', 'Globe', 'Package', 'Briefcase', 'HardHat', 'FileText', 'Tools',
    'Building', 'Road', 'Bridge', 'Trophy', 'Star', 'Calendar', 'Archive', 'ClipboardCheck',
];

export default function ModuleFolderModal({ show, onClose, storeFolderRoute, parentId = null }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        parent_id: parentId || '',
        name: '',
        color: '#EAEAEA',
        icon: 'Folder',
        description: '',
    });

    useEffect(() => {
        if (show) {
            reset();
            setData({
                parent_id: parentId || '',
                name: '',
                color: '#EAEAEA',
                icon: 'Folder',
                description: '',
            });
        }
    }, [show, parentId]);

    const handleSubmit = (e) => {
        e.preventDefault();
        post(route(storeFolderRoute), {
            preserveScroll: true,
            onSuccess: () => {
                onClose();
                reset();
            },
        });
    };

    if (!show) return null;

    return (
        <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1050 }}>
            <div className="modal-dialog modal-dialog-centered modal-dialog-scrollable">
                <div className="modal-content border-0 shadow-lg rounded-4">
                    <div className="modal-header border-0 pb-0">
                        <h5 className="modal-title fw-bold">Nueva Carpeta</h5>
                        <button type="button" className="btn-close" onClick={onClose}></button>
                    </div>
                    <form onSubmit={handleSubmit}>
                        <div className="modal-body">
                            <div className="mb-3">
                                <label className="form-label fw-semibold">Nombre de la carpeta</label>
                                <input
                                    type="text"
                                    className={`form-control ${errors.name ? 'is-invalid' : ''}`}
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Ej: Proyectos 2025"
                                    required
                                    autoFocus
                                />
                                {errors.name && <div className="invalid-feedback">{errors.name}</div>}
                            </div>
                            <div className="mb-3">
                                <label className="form-label fw-semibold">Color</label>
                                <div className="d-flex flex-wrap gap-2">
                                    {COLOR_OPTIONS.map((color) => (
                                        <button
                                            key={color}
                                            type="button"
                                            onClick={() => setData('color', color)}
                                            className={`btn btn-sm border ${data.color === color ? 'border-primary border-3' : 'border-secondary'}`}
                                            style={{ backgroundColor: color, width: '36px', height: '36px' }}
                                            title={color}
                                        />
                                    ))}
                                </div>
                            </div>
                            <div className="mb-3">
                                <label className="form-label fw-semibold">Icono</label>
                                <div className="row g-2">
                                    {ICON_OPTIONS.map((icon) => (
                                        <div key={icon} className="col-3 col-md-2">
                                            <button
                                                type="button"
                                                onClick={() => setData('icon', icon)}
                                                className={`btn btn-sm w-100 d-flex flex-column align-items-center justify-content-center ${data.icon === icon ? 'btn-primary' : 'btn-outline-secondary'}`}
                                                style={{ minHeight: '56px' }}
                                                title={icon}
                                            >
                                                <i className={`bi ${ICON_MAP[icon] || 'bi-folder-fill'} fs-5`}></i>
                                                <small className="d-none d-md-block" style={{ fontSize: '0.65rem' }}>{icon}</small>
                                            </button>
                                        </div>
                                    ))}
                                </div>
                            </div>
                            <div className="mb-3">
                                <label className="form-label fw-semibold">Descripción <span className="text-secondary fw-normal">(opcional)</span></label>
                                <textarea
                                    className="form-control"
                                    rows="2"
                                    maxLength="500"
                                    value={data.description}
                                    onChange={(e) => setData('description', e.target.value)}
                                />
                                <div className="form-text">{data.description.length}/500</div>
                            </div>
                        </div>
                        <div className="modal-footer border-0 pt-0">
                            <button type="button" className="btn btn-secondary" onClick={onClose}>Cancelar</button>
                            <button type="submit" className="btn btn-primary" disabled={processing}>
                                {processing ? 'Guardando...' : 'Crear Carpeta'}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    );
}
