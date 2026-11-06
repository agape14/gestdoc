import React, { useEffect, useRef, useState } from 'react';

const isoToDisplay = (iso) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(iso || '').trim());
    return m ? `${m[3]}/${m[2]}/${m[1]}` : '';
};

const isoOnly = (iso) => {
    const m = /^(\d{4})-(\d{2})-(\d{2})/.exec(String(iso || '').trim());
    return m ? `${m[1]}-${m[2]}-${m[3]}` : '';
};

const formatDigits = (digits) => {
    if (digits.length <= 2) return digits;
    if (digits.length <= 4) return `${digits.slice(0, 2)}/${digits.slice(2)}`;
    return `${digits.slice(0, 2)}/${digits.slice(2, 4)}/${digits.slice(4, 8)}`;
};

const digitsToIso = (digits) => {
    if (digits.length !== 8) return null;
    const d = parseInt(digits.slice(0, 2), 10);
    const m = parseInt(digits.slice(2, 4), 10);
    const y = parseInt(digits.slice(4, 8), 10);
    const date = new Date(y, m - 1, d);
    if (y < 1900 || date.getFullYear() !== y || date.getMonth() !== m - 1 || date.getDate() !== d) return null;
    return `${digits.slice(4, 8)}-${digits.slice(2, 4)}-${digits.slice(0, 2)}`;
};

/**
 * Fecha siempre en DD/MM/AAAA (independiente del idioma del navegador).
 * Recibe y emite `yyyy-mm-dd` mediante onChange({ target: { value } }), igual que un input nativo.
 */
export default function DateInput({
    value,
    onChange,
    className = 'form-control',
    placeholder = 'dd/mm/aaaa',
    name,
    id,
    required = false,
    disabled = false,
    readOnly = false,
    style,
    min,
    max,
}) {
    const [text, setText] = useState(isoToDisplay(value));
    const pickerRef = useRef(null);

    useEffect(() => {
        setText(isoToDisplay(value));
    }, [value]);

    const emit = (iso) => onChange?.({ target: { value: iso, name } });

    const handleTextChange = (e) => {
        const digits = e.target.value.replace(/\D/g, '').slice(0, 8);
        setText(formatDigits(digits));
        if (digits.length === 0) {
            emit('');
            return;
        }
        const iso = digitsToIso(digits);
        if (iso) emit(iso);
    };

    const handleBlur = () => {
        const digits = text.replace(/\D/g, '');
        if (digits.length > 0 && !digitsToIso(digits)) {
            setText(isoToDisplay(value));
        }
    };

    const openPicker = () => {
        const el = pickerRef.current;
        if (!el || disabled || readOnly) return;
        if (typeof el.showPicker === 'function') {
            try {
                el.showPicker();
                return;
            } catch {
                // showPicker no disponible: se usa focus como respaldo
            }
        }
        el.focus();
        el.click();
    };

    const isInvalid = String(className).includes('is-invalid');

    return (
        <div className={`position-relative w-100 ${isInvalid ? 'is-invalid' : ''}`}>
            <input
                type="text"
                inputMode="numeric"
                autoComplete="off"
                id={id}
                name={name}
                className={className}
                style={{ ...style, paddingRight: '2.4rem' }}
                placeholder={placeholder}
                value={text}
                onChange={handleTextChange}
                onBlur={handleBlur}
                required={required}
                disabled={disabled}
                readOnly={readOnly}
                maxLength={10}
            />
            <button
                type="button"
                className="btn btn-link btn-sm position-absolute top-50 end-0 translate-middle-y text-secondary me-1 p-1"
                onClick={openPicker}
                tabIndex={-1}
                disabled={disabled || readOnly}
                title="Abrir calendario"
            >
                <i className="bi bi-calendar3"></i>
            </button>
            <input
                ref={pickerRef}
                type="date"
                tabIndex={-1}
                aria-hidden="true"
                value={isoOnly(value)}
                min={min}
                max={max}
                onChange={(e) => emit(e.target.value)}
                style={{ position: 'absolute', left: 0, bottom: 0, width: '100%', height: 0, opacity: 0, border: 0, padding: 0, pointerEvents: 'none' }}
            />
        </div>
    );
}
