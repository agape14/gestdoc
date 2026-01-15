import React from 'react';

export default function SubmitButton({ processing, icon, children, className = '', ...props }) {
    return (
        <button
            type="submit"
            className={`btn btn-primary shadow-sm d-flex align-items-center justify-content-center ${className}`}
            disabled={processing}
            {...props}
        >
            {processing ? (
                <>
                    <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                    Processing...
                </>
            ) : (
                <>
                    {icon && <i className={`bi ${icon} me-2`}></i>}
                    {children}
                </>
            )}
        </button>
    );
}
