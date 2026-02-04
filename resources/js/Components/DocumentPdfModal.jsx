import React from 'react';

export default function DocumentPdfModal({ show, onClose, document: doc, file: selectedFile }) {
    if (!show || !doc || !selectedFile) return null;

    const viewUrl = route('folders.documents.files.view', { document: doc.id, file: selectedFile.id });
    const downloadUrl = route('folders.documents.files.download', { document: doc.id, file: selectedFile.id });

    const handleDownload = () => {
        window.location.href = downloadUrl;
    };

    return (
        <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.7)' }}>
            <div className="modal-dialog modal-fullscreen">
                <div className="modal-content border-0">
                    <div className="modal-header bg-dark text-white">
                        <div>
                            <h5 className="modal-title mb-0">
                                <i className="bi bi-file-pdf-fill me-2 text-danger"></i>
                                {selectedFile.nombre_archivo || 'Documento'}
                            </h5>
                            <small className="text-white-50">
                                {doc.numero && `N° ${doc.numero}`}
                                {doc.asunto && ` — ${doc.asunto}`}
                            </small>
                        </div>
                        <div className="d-flex align-items-center gap-2">
                            <a
                                href={downloadUrl}
                                className="btn btn-sm btn-light rounded-pill"
                                title="Descargar PDF"
                            >
                                <i className="bi bi-download me-2"></i>
                                Descargar
                            </a>
                            <button
                                type="button"
                                className="btn-close btn-close-white"
                                onClick={onClose}
                            ></button>
                        </div>
                    </div>
                    <div className="modal-body p-0" style={{ height: 'calc(100vh - 60px)' }}>
                        <iframe
                            src={viewUrl}
                            style={{ width: '100%', height: '100%', border: 'none' }}
                            title="Visualizador de PDF"
                        />
                    </div>
                </div>
            </div>
        </div>
    );
}
