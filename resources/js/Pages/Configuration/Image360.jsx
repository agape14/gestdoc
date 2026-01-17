import React, { useState } from 'react';
import MainLayout from '@/Layouts/MainLayout';
import { Head, router } from '@inertiajs/react';
import Viewer360 from '@/Components/Viewer360';

export default function Image360({ currentImage, flash }) {
    const [selectedFile, setSelectedFile] = useState(null);
    const [previewUrl, setPreviewUrl] = useState(null);
    const [uploading, setUploading] = useState(false);

    const handleFileSelect = (e) => {
        const file = e.target.files[0];
        if (file) {
            setSelectedFile(file);
            setPreviewUrl(URL.createObjectURL(file));
        }
    };

    const handleUpload = (e) => {
        e.preventDefault();
        
        if (!selectedFile) {
            alert('Por favor selecciona una imagen primero.');
            return;
        }

        setUploading(true);

        const formData = new FormData();
        formData.append('image', selectedFile);

        router.post('/config/image360/update', formData, {
            onSuccess: () => {
                setSelectedFile(null);
                setPreviewUrl(null);
                setUploading(false);
            },
            onError: () => {
                setUploading(false);
            }
        });
    };

    const handleRestore = () => {
        if (confirm('¿Estás seguro de restaurar la imagen predeterminada?')) {
            router.post('/config/image360/restore');
        }
    };

    const handleCancelPreview = () => {
        setSelectedFile(null);
        setPreviewUrl(null);
    };

    return (
        <MainLayout>
            <Head title="Configuración - Imagen 360°" />

            {flash?.success && (
                <div className="alert alert-success alert-dismissible fade show" role="alert">
                    <i className="bi bi-check-circle-fill me-2"></i>
                    {flash.success}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            )}

            {flash?.error && (
                <div className="alert alert-danger alert-dismissible fade show" role="alert">
                    <i className="bi bi-exclamation-triangle-fill me-2"></i>
                    {flash.error}
                    <button type="button" className="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            )}

            <div className="mb-4">
                <h2 className="fw-bold text-body mb-0">
                    <i className="bi bi-image-fill me-2 text-primary"></i>
                    Imagen Panorámica 360°
                </h2>
                <p className="text-secondary">Configura la imagen panorámica que se mostrará en el dashboard</p>
            </div>

            {/* Vista previa actual */}
            <div className="card border-0 shadow-sm rounded-4 overflow-hidden bg-body mb-4">
                <div className="card-header bg-body py-3 px-4 border-bottom border-light">
                    <h5 className="mb-0 fw-bold text-body">
                        <i className="bi bi-eye-fill me-2"></i>
                        Vista Previa Actual
                    </h5>
                </div>
                <div className="card-body p-0" style={{ height: '400px' }}>
                    <Viewer360 
                        imageUrl={previewUrl || currentImage}
                        title="Vista previa 360°"
                    />
                </div>
            </div>

            {/* Formulario de carga */}
            <div className="card border-0 shadow-sm rounded-4 bg-body">
                <div className="card-header bg-body py-3 px-4 border-bottom border-light">
                    <h5 className="mb-0 fw-bold text-body">
                        <i className="bi bi-cloud-upload-fill me-2"></i>
                        Cambiar Imagen
                    </h5>
                </div>
                <div className="card-body p-4">
                    <form onSubmit={handleUpload}>
                        <div className="mb-4">
                            <label className="form-label fw-semibold">Seleccionar nueva imagen 360°</label>
                            <input
                                type="file"
                                className="form-control"
                                accept="image/jpeg,image/jpg,image/png"
                                onChange={handleFileSelect}
                                disabled={uploading}
                            />
                            <div className="form-text">
                                <i className="bi bi-info-circle me-1"></i>
                                Formatos: JPG, JPEG, PNG | Tamaño máximo: 50MB | Proyección: Equirectangular (2:1)
                            </div>
                        </div>

                        {selectedFile && (
                            <div className="alert alert-info d-flex align-items-center">
                                <i className="bi bi-file-earmark-image fs-3 me-3"></i>
                                <div className="flex-grow-1">
                                    <strong>Archivo seleccionado:</strong><br />
                                    <span className="text-muted">{selectedFile.name}</span>
                                    <span className="badge bg-primary ms-2">
                                        {(selectedFile.size / 1024 / 1024).toFixed(2)} MB
                                    </span>
                                </div>
                            </div>
                        )}

                        <div className="d-flex gap-2 justify-content-between flex-wrap">
                            <div className="d-flex gap-2">
                                <button
                                    type="submit"
                                    className="btn btn-primary"
                                    disabled={!selectedFile || uploading}
                                >
                                    {uploading ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Subiendo...
                                        </>
                                    ) : (
                                        <>
                                            <i className="bi bi-upload me-2"></i>
                                            Subir y Aplicar
                                        </>
                                    )}
                                </button>

                                {selectedFile && !uploading && (
                                    <button
                                        type="button"
                                        className="btn btn-outline-secondary"
                                        onClick={handleCancelPreview}
                                    >
                                        <i className="bi bi-x-lg me-2"></i>
                                        Cancelar
                                    </button>
                                )}
                            </div>

                            <button
                                type="button"
                                className="btn btn-outline-warning"
                                onClick={handleRestore}
                                disabled={uploading}
                            >
                                <i className="bi bi-arrow-counterclockwise me-2"></i>
                                Restaurar Predeterminada
                            </button>
                        </div>
                    </form>

                    {/* Instrucciones */}
                    <div className="mt-4 p-3 bg-light rounded-3 border">
                        <h6 className="fw-bold mb-3">
                            <i className="bi bi-lightbulb-fill text-warning me-2"></i>
                            Consejos para mejores resultados
                        </h6>
                        <ul className="mb-0 small">
                            <li className="mb-2">
                                <strong>Formato equirectangular:</strong> La imagen debe tener ratio 2:1 (ej: 4096x2048 píxeles)
                            </li>
                            <li className="mb-2">
                                <strong>Resolución recomendada:</strong> Mínimo 4096x2048 para calidad óptima
                            </li>
                            <li className="mb-2">
                                <strong>Tamaño de archivo:</strong> Optimiza la imagen para web (menor a 5MB recomendado)
                            </li>
                            <li>
                                <strong>Fuentes:</strong> Usa apps como <a href="https://360photocam.com/" target="_blank" className="text-primary">360 Photo Cam</a>, cámaras 360° o renders equirectangulares
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </MainLayout>
    );
}

