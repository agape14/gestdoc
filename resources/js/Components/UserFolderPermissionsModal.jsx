import React, { useState, useEffect } from 'react';
import Modal from '@/Components/Modal';

export default function UserFolderPermissionsModal({ show, onClose, userId, userName }) {
    const [user, setUser] = useState(null);
    const [foldersByModule, setFoldersByModule] = useState({});
    const [menuLabels, setMenuLabels] = useState({});
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [activeTab, setActiveTab] = useState('');
    const [selected, setSelected] = useState({}); // { folders: [1,2], cvs: [3] }
    const [message, setMessage] = useState(null);

    useEffect(() => {
        if (show && userId) {
            setLoading(true);
            setMessage(null);
            window.axios
                .get(route('users.folder-permissions', userId))
                .then(({ data }) => {
                    setUser(data.user);
                    setFoldersByModule(data.foldersByModule || {});
                    setMenuLabels(data.menuLabels || {});
                    const keys = Object.keys(data.foldersByModule || {}).filter((k) => (data.foldersByModule[k] || []).length > 0);
                    if (keys.length) setActiveTab(keys[0]);
                    const allowed = data.user?.allowed_folders || {};
                    setSelected(allowed);
                })
                .catch(() => setMessage('Error al cargar las carpetas.'))
                .finally(() => setLoading(false));
        }
    }, [show, userId]);

    const toggleFolder = (menuKey, folderId) => {
        setSelected((prev) => {
            const arr = prev[menuKey] || [];
            const has = arr.includes(folderId);
            const next = { ...prev };
            next[menuKey] = has ? arr.filter((id) => id !== folderId) : [...arr, folderId];
            return next;
        });
    };

    const toggleAllInTab = (menuKey, checked) => {
        const folders = foldersByModule[menuKey] || [];
        const ids = folders.map((f) => f.id);
        setSelected((prev) => {
            const next = { ...prev };
            next[menuKey] = checked ? ids : [];
            return next;
        });
    };

    const handleSave = () => {
        setSaving(true);
        setMessage(null);
        window.axios
            .put(route('users.folder-permissions.update', userId), { allowed_folders: selected })
            .then(() => {
                setMessage({ type: 'success', text: 'Carpetas guardadas correctamente.' });
            })
            .catch(() => setMessage({ type: 'error', text: 'Error al guardar.' }))
            .finally(() => setSaving(false));
    };

    if (!show) return null;

    const tabKeys = Object.keys(foldersByModule).filter((k) => (foldersByModule[k] || []).length > 0);

    return (
        <Modal show={show} onClose={onClose} maxWidth="2xl">
            <div className="p-4">
                <h5 className="fw-bold mb-2">
                    <i className="bi bi-folder2 me-2"></i>
                    Carpetas visibles para el visualizador
                    {userName && <span className="text-secondary ms-2">({userName})</span>}
                </h5>
                {message && (
                    <div className={`alert alert-${message.type === 'success' ? 'success' : 'danger'} py-2 mb-3`}>
                        {message.text}
                    </div>
                )}
                {loading ? (
                    <p className="text-secondary">Cargando...</p>
                ) : (
                    <>
                        {tabKeys.length === 0 ? (
                            <p className="text-secondary">No hay carpetas en ningún módulo.</p>
                        ) : (
                            <>
                                <ul className="nav nav-tabs border-0 mb-3">
                                    {tabKeys.map((key) => (
                                        <li key={key} className="nav-item">
                                            <button
                                                type="button"
                                                className={`nav-link rounded-top ${activeTab === key ? 'active bg-white border-bottom-0' : 'border-0 bg-transparent text-secondary'}`}
                                                onClick={() => setActiveTab(key)}
                                            >
                                                {menuLabels[key] || key}
                                            </button>
                                        </li>
                                    ))}
                                </ul>
                                <div className="border rounded-bottom p-3 bg-light" style={{ minHeight: '200px' }}>
                                    {activeTab && foldersByModule[activeTab] && (
                                        <>
                                            <div className="mb-2">
                                                <label className="form-check small">
                                                    <input
                                                        type="checkbox"
                                                        className="form-check-input me-2"
                                                        checked={
                                                            (foldersByModule[activeTab] || []).length > 0 &&
                                                            (selected[activeTab] || []).length === (foldersByModule[activeTab] || []).length
                                                        }
                                                        onChange={(e) => toggleAllInTab(activeTab, e.target.checked)}
                                                    />
                                                    Seleccionar todas
                                                </label>
                                            </div>
                                            <div className="d-flex flex-column gap-1" style={{ maxHeight: '280px', overflowY: 'auto' }}>
                                                {(foldersByModule[activeTab] || []).map((folder) => (
                                                    <label key={folder.id} className="form-check d-flex align-items-center">
                                                        <input
                                                            type="checkbox"
                                                            className="form-check-input me-2"
                                                            checked={(selected[activeTab] || []).includes(folder.id)}
                                                            onChange={() => toggleFolder(activeTab, folder.id)}
                                                        />
                                                        <span className="text-body">{folder.name}{folder.creator_name ? ` — ${folder.creator_name}` : ''}</span>
                                                    </label>
                                                ))}
                                            </div>
                                        </>
                                    )}
                                </div>
                            </>
                        )}
                        <div className="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" className="btn btn-outline-secondary" onClick={onClose}>
                                Cerrar
                            </button>
                            {!loading && tabKeys.length > 0 && (
                                <button type="button" className="btn btn-primary" onClick={handleSave} disabled={saving}>
                                    {saving ? 'Guardando...' : 'Guardar'}
                                </button>
                            )}
                        </div>
                    </>
                )}
            </div>
        </Modal>
    );
}
