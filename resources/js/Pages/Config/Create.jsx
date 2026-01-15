import React from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, useForm, Link } from '@inertiajs/react';
import SubmitButton from '@/Components/SubmitButton';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        role: 'Operador',
        password: '',
        password_confirmation: '',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('users.store'));
    };

    return (
        <MainLayout>
            <Head title="Nuevo Usuario" />

            <div className="card border-0 shadow-sm p-4 rounded-4 bg-body" style={{ maxWidth: '800px', margin: '0 auto' }}>
                <div className="mb-4">
                    <h3 className="fw-bold mb-1">Nuevo Usuario</h3>
                    <p className="text-secondary small">Registrar un nuevo usuario con acceso al sistema.</p>
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

                    <div className="row g-4">
                        <div className="col-md-6 mb-3">
                            <label className="form-label fw-medium">Contraseña</label>
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
                        <SubmitButton processing={processing} icon="bi-person-plus">
                            Crear Usuario
                        </SubmitButton>
                    </div>
                </form>
            </div>
        </MainLayout>
    );
}
