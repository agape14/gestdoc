import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import FolderModal from '@/Components/FolderModal';
import ContractModal from '@/Components/ContractModal';
import PdfViewerModal from '@/Components/PdfViewerModal';

export default function Index({ folders = [], contracts = [], currentFolder = null, breadcrumb = [], allFolders = [], flash, filters = {} }) {
    const [showFolderModal, setShowFolderModal] = useState(false);
    const [showContractModal, setShowContractModal] = useState(false);
    const [showPdfModal, setShowPdfModal] = useState(false);
    const [editingFolder, setEditingFolder] = useState(null);
    const [editingContract, setEditingContract] = useState(null);
    const [viewingContract, setViewingContract] = useState(null);

    // Filtros
    const [search, setSearch] = useState(filters.search || '');
    const [status, setStatus] = useState(filters.status || '');
    const [currency, setCurrency] = useState(filters.currency || '');
    const [dateStart, setDateStart] = useState(filters.date_start || '');
    const [dateEnd, setDateEnd] = useState(filters.date_end || '');

    // Aplicar filtros con debounce
    useEffect(() => {
        if (!currentFolder) return;

        const timer = setTimeout(() => {
            const hasChanges =
                search !== (filters.search || '') ||
                status !== (filters.status || '') ||
                currency !== (filters.currency || '') ||
                dateStart !== (filters.date_start || '') ||
                dateEnd !== (filters.date_end || '');

            if (hasChanges) {
                router.get(
                    route('folders.show', currentFolder.id),
                    { search, status, currency, date_start: dateStart, date_end: dateEnd },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        replace: true,
                    }
                );
            }
        }, 300);

        return () => clearTimeout(timer);
    }, [search, status, currency, dateStart, dateEnd]);

    const handleCreateFolder = () => {
        setEditingFolder(null);
        setShowFolderModal(true);
    };

    const handleEditFolder = (folder) => {
        setEditingFolder(folder);
        setShowFolderModal(true);
    };

    const handleDeleteFolder = (folder) => {
        if (folder.is_system) {
            Swal.fire({
                icon: 'error',
                title: 'No permitido',
                text: 'No se pueden eliminar carpetas del sistema',
            });
            return;
        }

        Swal.fire({
            title: '¿Estás seguro?',
            text: "Se eliminará la carpeta y todo su contenido",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('folders.destroy', folder.id), {
                    preserveScroll: true,
                });
            }
        });
    };

    const handleCreateContract = () => {
        setEditingContract(null);
        setShowContractModal(true);
    };

    const handleEditContract = (contract) => {
        setEditingContract(contract);
        setShowContractModal(true);
    };

    const handleDeleteContract = (contract) => {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "Se eliminará el contrato permanentemente",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                router.delete(route('contracts.destroy', contract.id), {
                    preserveScroll: true,
                });
            }
        });
    };

    const handleViewPdf = (contract) => {
        setViewingContract(contract);
        setShowPdfModal(true);
    };

    const handleExportExcel = () => {
        const params = new URLSearchParams({
            search: search || '',
            status: status || '',
            currency: currency || '',
            date_start: dateStart || '',
            date_end: dateEnd || '',
        });

        window.location.href = route('folders.export-contracts', currentFolder.id) + '?' + params.toString();
    };

    const clearFilters = () => {
        setSearch('');
        setStatus('');
        setCurrency('');
        setDateStart('');
        setDateEnd('');
    };

    const formatCurrency = (amount, curr) => {
        const formatted = new Intl.NumberFormat('es-PE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);

        return `${curr === 'USD' ? '$' : 'S/'} ${formatted}`;
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('es-PE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }).format(date);
    };

    const getIconClass = (iconName) => {
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
        return iconMap[iconName] || 'bi-folder-fill';
    };

    return (
        <MainLayout>
            <Head title="Gestión Documental" />

            {flash?.success && (
                <div className="alert alert-success alert-dismissible fade show" role="alert">
                    {flash.success}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            )}

            <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 className="fw-bold text-body mb-0">Gestión Documental</h2>
                    <p className="text-secondary mb-0">Sistema de organización jerárquica de contratos</p>
                </div>
                <div className="d-flex gap-2">
                    {currentFolder && contracts && contracts.length > 0 && (
                        <button onClick={handleExportExcel} className="btn btn-success shadow-sm rounded-pill px-4">
                            <i className="bi bi-file-earmark-excel me-2"></i>
                            Exportar Excel
                        </button>
                    )}
                    {currentFolder && (
                        <button onClick={handleCreateContract} className="btn btn-success shadow-sm rounded-pill px-4">
                            <i className="bi bi-file-earmark-plus me-2"></i>
                            Nuevo Contrato
                        </button>
                    )}
                    <button onClick={handleCreateFolder} className="btn btn-primary shadow-sm rounded-pill px-4">
                        <i className="bi bi-folder-plus me-2"></i>
                        Nueva Carpeta
                    </button>
                </div>
            </div>

            {/* Breadcrumb */}
            {breadcrumb && breadcrumb.length > 0 && (
                <nav aria-label="breadcrumb" className="mb-4">
                    <ol className="breadcrumb bg-body-tertiary rounded-3 p-3">
                        <li className="breadcrumb-item">
                            <Link href={route('folders.index')} className="text-decoration-none">
                                <i className="bi bi-house-door-fill me-1"></i>
                                Inicio
                            </Link>
                        </li>
                        {breadcrumb.map((folder, index) => (
                            <li
                                key={folder.id}
                                className={`breadcrumb-item ${index === breadcrumb.length - 1 ? 'active' : ''}`}
                            >
                                {index === breadcrumb.length - 1 ? (
                                    folder.name
                                ) : (
                                    <Link href={route('folders.show', folder.id)} className="text-decoration-none">
                                        {folder.name}
                                    </Link>
                                )}
                            </li>
                        ))}
                    </ol>
                </nav>
            )}

            {/* Filtros de búsqueda - Solo si hay contratos */}
            {currentFolder && (
                <div className="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-body">
                    <div className="row g-3">
                        <div className="col-lg-3">
                            <div className="input-group">
                                <span className="input-group-text bg-body-tertiary border-end-0 rounded-start-pill ps-3">
                                    <i className="bi bi-search text-secondary"></i>
                                </span>
                                <input
                                    type="text"
                                    className="form-control border-start-0 bg-body-tertiary rounded-end-pill"
                                    placeholder="Buscar por proyecto, cliente..."
                                    value={search}
                                    onChange={(e) => setSearch(e.target.value)}
                                />
                            </div>
                        </div>
                        <div className="col-lg-2">
                            <select
                                className="form-select rounded-pill bg-body-tertiary border-0 px-3"
                                value={status}
                                onChange={(e) => setStatus(e.target.value)}
                            >
                                <option value="">Todos los estados</option>
                                <option value="completo">Completo</option>
                                <option value="incompleto">Incompleto</option>
                            </select>
                        </div>
                        <div className="col-lg-2">
                            <select
                                className="form-select rounded-pill bg-body-tertiary border-0 px-3"
                                value={currency}
                                onChange={(e) => setCurrency(e.target.value)}
                            >
                                <option value="">Todas las monedas</option>
                                <option value="PEN">Soles (PEN)</option>
                                <option value="USD">Dólares (USD)</option>
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
                        <div className="col-lg-1">
                            <button
                                onClick={clearFilters}
                                className="btn btn-outline-secondary rounded-pill w-100"
                                title="Limpiar filtros"
                            >
                                <i className="bi bi-x-circle"></i>
                            </button>
                        </div>
                    </div>
                </div>
            )}

            {/* Carpetas */}
            {folders && folders.length > 0 && (
                <div className="mb-4">
                    <h5 className="fw-bold text-body mb-3">
                        <i className="bi bi-folder me-2"></i>
                        Carpetas
                    </h5>
                    <div className="row g-3">
                        {folders.map((folder) => {
                            const summary = folder.contracts_summary || { total: 0, complete: 0, incomplete: 0, percentage: 0 };
                            return (
                                <div key={folder.id} className="col-md-6 col-lg-4 col-xl-3">
                                    <div
                                        className="card border-0 shadow-sm rounded-4 h-100 overflow-hidden position-relative"
                                        style={{ cursor: 'pointer', transition: 'transform 0.2s' }}
                                        onMouseEnter={(e) => e.currentTarget.style.transform = 'scale(1.02)'}
                                        onMouseLeave={(e) => e.currentTarget.style.transform = 'scale(1)'}
                                    >
                                        <Link
                                            href={route('folders.show', folder.id)}
                                            className="text-decoration-none text-body"
                                        >
                                            <div
                                                className="card-header border-0 p-4"
                                                style={{ backgroundColor: folder.color || '#EAEAEA', minHeight: '120px' }}
                                            >
                                                <div className="d-flex justify-content-between align-items-start">
                                                    <i className={`bi ${getIconClass(folder.icon)} fs-1 opacity-75`}></i>
                                                    {folder.is_system && (
                                                        <span className="badge bg-dark-subtle text-dark-emphasis border border-dark-subtle rounded-pill">
                                                            Sistema
                                                        </span>
                                                    )}
                                                </div>
                                                {summary.total > 0 && (
                                                    <div className="position-absolute bottom-0 end-0 m-3">
                                                        <span className={`badge ${summary.percentage === 100 ? 'bg-success' : 'bg-warning'} rounded-pill`}>
                                                            {summary.complete}/{summary.total}
                                                        </span>
                                                    </div>
                                                )}
                                            </div>
                                        </Link>
                                        <div className="card-body p-3">
                                            <h6 className="card-title fw-bold mb-1">{folder.name}</h6>
                                            {folder.description && (
                                                <p className="card-text text-secondary small mb-2" style={{ fontSize: '0.85rem' }}>
                                                    {folder.description}
                                                </p>
                                            )}
                                            {!folder.is_system && (
                                                <div className="d-flex gap-1 mt-2">
                                                    <button
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            handleEditFolder(folder);
                                                        }}
                                                        className="btn btn-sm btn-outline-secondary"
                                                        title="Editar"
                                                    >
                                                        <i className="bi bi-pencil"></i>
                                                    </button>
                                                    <button
                                                        onClick={(e) => {
                                                            e.preventDefault();
                                                            handleDeleteFolder(folder);
                                                        }}
                                                        className="btn btn-sm btn-outline-danger"
                                                        title="Eliminar"
                                                    >
                                                        <i className="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            )}
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                </div>
            )}

            {/* Contratos */}
            {contracts && contracts.length > 0 && (
                <div className="mb-4">
                    <h5 className="fw-bold text-body mb-3">
                        <i className="bi bi-file-earmark-text me-2"></i>
                        Contratos ({contracts.length})
                    </h5>
                    <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
                        <div className="table-responsive">
                            <table className="table table-hover align-middle mb-0">
                                <thead className="border-bottom text-secondary small text-uppercase">
                                    <tr>
                                        <th scope="col" className="ps-4 py-3">Proyecto</th>
                                        <th scope="col" className="py-3">Cliente</th>
                                        <th scope="col" className="py-3">N° Contrato</th>
                                        <th scope="col" className="py-3">Monto</th>
                                        <th scope="col" className="py-3">Fecha</th>
                                        <th scope="col" className="py-3">Estado</th>
                                        <th scope="col" className="text-end pe-4 py-3">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {contracts.map(contract => {
                                        const isComplete = contract.status === 'completo';
                                        return (
                                            <tr key={contract.id}>
                                                <td className="ps-4 py-3 fw-medium text-body">
                                                    {contract.project_name || 'Sin nombre'}
                                                </td>
                                                <td className="text-secondary">{contract.client || 'N/A'}</td>
                                                <td className="text-secondary">{contract.contract_number || 'N/A'}</td>
                                                <td className="text-body fw-bold">
                                                    {formatCurrency(contract.amount, contract.currency)}
                                                    {contract.participation_percentage < 100 && (
                                                        <div className="small text-secondary">
                                                            ({contract.participation_percentage}%)
                                                        </div>
                                                    )}
                                                </td>
                                                <td className="text-secondary">{formatDate(contract.contract_date)}</td>
                                                <td>
                                                    <span className={`badge ${isComplete ? 'bg-success' : 'bg-warning'}-subtle text-${isComplete ? 'success' : 'warning'}-emphasis border border-${isComplete ? 'success' : 'warning'}-subtle rounded-pill px-3`}>
                                                        {isComplete ? 'Completo' : 'Incompleto'}
                                                    </span>
                                                </td>
                                                <td className="text-end pe-4">
                                                    <button
                                                        onClick={() => handleViewPdf(contract)}
                                                        className="btn btn-sm btn-outline-primary me-1"
                                                        title="Ver PDF"
                                                    >
                                                        <i className="bi bi-eye"></i>
                                                    </button>
                                                    <button
                                                        onClick={() => handleEditContract(contract)}
                                                        className="btn btn-sm btn-outline-secondary me-1"
                                                        title="Editar"
                                                    >
                                                        <i className="bi bi-pencil"></i>
                                                    </button>
                                                    <button
                                                        onClick={() => handleDeleteContract(contract)}
                                                        className="btn btn-sm btn-outline-danger"
                                                        title="Eliminar"
                                                    >
                                                        <i className="bi bi-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            )}

            {/* Empty State */}
            {(!folders || folders.length === 0) && (!contracts || contracts.length === 0) && (
                <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
                    <div className="card-body text-center py-5">
                        <i className="bi bi-folder-x fs-1 text-secondary mb-3 d-block"></i>
                        <h5 className="text-body fw-bold mb-2">No hay contenido</h5>
                        <p className="text-secondary mb-4">
                            {currentFolder
                                ? 'Esta carpeta está vacía. Comienza creando una subcarpeta o un contrato.'
                                : 'Comienza creando una carpeta para organizar tus documentos.'
                            }
                        </p>
                        <button
                            onClick={currentFolder ? handleCreateContract : handleCreateFolder}
                            className="btn btn-primary rounded-pill px-4"
                        >
                            <i className={`bi ${currentFolder ? 'bi-file-earmark-plus' : 'bi-folder-plus'} me-2`}></i>
                            {currentFolder ? 'Crear Contrato' : 'Crear Carpeta'}
                        </button>
                    </div>
                </div>
            )}

            {/* Modales */}
            <FolderModal
                show={showFolderModal}
                onClose={() => setShowFolderModal(false)}
                folder={editingFolder}
                parentId={currentFolder?.id}
            />

            <ContractModal
                show={showContractModal}
                onClose={() => setShowContractModal(false)}
                contract={editingContract}
                folderId={currentFolder?.id}
                allFolders={allFolders}
            />

            <PdfViewerModal
                show={showPdfModal}
                onClose={() => setShowPdfModal(false)}
                contract={viewingContract}
            />
        </MainLayout>
    );
}
