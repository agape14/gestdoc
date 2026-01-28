import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link } from '@inertiajs/react';

export default function Dashboard({ auth, stats }) {

    // Helper to render a card with public/private breakdown or just total
    const StatCard = ({ title, icon, color, route, data }) => {
        // Check if data is an object with public/private or just a number
        const isAdvanced = typeof data === 'object';
        const total = isAdvanced ? (data?.total || 0) : (data || 0);
        const hasPublicPrivate = isAdvanced && (data.publicas !== undefined || data.privadas !== undefined);
        const hasProfesionalEmpresa = isAdvanced && (data.profesionales !== undefined || data.empresas !== undefined);

        return (
            <div className="col">
                <div className={`card h-100 border-0 shadow-lg position-relative overflow-hidden text-white`}
                    style={{
                        background: `linear-gradient(135deg, rgba(255,255,255,0.1), rgba(255,255,255,0.05))`,
                        backdropFilter: 'blur(10px)',
                        borderTop: `4px solid var(--bs-${color})`,
                        transition: 'transform 0.3s ease, box-shadow 0.3s ease'
                    }}
                    onMouseEnter={(e) => {
                        e.currentTarget.style.transform = 'translateY(-5px)';
                        e.currentTarget.style.boxShadow = '0 10px 20px rgba(0,0,0,0.3)';
                    }}
                    onMouseLeave={(e) => {
                        e.currentTarget.style.transform = 'translateY(0)';
                        e.currentTarget.style.boxShadow = 'none';
                    }}
                >
                    <div className="card-body p-4 d-flex flex-column justify-content-between">
                        <div className="d-flex align-items-center justify-content-between mb-3">
                            <div className={`bg-${color} bg-opacity-25 rounded-circle p-3 d-flex align-items-center justify-content-center shadow-inner`} style={{ width: '60px', height: '60px' }}>
                                <i className={`bi ${icon} fs-3 text-${color} text-white`}></i>
                            </div>
                            <h2 className="display-6 fw-bold mb-0 text-white">{total}</h2>
                        </div>

                        <div>
                            <h5 className="card-title fw-bold text-uppercase mb-3 tracking-wide text-white-50 small">{title}</h5>

                            {hasPublicPrivate && (
                                <div className="d-flex justify-content-between mt-3 pt-3 border-top border-light border-opacity-25">
                                    <div className="text-center">
                                        <span className="d-block small text-white-50">PUBLICAS</span>
                                        <span className="fw-bold fs-5 text-white">{data.publicas || 0}</span>
                                    </div>
                                    <div className="vr bg-light opacity-25"></div>
                                    <div className="text-center">
                                        <span className="d-block small text-white-50">PRIVADAS</span>
                                        <span className="fw-bold fs-5 text-white">{data.privadas || 0}</span>
                                    </div>
                                </div>
                            )}

                            {hasProfesionalEmpresa && (
                                <div className="d-flex justify-content-between mt-3 pt-3 border-top border-light border-opacity-25">
                                    <div className="text-center">
                                        <span className="d-block small text-white-50">PROFESIONALES</span>
                                        <span className="fw-bold fs-5 text-white">{data.profesionales || 0}</span>
                                    </div>
                                    <div className="vr bg-light opacity-25"></div>
                                    <div className="text-center">
                                        <span className="d-block small text-white-50">EMPRESAS</span>
                                        <span className="fw-bold fs-5 text-white">{data.empresas || 0}</span>
                                    </div>
                                </div>
                            )}

                            {!hasPublicPrivate && !hasProfesionalEmpresa && (
                                <div className="mt-3 pt-3 border-top border-light border-opacity-25">
                                    <Link href={route} className="btn btn-sm btn-outline-light w-100 rounded-pill stretched-link hover-bg-light hover-text-dark transition">
                                        Explorar
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                    {/* Decorative bubble */}
                    <div className="position-absolute top-0 end-0 p-5 rounded-circle bg-gradient opacity-10"
                        style={{
                            background: `radial-gradient(circle, var(--bs-${color}), transparent)`,
                            width: '150px', height: '150px', transform: 'translate(30%, -30%)'
                        }}></div>
                </div>
            </div>
        );
    };

    return (
        <MainLayout>
            <Head title="TECCONING - Dashboard" />

            {/* Background Video/Animation Container */}
            <div className="position-fixed top-0 start-0 w-100 h-100 video-background-container" style={{ zIndex: -1 }}>
                <div className="position-absolute top-0 start-0 w-100 h-100 bg-dark"></div>
                {/* CSS Animated Background gradient as placeholder for video */}
                <div className="w-100 h-100" style={{
                    background: 'linear-gradient(45deg, #1a2a6c, #b21f1f, #fdbb2d)',
                    backgroundSize: '400% 400%',
                    animation: 'gradientBG 15s ease infinite',
                    opacity: 0.8
                }}></div>
                <div className="position-absolute top-0 start-0 w-100 h-100 bg-black bg-opacity-50 backdrop-blur-sm"></div>

                <style>{`
                    @keyframes gradientBG {
                        0% { background-position: 0% 50%; }
                        50% { background-position: 100% 50%; }
                        100% { background-position: 0% 50%; }
                    }
                    .backdrop-blur-sm { backdrop-filter: blur(3px); }

                    /* Hide dark background in light mode */
                    [data-bs-theme="light"] .video-background-container {
                        display: none !important;
                    }
                    /* Ensure text is readable in light mode (revert text-white) */
                    [data-bs-theme="light"] .text-white {
                        color: #212529 !important;
                    }
                     [data-bs-theme="light"] .text-white-50 {
                        color: #6c757d !important;
                    }
                    [data-bs-theme="light"] .card {
                         background: rgba(255,255,255,0.9) !important;
                         border: 1px solid rgba(0,0,0,0.1) !important;
                         backdrop-filter: none !important;
                    }
                `}</style>
            </div>

            <div className="container-fluid py-2">
                <div className="d-flex align-items-center justify-content-between mb-5">
                    <div>
                        <h1 className="display-5 fw-bold text-white mb-2 text-shadow">Panel de Control</h1>
                        <p className="lead text-white-50 mb-0">Bienvenido a TECCONING Gestión Integral</p>
                    </div>
                </div>

                <div className="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4 mb-5">
                    <StatCard title="Licitaciones" icon="bi-briefcase" color="primary" route="/licitaciones" data={stats.licitaciones} />
                    <StatCard title="Consultor Obras" icon="bi-person-workspace" color="success" route="/consultor-obras" data={stats.consultorObras} />
                    <StatCard title="Ejecutor Obra" icon="bi-hammer" color="warning" route="/ejecutor-obra" data={stats.ejecutorObras} />
                    <StatCard title="Prov. Servicios" icon="bi-tools" color="info" route="/proveedor-servicios" data={stats.proveedorServicios} />

                    <StatCard title="Prov. Bienes" icon="bi-box-seam" color="danger" route="/proveedor-bienes" data={stats.proveedorBienes} />
                    <StatCard title="Esp. Ejecución" icon="bi-people-hard-hat" color="secondary" route="/especialistas-ejecucion" data={stats.especialistasEjecucion} />
                    <StatCard title="Esp. Consultoría" icon="bi-people" color="light" route="/especialistas-consultoria" data={stats.especialistasConsultoria} />
                    <StatCard title="Inmobiliaria" icon="bi-buildings" color="primary" route="/inmobiliaria" data={stats.inmobiliaria} />

                    <StatCard title="Topografía" icon="bi-map" color="success" route="/topografia" data={stats.topografia} />
                    <StatCard title="Tecnología" icon="bi-pc-display" color="info" route="/tecnologia" data={stats.tecnologia} />
                    <StatCard title="Plantillas Ing" icon="bi-file-earmark-ruled" color="warning" route="/plantillas-ing" data={stats.plantillasIng} />
                    <StatCard title="Banco de CVs" icon="bi-person-lines-fill" color="danger" route="/cvs" data={stats.cvsRegistrados} />

                    <StatCard title="Gestión Doc" icon="bi-folder-fill" color="light" route="/folders" data={stats.gestionDocumental} />
                </div>
            </div>
        </MainLayout>
    );
}
