import React, { useEffect, useState } from 'react';

export default function ZipTypePickerModal({
    show,
    title = 'Descargar ZIP',
    types = [],
    onConfirm,
    onClose,
}) {
    const [selected, setSelected] = useState(() => types.map((t) => t.id));

    useEffect(() => {
        if (show) {
            setSelected(types.map((t) => t.id));
        }
    }, [show, types]);

    if (!show) return null;

    const toggle = (id) => {
        setSelected((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
    };

    const allChecked = types.length > 0 && selected.length === types.length;

    return (
        <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1060 }}>
            <div className="modal-dialog modal-dialog-centered">
                <div className="modal-content border-0 shadow-lg rounded-4">
                    <div className="modal-header border-0">
                        <h5 className="modal-title fw-bold">{title}</h5>
                        <button type="button" className="btn-close" onClick={onClose}></button>
                    </div>
                    <div className="modal-body">
                        <p className="text-secondary small">
                            El ZIP tendrá una carpeta por proyecto (<code>CUI_PROYECTO / archivo</code>) con todos sus archivos dentro. Elija los tipos de archivo a incluir.
                        </p>
                        {types.length > 0 && (
                            <label className="form-check mb-2">
                                <input
                                    type="checkbox"
                                    className="form-check-input"
                                    checked={allChecked}
                                    onChange={() => setSelected(allChecked ? [] : types.map((t) => t.id))}
                                />
                                <span className="form-check-label fw-semibold">Todos</span>
                            </label>
                        )}
                        <div className="d-flex flex-column gap-1">
                            {types.map((t) => (
                                <label key={t.id} className="form-check">
                                    <input
                                        type="checkbox"
                                        className="form-check-input"
                                        checked={selected.includes(t.id)}
                                        onChange={() => toggle(t.id)}
                                    />
                                    <span className="form-check-label">{t.label}</span>
                                </label>
                            ))}
                        </div>
                    </div>
                    <div className="modal-footer border-0">
                        <button type="button" className="btn btn-secondary" onClick={onClose}>Cancelar</button>
                        <button
                            type="button"
                            className="btn btn-primary"
                            disabled={selected.length === 0}
                            onClick={() => onConfirm(selected)}
                        >
                            <i className="bi bi-file-zip me-1"></i> Descargar ZIP
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}
