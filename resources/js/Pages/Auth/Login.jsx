import { Head, Link, useForm } from '@inertiajs/react';
import React, { useEffect } from 'react';

export default function Login({ status, canResetPassword }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        email: '',
        password: '',
        remember: false,
    });

    useEffect(() => {
        const storedTheme = localStorage.getItem('theme');
        if (storedTheme) {
            document.documentElement.setAttribute('data-bs-theme', storedTheme);
        }
    }, []);

    const submit = (e) => {
        e.preventDefault();
        post(route('login'), {
            onFinish: () => reset('password'),
        });
    };

    return (
        <div className="d-flex min-vh-100 align-items-center justify-content-center bg-body-tertiary font-sans">
            <Head title="Iniciar Sesión - GestDoc" />

            <div className="container">
                <div className="row justify-content-center">
                    <div className="col-md-10 col-lg-8 col-xl-7">
                        <div className="card border-0 shadow-lg rounded-5 overflow-hidden">
                            <div className="row g-0">
                                {/* Left Side - Image/Brand */}
                                <div className="col-md-6 bg-primary d-none d-md-flex align-items-center justify-content-center flex-column text-white p-5 position-relative">
                                    <div className="position-absolute top-0 start-0 w-100 h-100 opacity-25"
                                        style={{
                                            backgroundImage: 'url("https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&q=80")',
                                            backgroundSize: 'cover',
                                            backgroundPosition: 'center',
                                            filter: 'grayscale(100%)'
                                        }}>
                                    </div>
                                    <div className="position-relative z-1 text-center">
                                        <div className="bg-body text-primary rounded-3 p-3 d-inline-block shadow mb-4">
                                            <i className="bi bi-building-fill fs-1"></i>
                                        </div>
                                        <h2 className="fw-bold mb-2">GestDoc</h2>
                                        <p className="opacity-75 mb-0">Gestión Inteligente de Documentación</p>
                                    </div>
                                </div>

                                {/* Right Side - Form */}
                                <div className="col-md-6 bg-body p-5">
                                    <div className="text-center mb-4 d-md-none">
                                        <div className="bg-primary text-white rounded-3 p-2 d-inline-block shadow-sm">
                                            <i className="bi bi-building-fill fs-2"></i>
                                        </div>
                                    </div>

                                    <h3 className="fw-bold text-body mb-1 text-center text-md-start">Bienvenido</h3>
                                    <p className="text-secondary small mb-4 text-center text-md-start">Ingresa tus credenciales para acceder</p>

                                    {status && (
                                        <div className="alert alert-success small mb-3" role="alert">
                                            {status}
                                        </div>
                                    )}

                                    <form onSubmit={submit}>
                                        <div className="mb-4">
                                            <label className="form-label text-secondary small fw-bold text-uppercase">Email</label>
                                            <div className="input-group">
                                                <span className="input-group-text bg-body-tertiary border-end-0 text-secondary"><i className="bi bi-envelope"></i></span>
                                                <input
                                                    type="email"
                                                    className={`form-control border-start-0 bg-body-tertiary ${errors.email ? 'is-invalid' : ''}`}
                                                    placeholder="nombre@empresa.com"
                                                    value={data.email}
                                                    onChange={(e) => setData('email', e.target.value)}
                                                />
                                            </div>
                                            {errors.email && <div className="text-danger small mt-1">{errors.email}</div>}
                                        </div>

                                        <div className="mb-4">
                                            <label className="form-label text-secondary small fw-bold text-uppercase">Contraseña</label>
                                            <div className="input-group">
                                                <span className="input-group-text bg-body-tertiary border-end-0 text-secondary"><i className="bi bi-lock"></i></span>
                                                <input
                                                    type="password"
                                                    className={`form-control border-start-0 bg-body-tertiary ${errors.password ? 'is-invalid' : ''}`}
                                                    placeholder="••••••••"
                                                    value={data.password}
                                                    onChange={(e) => setData('password', e.target.value)}
                                                />
                                            </div>
                                            {errors.password && <div className="text-danger small mt-1">{errors.password}</div>}
                                        </div>

                                        <div className="d-flex justify-content-between align-items-center mb-4">
                                            <div className="form-check">
                                                <input
                                                    className="form-check-input"
                                                    type="checkbox"
                                                    id="remember"
                                                    checked={data.remember}
                                                    onChange={(e) => setData('remember', e.target.checked)}
                                                />
                                                <label className="form-check-label text-secondary small" htmlFor="remember">
                                                    Recordarme
                                                </label>
                                            </div>
                                            {canResetPassword && (
                                                <Link href={route('password.request')} className="text-decoration-none small text-primary fw-medium">
                                                    ¿Olvidaste tu contraseña?
                                                </Link>
                                            )}
                                        </div>

                                        <button type="submit" className="btn btn-primary w-100 py-3 fw-bold shadow-sm rounded-3" disabled={processing}>
                                            {processing && <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>}
                                            Iniciar Sesión
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <div className="text-center mt-4 text-secondary small opacity-75">
                            &copy; {new Date().getFullYear()} GestDoc. Todos los derechos reservados.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    );
}
