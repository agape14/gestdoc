import React, { useEffect, useRef, useState } from 'react';
import { Viewer } from '@photo-sphere-viewer/core';
import '@photo-sphere-viewer/core/index.css';

export default function Viewer360({ imageUrl, title = "Vista 360°" }) {
    const viewerRef = useRef(null);
    const viewerInstance = useRef(null);
    const [imageExists, setImageExists] = useState(true);
    const [isLoading, setIsLoading] = useState(true);

    useEffect(() => {
        // Verificar si la imagen existe
        const img = new Image();
        img.onload = () => {
            setImageExists(true);
            setIsLoading(false);
            initViewer();
        };
        img.onerror = () => {
            setImageExists(false);
            setIsLoading(false);
        };
        img.src = imageUrl;

        return () => {
            if (viewerInstance.current) {
                viewerInstance.current.destroy();
                viewerInstance.current = null;
            }
        };
    }, [imageUrl]);

    const initViewer = () => {
        if (viewerRef.current && !viewerInstance.current) {
            try {
                viewerInstance.current = new Viewer({
                    container: viewerRef.current,
                    panorama: imageUrl,
                    navbar: [
                        'zoom',
                        'move',
                        'fullscreen',
                    ],
                    defaultZoomLvl: 50,
                    fisheye: false,
                    mousewheel: true,
                    autorotateDelay: 3000,
                    autorotateSpeed: '1rpm',
                });
            } catch (error) {
                console.error('Error initializing panorama viewer:', error);
                setImageExists(false);
            }
        }
    };

    if (isLoading) {
        return (
            <div className="d-flex align-items-center justify-content-center h-100 bg-body-secondary">
                <div className="text-center">
                    <div className="spinner-border text-primary mb-3" role="status">
                        <span className="visually-hidden">Cargando...</span>
                    </div>
                    <p className="text-secondary">Cargando vista 360°...</p>
                </div>
            </div>
        );
    }

    if (!imageExists) {
        return (
            <div className="d-flex align-items-center justify-content-center h-100 bg-body-secondary">
                <div className="text-center p-5">
                    <div className="mb-4">
                        <i className="bi bi-image text-secondary" style={{ fontSize: '4rem' }}></i>
                    </div>
                    <h4 className="fw-bold text-body mb-3">Imagen 360° No Disponible</h4>
                    <p className="text-secondary mb-4">
                        Para visualizar una imagen panorámica 360°, coloca tu archivo en:
                    </p>
                    <code className="bg-dark text-white p-3 rounded d-inline-block mb-4">
                        public/images/360/default-panorama.jpg
                    </code>
                    <div className="mt-4">
                        <h6 className="fw-semibold mb-2">¿Cómo obtener una imagen 360°?</h6>
                        <ul className="list-unstyled text-start mx-auto" style={{ maxWidth: '500px' }}>
                            <li className="mb-2">
                                <i className="bi bi-check-circle-fill text-success me-2"></i>
                                Usa la app <a href="https://360photocam.com/" target="_blank" className="text-primary">360 Photo Cam</a>
                            </li>
                            <li className="mb-2">
                                <i className="bi bi-check-circle-fill text-success me-2"></i>
                                Descarga imágenes gratuitas de <a href="https://www.flickr.com/groups/equirectangular/" target="_blank" className="text-primary">Flickr</a>
                            </li>
                            <li className="mb-2">
                                <i className="bi bi-check-circle-fill text-success me-2"></i>
                                Toma fotos con cámaras 360° (Ricoh Theta, Insta360)
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        );
    }

    return (
        <div className="viewer-360-container h-100">
            <div ref={viewerRef} style={{ width: '100%', height: '100%' }} />
        </div>
    );
}

