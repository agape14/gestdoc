import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Edit({ user }) {
    const { data, setData, put, processing, errors } = useForm({
        name: user.name || '',
        email: user.email || '',
        role: user.role || 'Operador',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('users.update', user.id));
    };

    return (
        <MainLayout>
            <Head title="Editar Usuario" />

            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '800px', margin: '0 auto' }}>
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
                            <label className="form-label fw-medium text-body">Coral Electrónico</label>
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
                            />
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
