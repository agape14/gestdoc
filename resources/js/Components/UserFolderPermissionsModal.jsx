import React, { useState, useEffect, useMemo } from 'react';
import Modal from '@/Components/Modal';

export default function UserFolderPermissionsModal({ show, onClose, userId, userName }) {
    const [user, setUser] = useState(null);
    const [foldersByModule, setFoldersByModule] = useState({});
    const [menuLabels, setMenuLabels] = useState({});
    const [operadores, setOperadores] = useState([]);
    const [operadorFilter, setOperadorFilter] = useState('');
    const [loading, setLoading] = useState(false);
    const [saving, setSaving] = useState(false);
    const [activeTab, setActiveTab] = useState('');
    const [selected, setSelected] = useState({}); // { folders: [1,2], cvs: [3] }
    const [message, setMessage] = useState(null);

    useEffect(() => {
        if (show && userId) {
            setLoading(true);
            setMessage(null);
            setOperadorFilter('');
            window.axios
                .get(route('users.folder-permissions', userId))
                .then(({ data }) => {
                    setUser(data.user);
                    setFoldersByModule(data.foldersByModule || {});
                    setMenuLabels(data.menuLabels || {});
                    setOperadores(data.operadores || []);
                    const keys = Object.keys(data.foldersByModule || {}).filter((k) => (data.foldersByModule[k] || []).length > 0);
                    if (keys.length) setActiveTab(keys[0]);
                    const allowed = data.user?.allowed_folders || {};
                    setSelected(allowed);
                })
                .catch(() => setMessage('Error al cargar las carpetas.'))
                .finally(() => setLoading(false));
        }
    }, [show, userId]);

    const filteredFoldersByModule = useMemo(() => {
        if (!operadorFilter) return foldersByModule;
        const operatorId = parseInt(operadorFilter, 10);
        const filtered = {};
        for (const [menuKey, folders] of Object.entries(foldersByModule)) {
            const list = (folders || []).filter((f) => (f.user_id || null) === operatorId);
            if (list.length > 0) filtered[menuKey] = list;
        }
        return filtered;
    }, [foldersByModule, operadorFilter]);

    const toggleFolder = (menuKey, folderId) => {
        setSelected((prev) => {
            const arr = prev[menuKey] || [];
            const has = arr.includes(folderId);
            const next = { ...prev };
            next[menuKey] = has ? arr.filter((id) => id !== folderId) : [...arr, folderId];
            return next;
        });
    };

    const toggleAllInTab = (menuKey, checked, foldersList = null) => {
        const folders = foldersList ?? (foldersByModule[menuKey] || []);
        const ids = folders.map((f) => f.id);
        setSelected((prev) => {
            const next = { ...prev };
            const current = prev[menuKey] || [];
            if (foldersList) {
                if (checked) {
                    next[menuKey] = [...new Set([...current, ...ids])];
                } else {
                    next[menuKey] = current.filter((id) => !ids.includes(id));
                }
            } else {
                next[menuKey] = checked ? ids : [];
            }
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

    useEffect(() => {
        const keys = Object.keys(filteredFoldersByModule).filter((k) => (filteredFoldersByModule[k] || []).length > 0);
        if (keys.length && !keys.includes(activeTab)) setActiveTab(keys[0]);
    }, [operadorFilter, filteredFoldersByModule]);

    if (!show) return null;

    const tabKeys = Object.keys(filteredFoldersByModule).filter((k) => (filteredFoldersByModule[k] || []).length > 0);
    const currentFolders = activeTab ? (filteredFoldersByModule[activeTab] || []) : [];

    return (
        <Modal show={show} onClose={onClose} maxWidth="5xl">
            <div className="p-4" style={{ maxHeight: '85vh', display: 'flex', flexDirection: 'column' }}>
                <h5 className="fw-bold mb-3">
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
                        {operadores.length > 0 && (
                            <div className="mb-3">
                                <label className="form-label small text-secondary mb-1">Buscar por Operador</label>
                                <select
                                    className="form-select form-select-sm rounded-pill"
                                    value={operadorFilter}
                                    onChange={(e) => setOperadorFilter(e.target.value)}
                                    style={{ maxWidth: '280px' }}
                                >
                                    <option value="">Todos los operadores</option>
                                    {operadores.map((op) => (
                                        <option key={op.id} value={op.id}>{op.name}</option>
                                    ))}
                                </select>
                            </div>
                        )}
                        {tabKeys.length === 0 ? (
                            <p className="text-secondary">No hay carpetas{operadorFilter ? ' para el operador seleccionado' : ''} en ningún módulo.</p>
                        ) : (
                            <div className="flex-grow-1 d-flex flex-column min-h-0">
                                <ul className="nav nav-tabs border-0 mb-0 flex-shrink-0">
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
                                <div className="border rounded-bottom p-2 bg-light flex-grow-1 d-flex flex-column min-h-0" style={{ minHeight: '280px' }}>
                                    {activeTab && currentFolders.length > 0 && (
                                        <>
                                            <div className="mb-1 flex-shrink-0">
                                                <label className="form-check small py-1">
                                                    <input
                                                        type="checkbox"
                                                        className="form-check-input me-2"
                                                        checked={
                                                            currentFolders.length > 0 &&
                                                            currentFolders.every((f) => (selected[activeTab] || []).includes(f.id))
                                                        }
                                                        onChange={(e) => toggleAllInTab(activeTab, e.target.checked, currentFolders)}
                                                    />
                                                    Seleccionar todas
                                                </label>
                                            </div>
                                            <div className="d-flex flex-column flex-grow-1 overflow-auto" style={{ minHeight: 0, gap: '2px' }}>
                                                {currentFolders.map((folder) => {
                                                    const fullName = `${folder.name}${folder.creator_name && folder.creator_name !== '—' ? ` — ${folder.creator_name}` : ''}`;
                                                    return (
                                                        <label key={folder.id} className="form-check d-flex align-items-center gap-2 min-w-0 py-1 px-2 rounded-1 ml-4" style={{ cursor: 'pointer' }}>
                                                            <input
                                                                type="checkbox"
                                                                className="form-check-input flex-shrink-0"
                                                                checked={(selected[activeTab] || []).includes(folder.id)}
                                                                onChange={() => toggleFolder(activeTab, folder.id)}
                                                            />
                                                            <span className="text-body text-truncate flex-grow-1 min-w-0" title={fullName}>{fullName}</span>
                                                        </label>
                                                    );
                                                })}
                                            </div>
                                        </>
                                    )}
                                </div>
                            </div>
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
