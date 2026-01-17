import React, { useState } from 'react';
import { Link, usePage } from '@inertiajs/react';

export default function MainLayout({ children }) {
    const { props, url } = usePage();
    const { auth } = props;
    const [isSidebarOpen, setIsSidebarOpen] = useState(false);
    const [isDarkMode, setIsDarkMode] = useState(false);
    const [configMenuOpen, setConfigMenuOpen] = useState(false);

    React.useEffect(() => {
        const storedTheme = localStorage.getItem('theme');
        setIsDarkMode(storedTheme === 'dark');
        if (storedTheme) {
            document.documentElement.setAttribute('data-bs-theme', storedTheme);
        }
    }, []);

    const toggleSidebar = () => setIsSidebarOpen(!isSidebarOpen);

    const toggleTheme = () => {
        const newTheme = isDarkMode ? 'light' : 'dark';
        setIsDarkMode(!isDarkMode);
        document.documentElement.setAttribute('data-bs-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    };

    const NavItem = ({ href, icon, label, activePattern }) => {
        const isActive = activePattern ? (url && url.startsWith(activePattern)) : url === href;
        return (
            <li className="nav-item">
                <Link
                    href={href}
                    className={`nav-link d-flex align-items-center py-3 px-4 ${isActive ? 'active bg-white text-primary shadow-sm fw-bold rounded-end-pill' : 'text-white-50 hover-text-white'}`}
                    style={{ transition: 'all 0.2s', marginRight: isActive ? '1rem' : '0' }}
                >
                    <i className={`bi ${icon} me-3 fs-5`}></i>
                    <span className="fs-6">{label}</span>
                </Link>
            </li>
        );
    };

    const NavItemWithSubmenu = ({ icon, label, activePattern, children }) => {
        const isActive = activePattern && url && url.startsWith(activePattern);
        const [isOpen, setIsOpen] = useState(isActive);

        React.useEffect(() => {
            if (isActive) setIsOpen(true);
        }, [isActive]);

        return (
            <li className="nav-item">
                <button
                    onClick={() => setIsOpen(!isOpen)}
                    className={`nav-link d-flex align-items-center justify-content-between py-3 px-4 w-100 border-0 ${isActive ? 'active bg-white text-primary shadow-sm fw-bold rounded-end-pill' : 'text-white-50 hover-text-white'}`}
                    style={{ transition: 'all 0.2s', marginRight: isActive ? '1rem' : '0', background: 'transparent' }}
                >
                    <div className="d-flex align-items-center">
                        <i className={`bi ${icon} me-3 fs-5`}></i>
                        <span className="fs-6">{label}</span>
                    </div>
                    <i className={`bi ${isOpen ? 'bi-chevron-up' : 'bi-chevron-down'} small`}></i>
                </button>
                {isOpen && (
                    <ul className="nav flex-column ps-3">
                        {children}
                    </ul>
                )}
            </li>
        );
    };

    const SubNavItem = ({ href, icon, label }) => {
        const isActive = url === href;
        return (
            <li className="nav-item">
                <Link
                    href={href}
                    className={`nav-link d-flex align-items-center py-2 px-4 ${isActive ? 'text-white fw-semibold' : 'text-white-50 hover-text-white'}`}
                    style={{ fontSize: '0.9rem' }}
                >
                    <i className={`bi ${icon} me-2`}></i>
                    <span>{label}</span>
                </Link>
            </li>
        );
    };

    return (
        <div className="d-flex min-vh-100 bg-body-tertiary">
            {/* Mobile Backdrop */}
            {isSidebarOpen && (
                <div
                    className="position-fixed top-0 start-0 w-100 h-100 bg-black bg-opacity-50 d-lg-none"
                    style={{ zIndex: 1035 }}
                    onClick={toggleSidebar}
                ></div>
            )}

            {/* Sidebar */}
            <div
                className={`d-flex flex-column bg-dark text-white fixed-top bottom-0 shadow-lg transition-transform ${isSidebarOpen ? 'translate-x-0' : '-translate-x-full'} d-lg-flex translate-lg-x-0`}
                style={{
                    width: '280px',
                    zIndex: 1040,
                    transition: 'transform 0.3s ease-in-out',
                    transform: isSidebarOpen ? 'translateX(0)' : undefined
                }}
            >
                {/* Mobile styles */}
                <style>{`
                    @media (max-width: 991.98px) {
                        .fixed-top.bottom-0 { transform: translateX(${isSidebarOpen ? '0' : '-100%'}); }
                    }
                    @media (min-width: 992px) {
                        .fixed-top.bottom-0 { transform: none !important; }
                    }
                    .hover-text-white:hover { color: white !important; background-color: rgba(255,255,255,0.05); }
                `}</style>

                <div className="p-4 border-bottom border-secondary d-flex justify-content-between align-items-center">
                    <a href="/" className="d-flex align-items-center text-white text-decoration-none">
                        <div className="bg-primary rounded-3 p-2 me-2 d-flex align-items-center justify-content-center shadow-lg" style={{ width: '40px', height: '40px' }}>
                            <i className="bi bi-building-fill fs-4 text-white"></i>
                        </div>
                        <span className="fs-4 fw-bold tracking-tight">GestDoc</span>
                    </a>
                    <button className="btn btn-dark d-lg-none p-1" onClick={toggleSidebar}>
                        <i className="bi bi-x-lg"></i>
                    </button>
                </div>

                <div className="py-4 overflow-y-auto custom-scrollbar flex-grow-1">
                    <ul className="nav nav-pills flex-column gap-1 pe-0">
                        <div className="px-4 mb-2 text-uppercase text-white-50 small fw-bold" style={{ fontSize: '0.75rem', letterSpacing: '1px' }}>Menu Principal</div>
                        <NavItem href="/dashboard" icon="bi-speedometer2" label="Dashboard" />
                        <NavItem href="/licitaciones" icon="bi-briefcase" label="Licitaciones" activePattern="/licitaciones" />
                        <NavItem href="/folders" icon="bi-folder-fill" label="Gestión Documental" activePattern="/folders" />

                        <div className="px-4 mt-4 mb-2 text-uppercase text-white-50 small fw-bold" style={{ fontSize: '0.75rem', letterSpacing: '1px' }}>Gestión</div>
                        <NavItem href="/cvs" icon="bi-people" label="Banco de CVs" activePattern="/cvs" />
                        <NavItemWithSubmenu icon="bi-gear" label="Configuración" activePattern="/config">
                            <SubNavItem href="/config" icon="bi-people-fill" label="Usuarios" />
                            <SubNavItem href="/config/image360" icon="bi-image-fill" label="Imagen 360°" />
                        </NavItemWithSubmenu>
                    </ul>
                </div>

                <div className="mt-auto px-4 py-3 border-top border-secondary bg-black bg-opacity-25">
                    <div className="mb-3 d-flex align-items-center justify-content-between text-white-50 small">
                        <span>Tema: {isDarkMode ? 'Oscuro' : 'Claro'}</span>
                        <button
                            onClick={toggleTheme}
                            className="btn btn-sm btn-outline-light rounded-pill d-flex align-items-center px-2 py-1"
                            title={isDarkMode ? 'Cambiar a modo claro' : 'Cambiar a modo oscuro'}
                        >
                            <i className={`bi ${isDarkMode ? 'bi-moon-stars-fill' : 'bi-sun-fill'} me-1`}></i>
                            {isDarkMode ? 'Dark' : 'Light'}
                        </button>
                    </div>

                    <div className="d-flex align-items-center justify-content-between p-2 rounded-3 hover-bg-dark-light">
                        <div className="d-flex align-items-center overflow-hidden">
                            <div className="bg-primary rounded-circle d-flex align-items-center justify-content-center text-white me-3 flex-shrink-0 shadow-sm" style={{ width: '42px', height: '42px', fontSize: '1.2rem' }}>
                                {auth?.user?.name?.charAt(0).toUpperCase()}
                            </div>
                            <div className="overflow-hidden">
                                <div className="fw-bold text-truncate text-white">{auth?.user?.name}</div>
                                <small className="text-white-50 d-block text-truncate" style={{ fontSize: '0.8rem' }}>{auth?.user?.role || 'Usuario'}</small>
                            </div>
                        </div>
                        <Link href={route('logout')} method="post" as="button" className="btn btn-icon btn-outline-danger border-0 rounded-circle" title="Cerrar Sesión">
                            <i className="bi bi-box-arrow-right fs-5"></i>
                        </Link>
                    </div>
                </div>
            </div>

            {/* Main Content */}
            <div className="flex-grow-1 d-flex flex-column min-vh-100 transition-all"
                style={{ marginLeft: '0' }}>

                {/* Desktop Margin Logic injected via style tag to handle responsive behavior cleanly or use a class if configured */}
                <style>{`
                    @media (min-width: 992px) {
                        .main-content-wrapper { margin-left: 280px !important; }
                    }
                 `}</style>

                <div className="main-content-wrapper flex-grow-1 d-flex flex-column min-vh-100">
                    {/* Topbar Mobile */}
                    <div className="d-lg-none bg-body p-3 d-flex justify-content-between align-items-center shadow-sm sticky-top z-1030">
                        <div className="d-flex align-items-center">
                            <div className="bg-primary rounded-3 p-1 me-2 d-flex align-items-center justify-content-center" style={{ width: '32px', height: '32px' }}>
                                <i className="bi bi-building-fill fs-6 text-white"></i>
                            </div>
                            <span className="fw-bold fs-5 text-body">GestDoc</span>
                        </div>
                        <button className="btn btn-light btn-sm shadow-sm border" type="button" onClick={toggleSidebar}>
                            <i className="bi bi-list fs-4"></i>
                        </button>
                    </div>

                    {/* Page Content */}
                    <main className="flex-grow-1 p-3 p-md-4 p-lg-5 overflow-x-hidden">
                        {children}
                    </main>
                </div>
            </div>
        </div>
    );
}
