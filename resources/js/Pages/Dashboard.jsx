import React, { useState } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, Link, router, usePage } from '@inertiajs/react';

const DASHBOARD_CARDS = [
    { key: 'licitaciones', statsKey: 'licitaciones', title: 'Licitaciones', icon: 'bi-briefcase', color: 'primary', route: '/licitaciones' },
    { key: 'consultor-obras', statsKey: 'consultorObras', title: 'Consultor Obras', icon: 'bi-person-workspace', color: 'success', route: '/consultor-obras' },
    { key: 'ejecutor-obra', statsKey: 'ejecutorObras', title: 'Ejecutor Obra', icon: 'bi-hammer', color: 'warning', route: '/ejecutor-obra' },
    { key: 'proveedor-servicios', statsKey: 'proveedorServicios', title: 'Prov. Servicios', icon: 'bi-tools', color: 'info', route: '/proveedor-servicios' },
    { key: 'proveedor-bienes', statsKey: 'proveedorBienes', title: 'Prov. Bienes', icon: 'bi-box-seam', color: 'danger', route: '/proveedor-bienes' },
    { key: 'especialistas-ejecucion', statsKey: 'especialistasEjecucion', title: 'Esp. Ejecución', icon: 'bi-people-hard-hat', color: 'secondary', route: '/especialistas-ejecucion' },
    { key: 'especialistas-consultoria', statsKey: 'especialistasConsultoria', title: 'Esp. Consultoría', icon: 'bi-people', color: 'light', route: '/especialistas-consultoria' },
    { key: 'inmobiliaria', statsKey: 'inmobiliaria', title: 'Inmobiliaria', icon: 'bi-buildings', color: 'primary', route: '/inmobiliaria' },
    { key: 'topografia', statsKey: 'topografia', title: 'Topografía', icon: 'bi-map', color: 'success', route: '/topografia' },
    { key: 'tecnologia', statsKey: 'tecnologia', title: 'Tecnología', icon: 'bi-pc-display', color: 'info', route: '/tecnologia' },
    { key: 'plantillas-ing', statsKey: 'plantillasIng', title: 'Plantillas Ing', icon: 'bi-file-earmark-ruled', color: 'warning', route: '/plantillas-ing' },
    { key: 'cvs', statsKey: 'cvsRegistrados', title: 'Banco de CVs', icon: 'bi-person-lines-fill', color: 'danger', route: '/cvs' },
    { key: 'folders', statsKey: 'gestionDocumental', title: 'Gestión Doc', icon: 'bi-folder-fill', color: 'light', route: '/folders' },
];

function formatR2UpdatedAt(ts) {
    if (ts == null) return null;
    const d = new Date(ts * 1000);
    if (Number.isNaN(d.getTime())) return null;
    return d.toLocaleString('es-PE', { dateStyle: 'short', timeStyle: 'short' });
}

