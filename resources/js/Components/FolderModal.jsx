import React, { useEffect } from 'react';
import { useForm } from '@inertiajs/react';

export default function FolderModal({ show, onClose, folder = null, parentId = null }) {
    const isEditing = !!folder;

    const { data, setData, post, put, processing, errors, reset } = useForm({
        parent_id: parentId || '',
        name: '',
        color: '#EAEAEA',
        icon: 'Folder',
        description: '',
    });

    useEffect(() => {
        if (folder) {
            setData({
                parent_id: folder.parent_id || parentId || '',
                name: folder.name || '',
                color: folder.color || '#EAEAEA',
                icon: folder.icon || 'Folder',
                description: folder.description || '',
            });
        } else {
            reset();
            setData({
                parent_id: parentId || '',
                name: '',
                color: '#EAEAEA',
                icon: 'Folder',
                description: '',
            });
        }
    }, [folder, parentId, show]);

    const handleSubmit = (e) => {
        e.preventDefault();

        if (isEditing) {
            put(route('folders.update', folder.id), {
                preserveScroll: true,
                onSuccess: () => {
                    onClose();
                    reset();
                },
            });
        } else {
            post(route('folders.store'), {
                preserveScroll: true,
                onSuccess: () => {
                    onClose();
                    reset();
                },
            });
        }
    };

    const colorOptions = [
        '#EAEAEA', '#FFE5E5', '#E5F5FF', '#FFEAA7', '#FDCB6E',
        '#74B9FF', '#A29BFE', '#DFE6E9', '#55EFC4', '#00B894',
        '#FAB1A0', '#636E72', '#6C5CE7', '#00CEC9',
    ];

    const iconOptions = [
        'Folder', 'Lock', 'Globe', 'Package', 'Settings',
        'MoreHorizontal', 'Briefcase', 'HardHat', 'Droplets',
        'Waves', 'School', 'Road', 'Bridge', 'Trophy',
        'FileText', 'Diagram', 'Tools', 'Lightning', 'Tree',
        'Shield', 'Star', 'Calendar', 'Archive', 'ClipboardCheck',
    ];

    if (!show) return null;

    return (
        <>
            <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                <div className="modal-dialog modal-dialog-centered">
                    <div className="modal-content border-0 shadow-lg rounded-4">
                        <div className="modal-header border-0 pb-0">
                            <h5 className="modal-title fw-bold">
                                {isEditing ? 'Editar Carpeta' : 'Nueva Carpeta'}
                            </h5>
                            <button type="button" className="btn-close" onClick={onClose}></button>
                        </div>
                        <form onSubmit={handleSubmit}>
                            <div className="modal-body">
                                {/* Nombre */}
                                <div className="mb-3">
                                    <label htmlFor="name" className="form-label fw-semibold">
                                        Nombre de la Carpeta
                                    </label>
                                    <input
                                        type="text"
                                        id="name"
                                        className={`form-control ${errors.name ? 'is-invalid' : ''}`}
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                        required
                                        autoFocus
                                    />
                                    {errors.name && (
                                        <div className="invalid-feedback">{errors.name}</div>
                                    )}
                                </div>

                                {/* Color */}
                                <div className="mb-3">
                                    <label className="form-label fw-semibold">Color</label>
                                    <div className="d-flex flex-wrap gap-2">
                                        {colorOptions.map((color) => (
                                            <button
                                                key={color}
                                                type="button"
                                                onClick={() => setData('color', color)}
                                                className={`btn btn-sm border ${
                                                    data.color === color
                                                        ? 'border-primary border-3'
                                                        : 'border-secondary'
                                                }`}
                                                style={{
                                                    backgroundColor: color,
                                                    width: '40px',
                                                    height: '40px'
                                                }}
                                                title={color}
                                            />
                                        ))}
                                    </div>
                                    {errors.color && (
                                        <div className="text-danger small mt-1">{errors.color}</div>
                                    )}
                                </div>

                                {/* Icono */}
                                {!folder?.is_system && (
                                    <div className="mb-3">
                                        <label htmlFor="icon" className="form-label fw-semibold">
                                            Icono
                                        </label>
                                        <div className="row g-2">
                                            {iconOptions.map((icon) => {
                                                const iconMap = {
                                                    Lock: 'bi-lock-fill',
                                                    Globe: 'bi-globe',
                                                    Package: 'bi-box-seam',
                                                    Settings: 'bi-gear-fill',
                                                    MoreHorizontal: 'bi-three-dots',
                                                    Briefcase: 'bi-briefcase-fill',
                                                    HardHat: 'bi-hammer',
                                                    Droplets: 'bi-droplet-fill',
                                                    Waves: 'bi-water',
                                                    School: 'bi-building',
                                                    Road: 'bi-signpost-fill',
                                                    Bridge: 'bi-bricks',
                                                    Trophy: 'bi-trophy-fill',
                                                    Folder: 'bi-folder-fill',
                                                    FileText: 'bi-file-text-fill',
                                                    Diagram: 'bi-diagram-3-fill',
                                                    Tools: 'bi-tools',
                                                    Lightning: 'bi-lightning-charge-fill',
                                                    Tree: 'bi-tree-fill',
                                                    Shield: 'bi-shield-fill-check',
                                                    Star: 'bi-star-fill',
                                                    Calendar: 'bi-calendar-check-fill',
                                                    Archive: 'bi-archive-fill',
                                                    ClipboardCheck: 'bi-clipboard-check-fill',
                                                };
                                                const iconClass = iconMap[icon] || 'bi-folder-fill';

                                                return (
                                                    <div key={icon} className="col-3">
                                                        <button
                                                            type="button"
                                                            onClick={() => setData('icon', icon)}
                                                            className={`btn btn-sm w-100 d-flex flex-column align-items-center justify-content-center ${
                                                                data.icon === icon
                                                                    ? 'btn-primary'
                                                                    : 'btn-outline-secondary'
                                                            }`}
                                                            style={{ height: '70px' }}
                                                            title={icon}
                                                        >
                                                            <i className={`bi ${iconClass} fs-3 mb-1`}></i>
                                                            <small style={{ fontSize: '0.7rem' }}>{icon}</small>
                                                        </button>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                        {errors.icon && (
                                            <div className="text-danger small mt-1">{errors.icon}</div>
                                        )}
                                    </div>
                                )}

                                {/* Descripción */}
                                <div className="mb-3">
                                    <label htmlFor="description" className="form-label fw-semibold">
                                        Descripción <span className="text-secondary fw-normal">(opcional)</span>
                                    </label>
                                    <textarea
                                        id="description"
                                        className={`form-control ${errors.description ? 'is-invalid' : ''}`}
                                        rows="3"
                                        maxLength="500"
                                        value={data.description}
                                        onChange={(e) => setData('description', e.target.value)}
                                    />
                                    <div className="form-text">
                                        {data.description.length}/500 caracteres
                                    </div>
                                    {errors.description && (
                                        <div className="invalid-feedback">{errors.description}</div>
                                    )}
                                </div>
                            </div>
                            <div className="modal-footer border-0 pt-0">
                                <button type="button" className="btn btn-secondary" onClick={onClose}>
                                    Cancelar
                                </button>
                                <button type="submit" className="btn btn-primary" disabled={processing}>
                                    {processing ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Guardando...
                                        </>
                                    ) : (
                                        isEditing ? 'Actualizar' : 'Crear Carpeta'
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
