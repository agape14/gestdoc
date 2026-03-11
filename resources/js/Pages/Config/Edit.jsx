import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

const ALL_MENU_KEYS = [
    'dashboard', 'licitaciones', 'consultor-obras', 'ejecutor-obra', 'proveedor-servicios',
    'proveedor-bienes', 'especialistas-ejecucion', 'especialistas-consultoria', 'inmobiliaria',
    'topografia', 'tecnologia', 'plantillas-ing', 'cvs', 'folders',
];

export default function Edit({ user, menuOptions = [], allowedMenusDefault = [] }) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name || '',
        email: user.email || '',
        role: user.role || 'Operador',
        password: '',
        password_confirmation: '',
        allowed_menus: allowedMenusDefault.length ? allowedMenusDefault : [...ALL_MENU_KEYS],
    });

    const showMenuAccess = data.role === 'Operador' || data.role === 'Visualizador';

    const toggleMenu = (key) => {
        const current = data.allowed_menus || [];
        if (current.includes(key)) {
            setData('allowed_menus', current.filter(k => k !== key));
        } else {
            setData('allowed_menus', [...current, key]);
        }
    };

    const selectAllMenus = () => setData('allowed_menus', [...ALL_MENU_KEYS]);
    const deselectAllMenus = () => setData('allowed_menus', []);

    const submit = (e) => {
        e.preventDefault();
        put(route('users.update', user.id));
    };

    return (
        <MainLayout>
            <Head title="Editar Usuario" />

            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body form-card-responsive" style={{ maxWidth: '800px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Editar Usuario</h3>
                    <p className="text-secondary small">Modificar información de acceso.</p>
                </div>

                <form onSubmit={submit}>
                    <div className="mb-3">
                        <label className="form-label fw-medium text-body">Nombre Completo</label>
                        <input
                            type="text"
                            className={`form-control ${errors.name ? 'is-invalid' : ''}`}
                            value={data.name}
                            onChange={e => setData('name', e.target.value)}
                        />
                        {errors.name && <div className="invalid-feedback">{errors.name}</div>}
                    </div>

                    <div className="row g-4">
                        <div className="col-md-6 mb-3">
                            <label className="form-label fw-medium text-body">Correo Electrónico</label>
                            <input
                                type="email"
                                className={`form-control ${errors.email ? 'is-invalid' : ''}`}
                                value={data.email}
                                onChange={e => setData('email', e.target.value)}
                            />
                            {errors.email && <div className="invalid-feedback">{errors.email}</div>}
                        </div>
                        <div className="col-md-6 mb-3">
                            <label className="form-label fw-medium text-body">Rol de Usuario</label>
                            <select
                                className={`form-select ${errors.role ? 'is-invalid' : ''}`}
                                value={data.role}
                                onChange={e => setData('role', e.target.value)}
                            >
                                <option value="Administrador">Administrador</option>
                                <option value="Operador">Operador</option>
                                <option value="Visualizador">Visualizador</option>
                            </select>
                            {errors.role && <div className="invalid-feedback">{errors.role}</div>}
                        </div>
                    </div>

                    {showMenuAccess && (
                        <div className="mb-4 p-3 rounded-3 border bg-light bg-opacity-50">
                            <div className="d-flex justify-content-between align-items-center mb-3">
                                <label className="form-label fw-semibold text-body mb-0">
                                    <i className="bi bi-menu-button-wide me-2"></i>
                                    Accesos al menú
                                </label>
                                <div className="d-flex gap-2">
                                    <button type="button" className="btn btn-sm btn-outline-primary" onClick={selectAllMenus}>Todos</button>
                                    <button type="button" className="btn btn-sm btn-outline-secondary" onClick={deselectAllMenus}>Ninguno</button>
                                </div>
                            </div>
                            <p className="text-secondary small mb-3">Selecciona los menús que podrá ver este usuario.</p>
                            <div className="row g-2">
                                {(menuOptions.length ? menuOptions : ALL_MENU_KEYS.map(k => ({ key: k, label: k }))).map(({ key, label }) => (
                                    <div key={key} className="col-md-6 col-lg-4">
                                        <div className="form-check">
                                            <input
                                                type="checkbox"
                                                className="form-check-input"
                                                id={`menu-${key}`}
                                                checked={(data.allowed_menus || []).includes(key)}
                                                onChange={() => toggleMenu(key)}
                                            />
                                            <label className="form-check-label small" htmlFor={`menu-${key}`}>{label}</label>
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    <div className="alert alert-warning border-0 bg-warning-subtle text-warning-emphasis small">
                        <i className="bi bi-exclamation-triangle-fill me-2"></i>
                        Dejar los campos de contraseña vacíos si no desea cambiarla.
                    </div>

                    <div className="row g-4">
                        <div className="col-md-6 mb-3">
                            <label className="form-label fw-medium">Nueva Contraseña</label>
                            <input
                                type="password"
                                className={`form-control ${errors.password ? 'is-invalid' : ''}`}
                                value={data.password}
                                onChange={e => setData('password', e.target.value)}
                                autoComplete="new-password"
                            />
                            {errors.password && <div className="invalid-feedback">{errors.password}</div>}
                        </div>
                        <div className="col-md-6 mb-3">
                            <label className="form-label fw-medium">Confirmar Contraseña</label>
                            <input
                                type="password"
                                className={`form-control ${errors.password_confirmation ? 'is-invalid' : ''}`}
                                value={data.password_confirmation}
                                onChange={e => setData('password_confirmation', e.target.value)}
                                autoComplete="new-password"
                            />
                            {errors.password_confirmation && <div className="invalid-feedback">{errors.password_confirmation}</div>}
                        </div>
                    </div>

                    <div className="d-flex justify-content-end mt-4 pt-3 border-top">
                        <Link href={route('users.index')} className="btn btn-outline-secondary me-2">Cancelar</Link>
                        <SubmitButton processing={processing} icon="bi-arrow-repeat">
                            Actualizar Usuario
                        </SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
