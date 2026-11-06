import React from 'react';

const isPreviewable = (pathOrUrl) => /\.(pdf|png|jpe?g)(\?|$)/i.test(String(pathOrUrl || ''));

const iconFor = (pathOrUrl) => {
    const s = String(pathOrUrl || '').toLowerCase();
    if (/\.pdf(\?|$)/.test(s)) return 'bi-file-pdf';
    if (/\.(png|jpe?g)(\?|$)/.test(s)) return 'bi-file-image';
    if (/\.(xls|xlsx)(\?|$)/.test(s)) return 'bi-file-excel';
    if (/\.(doc|docx)(\?|$)/.test(s)) return 'bi-file-word';
    if (/\.(zip|rar)(\?|$)/.test(s)) return 'bi-file-zip';
    return 'bi-file-earmark';
};

/**
 * Botones por archivo (como en Gestión Documental): PDF/imagen abren en el visor modal; el resto en otra pestaña.
 */
export default function FileButtons({ docs = [], openPdfInModal, emptyText = '—' }) {
    const list = (docs || []).filter((d) => d && (d.path || d.url));
    if (list.length === 0) {
        return <span className="text-secondary small">{emptyText}</span>;
    }

    return (
        <div className="d-flex flex-wrap gap-1" style={{ minWidth: '160px', maxWidth: '280px' }} onClick={(e) => e.stopPropagation()}>
            {list.map((doc, idx) => {
                const ref = doc.path || doc.url;
                const href = doc.url || (doc.path ? `/storage/${doc.path}` : '#');
                const label = doc.label || `Archivo ${idx + 1}`;
                const common = {
                    className: 'btn btn-sm btn-outline-primary text-uppercase text-truncate',
                    style: { fontSize: '0.7rem', maxWidth: '100%' },
                    title: label,
                };
                const content = (
                    <>
                        <i className={`bi ${iconFor(ref)} me-1`}></i>
                        {label}
                    </>
                );

                if (openPdfInModal && (isPreviewable(doc.path) || isPreviewable(doc.url))) {
                    return (
                        <button
                            key={idx}
                            type="button"
                            {...common}
                            onClick={(e) => {
                                e.stopPropagation();
                                openPdfInModal(label, doc.path, doc.url);
                            }}
                        >
                            {content}
                        </button>
                    );
                }

                return (
                    <a key={idx} href={href} target="_blank" rel="noopener noreferrer" {...common} onClick={(e) => e.stopPropagation()}>
                        {content}
                    </a>
                );
            })}
        </div>
    );
}
