import React from 'react';
import { router } from '@inertiajs/react';

export default function PdfViewerModal({ show, onClose, contract }) {
    if (!show || !contract) return null;

    const handleDownload = () => {
        router.get(route('contracts.download', contract.id));
    };

    const pdfUrl = route('contracts.view', contract.id);

    return (
        <>
            <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.7)' }}>
                <div className="modal-dialog modal-fullscreen">
                    <div className="modal-content border-0">
                        <div className="modal-header bg-dark text-white">
                            <div>
                                <h5 className="modal-title mb-0">
                                    <i className="bi bi-file-pdf-fill me-2 text-danger"></i>
                                    {contract.project_name || 'Contrato'}
                                </h5>
                                <small className="text-white-50">
                                    Cliente: {contract.client} | N° {contract.contract_number || 'N/A'}
                                </small>
                            </div>
                            <div className="d-flex align-items-center gap-2">
                                <button 
                                    onClick={handleDownload}
                                    className="btn btn-sm btn-light rounded-pill"
                                    title="Descargar PDF"
                                >
                                    <i className="bi bi-download me-2"></i>
                                    Descargar
                                </button>
                                <button 
                                    type="button" 
                                    className="btn-close btn-close-white" 
                                    onClick={onClose}
                                ></button>
                            </div>
                        </div>
                        <div className="modal-body p-0" style={{ height: 'calc(100vh - 60px)' }}>
                            <iframe
                                src={pdfUrl}
                                style={{ 
                                    width: '100%', 
                                    height: '100%', 
                                    border: 'none' 
                                }}
                                title="Visualizador de PDF"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}

