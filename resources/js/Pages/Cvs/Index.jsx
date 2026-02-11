import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';
import Swal from 'sweetalert2';
import PdfModal from '@/Components/PdfModal';
import FolderCardModule from '@/Components/FolderCardModule';
import ModuleFolderEditModal from '@/Components/ModuleFolderEditModal';
import ModuleFolderModal from '@/Components/ModuleFolderModal';

const getIconClass = (iconName) => {
    const iconMap = { Lock: 'bi-lock-fill', Globe: 'bi-globe', Folder: 'bi-folder-fill', Building: 'bi-building' };
    return iconMap[iconName] || 'bi-folder-fill';
};

export default function Index({ cvs, filters, flash, folders = [], currentFolder = null, breadcrumb = [], userRole, operadores = [], anulados = [] }) {
    const { auth } = usePage().props;
    const currentUserRole = userRole || auth?.user?.role || 'Visualizador';
    const isAdmin = currentUserRole === 'Administrador';
    const [search, setSearch] = useState(filters.search || '');
    const [especialidad, setEspecialidad] = useState(filters.especialidad || '');
    const [dateStart, setDateStart] = useState(filters.date_start || '');
    const [dateEnd, setDateEnd] = useState(filters.date_end || '');
    const [operatorId, setOperatorId] = useState(filters.user_id || '');
    const [editingFolder, setEditingFolder] = useState(null);
    const [showFolderModal, setShowFolderModal] = useState(false);
    const [tabActivo, setTabActivo] = useState('activos');
    const [showPdfModal, setShowPdfModal] = useState(false);
    const [selectedPdfUrl, setSelectedPdfUrl] = useState('');
    const [selectedPdfTitle, setSelectedPdfTitle] = useState('');

    const breadcrumbTitle = (breadcrumb && breadcrumb.length > 0) ? breadcrumb.map(f => f.name).join(' / ') : (currentFolder?.name || 'Banco de CVs');
    const buildParams = (extra = {}) => ({ ...filters, ...extra });

    useEffect(() => {
        const timer = setTimeout(() => {
            const params = { search, especialidad, date_start: dateStart, date_end: dateEnd, folder_id: filters.folder_id };
            if (isAdmin) params.user_id = operatorId || undefined;
            if (search !== (filters.search || '') || especialidad !== (filters.especialidad || '') || dateStart !== (filters.date_start || '') || dateEnd !== (filters.date_end || '') || operatorId !== (filters.user_id || '')) {
                router.get(route('cvs.index'), params, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search, especialidad, dateStart, dateEnd, operatorId]);

    const handleDelete = (id) => {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "No podrás revertir esta acción",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('cvs.destroy', id));
            }
        });
    };

    const canEditOrDeleteFolder = (folder) => {
        if (currentUserRole === 'Administrador') return true;
        if (currentUserRole === 'Operador') return folder.user_id === auth?.user?.id;
        return false;
    };

    const handleDeleteFolder = (folder) => {
        if (folder?.is_system) {
            Swal.fire({ icon: 'error', title: 'No permitido', text: 'No se pueden eliminar carpetas del sistema' });
            return;
        }
        Swal.fire({
            title: '¿Eliminar carpeta?',
            text: 'Se eliminará la carpeta y su contenido. Esta acción no se puede deshacer.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar',
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('folders.destroy', folder.id), { preserveScroll: true });
            }
        });
    };

    const handleViewPdf = (pdfPath, title) => {
        setSelectedPdfUrl(`/storage/${pdfPath}`);
        setSelectedPdfTitle(title || 'Ver CV');
        setShowPdfModal(true);
    };

    const closePdfModal = () => {
        setShowPdfModal(false);
        setSelectedPdfUrl('');
        setSelectedPdfTitle('');
    };

    return (
        <MainLayout>
            <Head title="Banco de CVs" />

            {flash?.success && (
                <div className="alert alert-success alert-dismissible fade show" role="alert">
                    {flash.success}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            )}

            {breadcrumb && breadcrumb.length > 0 && (
                <nav aria-label="breadcrumb" className="mb-3">
                    <ol className="breadcrumb bg-body-tertiary rounded-3 p-3">
                        <li className="breadcrumb-item">
                            <Link href={route('cvs.index')} className="text-decoration-none"><i className="bi bi-house-door-fill me-1"></i> Banco de CVs</Link>
                        </li>
                        {breadcrumb.map((folder, index) => (
                            <li key={folder.id} className={`breadcrumb-item ${index === breadcrumb.length - 1 ? 'active' : ''}`}>
                                {index === breadcrumb.length - 1 ? folder.name : <Link href={route('cvs.index', { folder_id: folder.id })} className="text-decoration-none">{folder.name}</Link>}
                            </li>
                        ))}
                    </ol>
                </nav>
            )}

            {(folders?.length > 0 || currentUserRole !== 'Visualizador') && (
                <div className="mb-4">
                    <div className="d-flex justify-content-between align-items-center mb-3">
                        <h5 className="fw-bold text-body mb-0"><i className="bi bi-folder me-2"></i>Carpetas</h5>
                        {currentUserRole !== 'Visualizador' && (
                            <button type="button" className="btn btn-primary rounded-pill px-3" onClick={() => setShowFolderModal(true)}>
                                <i className="bi bi-folder-plus me-2"></i> Nueva carpeta
                            </button>
                        )}
                    </div>
                    {folders && folders.length > 0 && (
                    <div className="row g-3">
                        {folders.map((folder) => (
                            <FolderCardModule
                                key={folder.id}
                                folder={folder}
                                indexRoute="cvs.index"
                                indexParams={buildParams()}
                                canEditFolder={canEditOrDeleteFolder(folder)}
                                onEdit={(f) => setEditingFolder(f)}
                                onDelete={handleDeleteFolder}
                            />
                        ))}
                    </div>
                    )}
                </div>
            )}

            <ModuleFolderModal
                show={showFolderModal}
                onClose={() => setShowFolderModal(false)}
                storeFolderRoute="cvs.folders.store"
                parentId={currentFolder?.id ?? null}
            />

            <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 className="fw-bold text-body mb-0">Banco de CVs</h2>
                    <p className="text-secondary mb-0">Registro y búsqueda de talento</p>
                </div>
                <Link href={currentFolder?.id ? route('cvs.create', { folder_id: currentFolder.id }) : route('cvs.create')} className="btn btn-primary shadow-sm rounded-pill px-4">
                    <i className="bi bi-upload me-2"></i>
                    Subir CV
                </Link>
            </div>

            <div className="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-body">
                <div className="row g-3 items-center">
                    <div className="col-lg-4">
                        <div className="input-group">
                            <span className="input-group-text bg-body-tertiary border-end-0 rounded-start-pill ps-3"><i className="bi bi-search text-secondary"></i></span>
                            <input
                                type="text"
                                className="form-control border-start-0 bg-body-tertiary rounded-end-pill"
                                placeholder="Buscar por nombre..."
                                value={search}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </div>
                    <div className="col-lg-4">
                        <select
                            className="form-select rounded-pill bg-body-tertiary border-0 px-3 text-secondary"
                            value={especialidad}
                            onChange={(e) => setEspecialidad(e.target.value)}
                        >
                            <option value="">Todas las especialidades</option>
                            <option value="Arquitecto">Arquitecto</option>
                            <option value="Ingeniero Civil">Ingeniero Civil</option>
                            <option value="Ingeniero Eléctrico">Ingeniero Eléctrico</option>
                            <option value="Seguridad">Seguridad</option>
                            <option value="Maestro de Obra">Maestro de Obra</option>
                        </select>
                    </div>
                    <div className="col-lg-2">
                        <input
                            type="date"
                            className="form-control rounded-pill bg-body-tertiary border-0 px-3"
                            placeholder="Fecha Inicio"
                            value={dateStart}
                            onChange={(e) => setDateStart(e.target.value)}
                        />
                    </div>
                    <div className="col-lg-2">
                        <input
                            type="date"
                            className="form-control rounded-pill bg-body-tertiary border-0 px-3"
                            placeholder="Fecha Fin"
                            value={dateEnd}
                            onChange={(e) => setDateEnd(e.target.value)}
                        />
                    </div>
                    {isAdmin && operadores.length > 0 && (
                        <div className="col-lg-3">
                            <select
                                className="form-select rounded-pill bg-body-tertiary border-0 px-3 text-secondary"
                                value={operatorId}
                                onChange={(e) => setOperatorId(e.target.value)}
                            >
                                <option value="">Todos los operadores</option>
                                {operadores.map(op => (<option key={op.id} value={op.id}>{op.name}</option>))}
                            </select>
                        </div>
                    )}
                </div>
            </div>

            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
                {isAdmin && Array.isArray(anulados) && (
                    <ul className="nav nav-tabs card-header-tabs border-0 px-4 pt-3">
                        <li className="nav-item">
                            <button type="button" className={`nav-link rounded-top-pill ${tabActivo === 'activos' ? 'active bg-white border-bottom-0 fw-semibold' : 'border-0 bg-transparent text-secondary'}`} onClick={() => setTabActivo('activos')}>
                                Activos {cvs.data?.length != null && <span className="badge bg-primary-subtle text-primary ms-1">{cvs.data.length}</span>}
                            </button>
                        </li>
                        <li className="nav-item">
                            <button type="button" className={`nav-link rounded-top-pill ${tabActivo === 'anulados' ? 'active bg-white border-bottom-0 fw-semibold' : 'border-0 bg-transparent text-secondary'}`} onClick={() => setTabActivo('anulados')}>
                                Anulados {anulados.length > 0 && <span className="badge bg-secondary-subtle text-secondary ms-1">{anulados.length}</span>}
                            </button>
                        </li>
                    </ul>
                )}
                <div className="table-responsive">
                    {tabActivo === 'anulados' && isAdmin ? (
                        <table className="table table-hover align-middle mb-0">
                            <thead className="border-bottom text-secondary small text-uppercase">
                                <tr>
                                    <th scope="col" className="ps-4 py-3">Candidato</th>
                                    <th scope="col" className="py-3">Especialidad</th>
                                    <th scope="col" className="py-3">Fecha Registro</th>
                                    <th scope="col" className="text-center py-3">CV</th>
                                    <th scope="col" className="text-end pe-4 py-3">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                {anulados.length > 0 ? anulados.map(cv => (
                                    <tr key={cv.id} className="table-secondary">
                                        <td className="ps-4 py-3">{cv.nombre_candidato}</td>
                                        <td className="text-secondary">{cv.especialidad}</td>
                                        <td className="text-secondary">{new Date(cv.created_at).toLocaleDateString('es-PE', { timeZone: 'America/Lima' })}</td>
                                        <td className="text-center">
                                            {cv.archivo_cv ? (
                                                <button type="button" onClick={() => handleViewPdf(cv.archivo_cv, `CV - ${cv.nombre_candidato}`)} className="btn btn-sm btn-outline-primary"><i className="bi bi-file-earmark-pdf"></i></button>
                                            ) : <span className="text-muted">-</span>}
                                        </td>
                                        <td className="text-end pe-4"><span className="badge bg-secondary">Anulado</span></td>
                                    </tr>
                                )) : (
                                    <tr><td colSpan="5" className="text-center py-4 text-muted">No hay CVs anulados.</td></tr>
                                )}
                            </tbody>
                        </table>
                    ) : (
                    <table className="table table-hover align-middle mb-0">
                        <thead className="border-bottom text-secondary small text-uppercase">
                            <tr>
                                <th scope="col" className="ps-4 py-3">Candidato</th>
                                <th scope="col" className="py-3">Especialidad</th>
                                <th scope="col" className="py-3">Fecha Registro</th>
                                <th scope="col" className="text-center py-3">CV</th>
                                <th scope="col" className="text-end pe-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {cvs.data.length > 0 ? cvs.data.map(cv => (
                                <tr key={cv.id}>
                                    <td className="ps-4 py-3 fw-medium text-body">{cv.nombre_candidato}</td>
                                    <td className="text-secondary">{cv.especialidad}</td>
                                    <td className="text-secondary">{new Date(cv.created_at).toLocaleDateString('es-PE', { timeZone: 'America/Lima' })}</td>
                                    <td className="text-center">
                                        {cv.archivo_cv ? (
                                            <button
                                                onClick={() => handleViewPdf(cv.archivo_cv, `CV - ${cv.nombre_candidato}`)}
                                                className="btn btn-sm btn-outline-primary"
                                                title="Ver PDF"
                                            >
                                                <i className="bi bi-file-earmark-pdf"></i>
                                            </button>
                                        ) : (
                                            <span className="text-muted">-</span>
                                        )}
                                    </td>
                                    <td className="text-end pe-4">
                                        <Link href={route('cvs.edit', cv.id)} className="btn btn-sm btn-outline-secondary me-1" title="Editar">
                                            <i className="bi bi-pencil"></i>
                                        </Link>
                                        <button onClick={() => handleDelete(cv.id)} className="btn btn-sm btn-outline-danger" title="Eliminar">
                                            <i className="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            )) : (
                                <tr>
                                    <td colSpan="5" className="text-center py-5 text-muted">
                                        No se encontraron resultados
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                    )}
                </div>
                {tabActivo === 'activos' && cvs.links && cvs.links.length > 3 && (
                    <div className="card-footer bg-body border-top-0 py-3">
                        <nav aria-label="Page navigation">
                            <ul className="pagination justify-content-center mb-0">
                                {cvs.links.map((link, key) => (
                                    <li key={key} className={`page-item ${link.active ? 'active' : ''} ${!link.url ? 'disabled' : ''}`}>
                                        <Link
                                            className="page-link"
                                            href={link.url || '#'}
                                            dangerouslySetInnerHTML={{ __html: link.label }}
                                        />
                                    </li>
                                ))}
                            </ul>
                        </nav>
                    </div>
                )}
            </div>

            {editingFolder && (
                <ModuleFolderEditModal show={!!editingFolder} onClose={() => setEditingFolder(null)} folder={editingFolder} />
            )}

            <PdfModal
                show={showPdfModal}
                onClose={closePdfModal}
                pdfUrl={selectedPdfUrl}
                title={selectedPdfTitle}
            />
        </MainLayout>
    );
}
