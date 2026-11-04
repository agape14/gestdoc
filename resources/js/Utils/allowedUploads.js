export const FILE_ACCEPT = '.pdf,.doc,.docx,.xls,.xlsx,.dwg,.zip,.rar,.png,.jpg,.jpeg';

export const FILE_ACCEPT_HINT = 'PDF, Word, Excel, DWG, ZIP/RAR o imagen (máx. 25 MB)';

export const EXPERIENCIA_ZIP_TYPES = [
    { id: 'CONTRATOS', label: 'Contratos' },
    { id: 'RESOLUCIONES_CONFORMIDADES', label: 'Resoluciones / Conformidades' },
    { id: 'COMPROBANTES', label: 'Comprobantes de pago' },
    { id: 'OTROS', label: 'Otros documentos' },
    { id: 'SUSPENSION', label: 'Suspensión' },
    { id: 'REINICIO', label: 'Reinicio' },
];

export const CONSULTOR_ZIP_TYPES = [
    ...EXPERIENCIA_ZIP_TYPES,
    { id: 'TDR', label: 'TDR' },
    { id: 'PRODUCTOS_TECNICOS', label: 'Productos técnicos' },
];

export const EJECUTOR_ZIP_TYPES = [
    { id: 'CONTRATOS', label: 'Contratos' },
    { id: 'RESOLUCIONES_CONFORMIDADES', label: 'Actas / resoluciones / conformidades' },
    { id: 'SUSPENSION', label: 'Suspensión' },
    { id: 'REINICIO', label: 'Reinicio' },
    { id: 'OTROS', label: 'Adicionales, deductivos y liquidación' },
];
