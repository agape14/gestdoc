import React, { useState, useEffect } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router } from '@inertiajs/react';
import Swal from 'sweetalert2';
import PdfModal from '@/Components/PdfModal';

export default function Index({ cvs, filters, flash }) {
    const [search, setSearch] = useState(filters.search || '');
    const [especialidad, setEspecialidad] = useState(filters.especialidad || '');
    const [dateStart, setDateStart] = useState(filters.date_start || '');
    const [dateEnd, setDateEnd] = useState(filters.date_end || '');
    const [showPdfModal, setShowPdfModal] = useState(false);
    const [selectedPdfUrl, setSelectedPdfUrl] = useState('');
    const [selectedPdfTitle, setSelectedPdfTitle] = useState('');

    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== (filters.search || '') || especialidad !== (filters.especialidad || '') || dateStart !== (filters.date_start || '') || dateEnd !== (filters.date_end || '')) {
                router.get(route('cvs.index'), { search, especialidad, date_start: dateStart, date_end: dateEnd }, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                });
            }
        }, 300);
        return () => clearTimeout(timer);
    }, [search, especialidad, dateStart, dateEnd]);

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

            <div className="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
                <div>
                    <h2 className="fw-bold text-body mb-0">Banco de CVs</h2>
                    <p className="text-secondary mb-0">Registro y búsqueda de talento</p>
                </div>
                <Link href={route('cvs.create')} className="btn btn-primary shadow-sm rounded-pill px-4">
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
                </div>
            </div>

            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
                <div className="table-responsive">
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
                </div>
                {cvs.links.length > 3 && (
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

            <PdfModal
                show={showPdfModal}
                onClose={closePdfModal}
                pdfUrl={selectedPdfUrl}
                title={selectedPdfTitle}
            />
        </MainLayout>
    );
}
