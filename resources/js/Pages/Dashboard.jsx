import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head } from '@inertiajs/react';

export default function Dashboard({ auth }) {
    // Placeholder data
    const stats = [
        { label: 'Total Contratos', value: '12', icon: 'bi-file-text', color: 'primary' },
        { label: 'Licitaciones en Curso', value: '5', icon: 'bi-briefcase', color: 'warning' },
        { label: 'CVs Registrados', value: '48', icon: 'bi-people', color: 'success' },
    ];

    const activities = [
        { id: 1, action: 'Nuevo Contrato firmado: Proyecto Alpha', user: 'Juan Perez', time: 'Hace 2 horas', status: 'Nuevo' },
        { id: 2, action: 'Licitación "Hosp. Central" actualizada', user: 'Maria Lopez', time: 'Hace 5 horas', status: 'Revisión' },
        { id: 3, action: 'CV subido: Arquitecto Senior', user: 'Sistema', time: 'Ayer', status: 'Pendiente' },
        { id: 4, action: 'Presupuesto aprobado #4023', user: 'Carlos Ruiz', time: 'Ayer', status: 'Aprobado' },
    ];

    return (
        <MainLayout>
            <Head title="Dashboard" />

            <div className="mb-4">
                <h2 className="fw-bold text-body mb-0">Dashboard</h2>
                <p className="text-secondary">Resumen general de gestión constructora</p>
            </div>

            {/* Stats Cards */}
            <div className="row g-4 mb-5">
                {stats.map((stat, index) => (
                    <div key={index} className="col-md-4">
                        <div className={`card bg-body border-0 shadow-sm h-100 border-start border-4 border-${stat.color}`}>
                            <div className="card-body d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 className="text-muted text-uppercase mb-2 fw-semibold" style={{ fontSize: '0.85rem' }}>{stat.label}</h6>
                                    <h3 className="fw-bold mb-0 text-body">{stat.value}</h3>
                                </div>
                                <div className={`text-${stat.color} bg-${stat.color}-subtle rounded-3 d-flex align-items-center justify-content-center`} style={{ width: '56px', height: '56px' }}>
                                    <i className={`bi ${stat.icon} fs-4`}></i>
                                </div>
                            </div>
                        </div>
                    </div>
                ))}
            </div>

            {/* Recent Activity Table */}
            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
                <div className="card-header bg-body py-3 px-4 border-bottom border-light d-flex align-items-center justify-content-between">
                    <h5 className="mb-0 fw-bold text-body">Actividad Reciente</h5>
                    <button className="btn btn-sm btn-outline-secondary">Ver todo</button>
                </div>
                <div className="table-responsive">
                    <table className="table table-hover align-middle mb-0">
                        <thead className="border-bottom text-secondary small text-uppercase">
                            <tr>
                                <th scope="col" className="ps-4 py-3">Descripción</th>
                                <th scope="col" className="py-3">Usuario</th>
                                <th scope="col" className="py-3">Fecha</th>
                                <th scope="col" className="text-end pe-4 py-3">Estado</th>
                            </tr>
                        </thead>
                        <tbody>
                            {activities.map((item) => (
                                <tr key={item.id}>
                                    <td className="ps-4 py-3">
                                        <div className="d-flex align-items-center">
                                            <div className="bg-body-tertiary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style={{ width: '40px', height: '40px' }}>
                                                <i className="bi bi-clock-history text-secondary"></i>
                                            </div>
                                            <span className="fw-medium text-body">{item.action}</span>
                                        </div>
                                    </td>
                                    <td className="text-secondary">{item.user}</td>
                                    <td><small className="text-muted">{item.time}</small></td>
                                    <td className="text-end pe-4">
                                        <span className="badge bg-body-secondary text-body border fw-normal px-3 py-2 rounded-pill">{item.status}</span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </MainLayout>
    );
}
