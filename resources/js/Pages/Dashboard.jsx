import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head } from '@inertiajs/react';
import Viewer360 from '@/Components/Viewer360';

export default function Dashboard({ auth, stats, image360 }) {
    // Preparar datos de estadísticas con valores reales
    const statsCards = [
        { 
            label: 'Total Contratos', 
            value: stats?.totalContratos || 0, 
            icon: 'bi-file-text', 
            color: 'primary' 
        },
        { 
            label: 'Licitaciones en Curso', 
            value: stats?.licitacionesEnCurso || 0, 
            icon: 'bi-briefcase', 
            color: 'warning' 
        },
        { 
            label: 'CVs Registrados', 
            value: stats?.cvsRegistrados || 0, 
            icon: 'bi-people', 
            color: 'success' 
        },
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
                {statsCards.map((stat, index) => (
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

            {/* Visor 360° */}
            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body">
                <div className="card-header bg-body py-3 px-4 border-bottom border-light d-flex align-items-center justify-content-between">
                    <div>
                        <h5 className="mb-0 fw-bold text-body">Vista Panorámica del Proyecto</h5>
                        <p className="text-secondary mb-0 small mt-1">Explora la vista 360° del sitio de construcción</p>
                    </div>
                    <button className="btn btn-sm btn-outline-primary">
                        <i className="bi bi-arrows-fullscreen me-2"></i>
                        Pantalla Completa
                    </button>
                </div>
                <div className="card-body p-0" style={{ height: '600px' }}>
                    <Viewer360 
                        imageUrl={image360 || "/images/360/default-panorama.jpg"} 
                        title="Vista 360° del Proyecto"
                    />
                </div>
            </div>
        </MainLayout>
    );
}
