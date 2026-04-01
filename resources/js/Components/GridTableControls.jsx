import React from 'react';

/**
 * Selector de cantidad por página (10, 50, 100, todos).
 */
export function GridPerPageSelect({ value = '50', onChange, className = '' }) {
    const v = value === 'all' || value === 'todos' ? 'all' : String(value);
    return (
        <div className={`d-flex align-items-center gap-2 ${className}`}>
            <label className="form-label small text-secondary mb-0 text-nowrap">Por página</label>
            <select
                className="form-select form-select-sm rounded-pill bg-body-tertiary border-0"
                style={{ maxWidth: '130px' }}
                value={v}
                onChange={(e) => onChange(e.target.value)}
                aria-label="Registros por página"
            >
                <option value="10">10</option>
                <option value="50">50</option>
                <option value="100">100</option>
                <option value="all">Todos</option>
            </select>
        </div>
    );
}

/**
 * Cabecera de tabla ordenable (mismo patrón que registro-expedientes / ModuleIndex).
 */
export function SortTh({ label, field, currentSort, currentDirection, onSort, className = 'py-3', scope = 'col' }) {
    const active = currentSort === field;
    const dir = currentDirection === 'asc' ? 'asc' : 'desc';
    return (
        <th scope={scope} className={className}>
            <button
                type="button"
                className={`btn btn-link text-secondary text-decoration-none p-0 border-0 fw-semibold text-uppercase small d-inline-flex align-items-center gap-1 ${active ? 'text-primary' : ''}`}
                onClick={() => onSort(field)}
            >
                <span>{label}</span>
                {active ? (
                    <i className={`bi ${dir === 'asc' ? 'bi-sort-up' : 'bi-sort-down'}`} aria-hidden />
                ) : (
                    <i className="bi bi-arrow-down-up opacity-50" style={{ fontSize: '0.75rem' }} aria-hidden />
                )}
            </button>
        </th>
    );
}
