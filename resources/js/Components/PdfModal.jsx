import React, { useEffect } from 'react';

export default function PdfModal({ show, onClose, pdfUrl, title = 'Ver PDF' }) {
    useEffect(() => {
        if (show) {
            // Agregar clase al body para prevenir scroll cuando el modal está abierto
            document.body.style.overflow = 'hidden';
        } else {
            document.body.style.overflow = '';
        }

        // Cleanup
        return () => {
            document.body.style.overflow = '';
        };
    }, [show]);

    useEffect(() => {
        const handleEscape = (e) => {
            if (e.key === 'Escape' && show) {
                onClose();
            }
        };

        if (show) {
            document.addEventListener('keydown', handleEscape);
        }

        return () => {
            document.removeEventListener('keydown', handleEscape);
        };
    }, [show, onClose]);

    if (!show) return null;

    return (
        <div 
            className="modal fade show d-block" 
            style={{ backgroundColor: 'rgba(0,0,0,0.5)', zIndex: 1055 }}
            onClick={onClose}
            tabIndex="-1"
        >
            <div 
                className="modal-dialog modal-xl modal-dialog-centered" 
                onClick={(e) => e.stopPropagation()}
            >
                <div className="modal-content border-0 shadow-lg">
                    <div className="modal-header border-bottom">
                        <h5 className="modal-title fw-bold">{title}</h5>
                        <button 
                            type="button" 
                            className="btn-close" 
                            onClick={onClose}
                            aria-label="Cerrar"
                        ></button>
                    </div>
                    <div className="modal-body p-0" style={{ height: '80vh', minHeight: '500px' }}>
                        <iframe
                            src={pdfUrl}
                            className="w-100 h-100 border-0"
                            title={title}
                            style={{ minHeight: '500px' }}
                        ></iframe>
                    </div>
                    <div className="modal-footer border-top">
                        <a 
                            href={pdfUrl} 
                            target="_blank" 
                            rel="noopener noreferrer"
                            className="btn btn-outline-primary"
                        >
                            <i className="bi bi-box-arrow-up-right me-2"></i>
                            Abrir en nueva pestaña
                        </a>
                        <button 
                            type="button" 
                            className="btn btn-secondary" 
                            onClick={onClose}
                        >
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