export default function Dashboard({ auth, stats, r2StorageUsedBytes = null, r2StorageUpdatedAt = null, r2StorageLimitBytes = 2 * 1024 ** 4 }) {
    const { props } = usePage();
    const flash = props.flash ?? {};
    const [r2Refreshing, setR2Refreshing] = useState(false);
    const user = props.auth?.user;
    const isAdmin = user?.role === 'Administrador';
    const allowedMenus = user?.allowed_menus ?? [];
    const visibleCards = isAdmin ? DASHBOARD_CARDS : DASHBOARD_CARDS.filter(c => allowedMenus.includes(c.key));

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
                                <>
                                    <div className="d-flex justify-content-between mt-3 pt-3 border-top border-light border-opacity-25">
                                        <div className="text-center">
                                            <span className="d-block small text-white-50">PÚBLICAS</span>
                                            <span className="fw-bold fs-5 text-white">{data.publicas || 0}</span>
                                        </div>
                                        <div className="vr bg-light opacity-25"></div>
                                        <div className="text-center">
                                            <span className="d-block small text-white-50">PRIVADAS</span>
                                            <span className="fw-bold fs-5 text-white">{data.privadas || 0}</span>
                                        </div>
                                    </div>
                                    <div className="mt-3 pt-2">
                                        <Link href={route} className="btn btn-sm btn-outline-light w-100 rounded-pill">Explorar</Link>
                                    </div>
                                </>
                            )}

                            {hasProfesionalEmpresa && (
                                <>
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
                                    <div className="mt-3 pt-2">
                                        <Link href={route} className="btn btn-sm btn-outline-light w-100 rounded-pill">Explorar</Link>
                                    </div>
                                </>
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

            {/* Background Video/Animation Container - scoped to avoid affecting sidebar */}
            <div className="dashboard-page position-fixed top-0 start-0 w-100 h-100 video-background-container" style={{ zIndex: -1 }}>
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
                    /* Ensure text is readable in light mode - scoped to dashboard content only (not sidebar) */
                    [data-bs-theme="light"] .dashboard-content .text-white {
                        color: #212529 !important;
                    }
                    [data-bs-theme="light"] .dashboard-content .text-white-50 {
                        color: #6c757d !important;
                    }
                    [data-bs-theme="light"] .dashboard-content .card {
                         background: rgba(255,255,255,0.9) !important;
                         border: 1px solid rgba(0,0,0,0.1) !important;
                         backdrop-filter: none !important;
                    }
                `}</style>
            </div>

            <div className="dashboard-content container-fluid py-2">
                {(flash.success || flash.error) && (
                    <div className={`alert ${flash.error ? 'alert-danger' : 'alert-success'} alert-dismissible fade show mb-3`} role="alert">
                        {flash.error || flash.success}
                        <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Cerrar" />
                    </div>
                )}
                <div className="d-flex align-items-center justify-content-between mb-5 flex-wrap gap-3">
                    <div>
                        <h1 className="display-5 fw-bold text-white mb-2 text-shadow">Panel de Control</h1>
                        <p className="lead text-white-50 mb-0">Bienvenido a TECCONING Gestión Integral</p>
                    </div>
                    {isAdmin && (r2StorageUsedBytes != null || r2StorageLimitBytes) && (
                        <div className="card border-0 shadow-sm bg-white bg-opacity-25 text-white p-3 rounded-4" style={{ minWidth: '300px' }}>
                            <div className="d-flex align-items-start gap-3">
                                <div className="bg-info bg-opacity-25 rounded-circle p-2 flex-shrink-0">
                                    <i className="bi bi-cloud-arrow-up fs-4"></i>
                                </div>
                                <div className="flex-grow-1 min-w-0">
                                    <div className="d-flex align-items-center justify-content-between gap-2 flex-wrap">
                                        <div className="small text-white-50 text-uppercase fw-semibold">Almacenamiento R2</div>
                                        <button
                                            type="button"
                                            className="btn btn-sm btn-outline-light border-white text-white"
                                            disabled={r2Refreshing}
                                            title="Recalcular uso del bucket (puede tardar si hay muchos archivos)"
                                            onClick={() => {
                                                setR2Refreshing(true);
                                                router.post('/dashboard/r2-refresh-storage', {}, {
                                                    preserveScroll: true,
                                                    onFinish: () => setR2Refreshing(false),
                                                });
                                            }}
                                        >
                                            {r2Refreshing ? (
                                                <><span className="spinner-border spinner-border-sm me-1" role="status" /> Actualizando…</>
                                            ) : (
                                                <><i className="bi bi-arrow-clockwise me-1"></i>Actualizar</>
                                            )}
                                        </button>
                                    </div>
                                    <div className="fw-bold mt-1">
                                        {r2StorageUsedBytes != null
                                            ? `${(r2StorageUsedBytes / 1024 / 1024 / 1024).toFixed(2)} GB`
                                            : '—'}
                                        <span className="text-white-50 fw-normal"> de 2 TB</span>
                                    </div>
                                    {r2StorageUsedBytes != null && r2StorageLimitBytes > 0 && (
                                        <div className="progress mt-1" style={{ height: '6px', width: '120px' }}>
                                            <div
                                                className="progress-bar bg-info"
                                                role="progressbar"
                                                style={{ width: `${Math.min(100, (r2StorageUsedBytes / r2StorageLimitBytes) * 100)}%` }}
                                            />
                                        </div>
                                    )}
                                    <div className="small text-white-50 mt-1" style={{ maxWidth: '260px' }}>
                                        {r2StorageUsedBytes != null && formatR2UpdatedAt(r2StorageUpdatedAt) && (
                                            <>Última lectura: {formatR2UpdatedAt(r2StorageUpdatedAt)}. Pulse Actualizar si caducó la caché.</>
                                        )}
                                        {r2StorageUsedBytes == null && (
                                            <>Sin datos. Pulse <strong>Actualizar</strong> o configure el cron del scheduler.</>
                                        )}
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}
                </div>

                <div className="row row-cols-1 row-cols-md-2 row-cols-xl-4 g-4 mb-5">
                    {visibleCards.map(card => (
                        <StatCard
                            key={card.key}
                            title={card.title}
                            icon={card.icon}
                            color={card.color}
                            route={card.route}
                            data={stats[card.statsKey]}
                        />
                    ))}
                </div>
            </div>
        </MainLayout>
    );
}
