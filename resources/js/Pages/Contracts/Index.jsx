import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import PdfModal from '@/Components/PdfModal';

export default function Index({ contratos, filters, flash }) {
    const [search, setSearch] = useState(filters ? filters.search : '');
    const [dateStart, setDateStart] = useState(filters ? filters.date_start : '');
    const [dateEnd, setDateEnd] = useState(filters ? filters.date_end : '');
    const [showPdfModal, setShowPdfModal] = useState(false);
    const [selectedPdfUrl, setSelectedPdfUrl] = useState('');
    const [selectedPdfTitle, setSelectedPdfTitle] = useState('');

    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== (filters?.search || '') || dateStart !== (filters?.date_start || '') || dateEnd !== (filters?.date_end || '')) {
                router.get(route('contracts.index'), { search, date_start: dateStart, date_end: dateEnd }, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search, dateStart, dateEnd]);

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
                router.delete(route('contracts.destroy', id));
            }
        });
    };

    const handleViewPdf = (pdfPath, title) => {
        setSelectedPdfUrl(`/storage/${pdfPath}`);
        setSelectedPdfTitle(title || 'Ver Contrato');
        setShowPdfModal(true);
    };

    const closePdfModal = () => {
        setShowPdfModal(false);
        setSelectedPdfUrl('');
        setSelectedPdfTitle('');
    };

    return (
        <MainLayout>
            <Head title="Contratos" />

            {flash?.success && (
                <div className="alert alert-success alert-dismissible fade show" role="alert">
                    {flash.success}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            )}

            <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 className="fw-bold text-body mb-0">Contratos</h2>
                    <p className="text-secondary mb-0">Gestión de contratos y documentación</p>
                </div>
                <Link href={route('contracts.create')} className="btn btn-primary shadow-sm rounded-pill px-4">
                    <i className="bi bi-plus-lg me-2"></i>
                    Nuevo Contrato
                </Link>
            </div>

            <div className="card border-0 shadow-sm rounded-4 p-3 mb-4 bg-body">
                <div className="row g-3 items-center">
                    <div className="col-lg-6">
                        <div className="input-group">
                            <span className="input-group-text bg-body-tertiary border-end-0 rounded-start-pill ps-3"><i className="bi bi-search text-secondary"></i></span>
                            <input
                                type="text"
                                className="form-control border-start-0 bg-body-tertiary rounded-end-pill"
                                placeholder="Buscar por proyecto o cliente..."
                                value={search || ''}
                                onChange={(e) => setSearch(e.target.value)}
                            />
                        </div>
                    </div>
                    <div className="col-lg-3">
                        <input
                            type="date"
                            className="form-control rounded-pill bg-body-tertiary border-0 px-3"
                            placeholder="Fecha Firma Inicio"
                            value={dateStart || ''}
                            onChange={(e) => setDateStart(e.target.value)}
                        />
                    </div>
                    <div className="col-lg-3">
                        <input
                            type="date"
                            className="form-control rounded-pill bg-body-tertiary border-0 px-3"
                            placeholder="Fecha Firma Fin"
                            value={dateEnd || ''}
                            onChange={(e) => setDateEnd(e.target.value)}
                        />
                    </div>
                </div>
            </div>

            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
                <div className="table-responsive">
                    <table className="table table-hover align-middle mb-0">
                        <thead className="border-bottom text-secondary small text-uppercase">
                            <tr>
                                <th scope="col" className="ps-4 py-3">Proyecto</th>
                                <th scope="col" className="py-3">Cliente</th>
                                <th scope="col" className="py-3">Fecha Firma</th>
                                <th scope="col" className="py-3">Archivo</th>
                                <th scope="col" className="text-end pe-4 py-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            {contratos.data.length === 0 ? (
                                <tr>
                                    <td colSpan="5" className="text-center py-5 text-muted">
                                        No hay contratos registrados.
                                    </td>
                                </tr>
                            ) : (
                                contratos.data.map((contrato) => (
                                    <tr key={contrato.id}>
                                        <td className="ps-4 py-3 fw-medium text-body">
                                            {contrato.licitacion?.titulo || 'N/A'}
                                        </td>
                                        <td className="text-secondary">{contrato.licitacion?.entidad || 'N/A'}</td>
                                        <td className="text-secondary">
                                            {contrato.fecha_firma ? (() => {
                                                try {
                                                    const date = new Date(contrato.fecha_firma);
                                                    if (isNaN(date.getTime())) return contrato.fecha_firma;
                                                    const day = String(date.getDate()).padStart(2, '0');
                                                    const month = String(date.getMonth() + 1).padStart(2, '0');
                                                    const year = date.getFullYear();
                                                    return `${day}/${month}/${year}`;
                                                } catch (e) {
                                                    return contrato.fecha_firma;
                                                }
                                            })() : '-'}
                                        </td>
                                        <td>
                                            {contrato.archivo_path ? (
                                                <button 
                                                    onClick={() => handleViewPdf(contrato.archivo_path, contrato.licitacion?.titulo || 'Contrato')}
                                                    className="btn btn-sm btn-outline-danger border-0 text-danger text-decoration-none p-0"
                                                    title="Ver PDF"
                                                >
                                                    <i className="bi bi-file-earmark-pdf me-1"></i> PDF
                                                </button>
                                            ) : (
                                                <span className="text-muted">-</span>
                                            )}
                                        </td>
                                        <td className="text-end pe-4">
                                            <Link href={route('contracts.edit', contrato.id)} className="btn btn-sm btn-outline-secondary me-1">
                                                <i className="bi bi-pencil"></i>
                                            </Link>
                                            <button onClick={() => handleDelete(contrato.id)} className="btn btn-sm btn-outline-danger">
                                                <i className="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                ))
                            )}
                        </tbody>
                    </table>
                </div>
                {contratos.links && contratos.links.length > 3 && (
                    <div className="card-footer bg-body border-top-0 py-3">
                        <nav aria-label="Page navigation">
                            <ul className="pagination justify-content-center mb-0">
                                {contratos.links.map((link, key) => (
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

            <PdfModal
                show={showPdfModal}
                onClose={closePdfModal}
                pdfUrl={selectedPdfUrl}
                title={selectedPdfTitle}
            />
        </MainLayout>
    );
}
