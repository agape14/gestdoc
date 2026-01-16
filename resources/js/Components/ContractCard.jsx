import React from 'react';
import { FileTextIcon, DownloadIcon, Edit2Icon, Trash2Icon } from 'lucide-react';
import { router } from '@inertiajs/react';

export default function ContractCard({ contract, onEdit, onDelete }) {
    const isComplete = contract.status === 'completo';
    
    const formatCurrency = (amount, currency) => {
        const formatted = new Intl.NumberFormat('es-PE', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        }).format(amount);
        
        return `${currency === 'USD' ? '$' : 'S/'} ${formatted}`;
    };

    const formatDate = (dateString) => {
        if (!dateString) return 'N/A';
        const date = new Date(dateString);
        return new Intl.DateTimeFormat('es-PE', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
        }).format(date);
    };

    const handleDownload = () => {
        router.get(route('contracts.download', contract.id));
    };

    return (
        <div className={`bg-white rounded-lg shadow-sm border-2 transition-all hover:shadow-md ${
            isComplete ? 'border-green-300' : 'border-amber-300'
        }`}>
            <div className="p-4">
                {/* Header con estado */}
                <div className="flex items-start justify-between mb-3">
                    <div className="flex items-center space-x-2">
                        <FileTextIcon className="w-5 h-5 text-blue-600" />
                        <span className={`inline-flex items-center px-2 py-1 text-xs font-medium rounded-full ${
                            isComplete 
                                ? 'bg-green-100 text-green-800' 
                                : 'bg-amber-100 text-amber-800'
                        }`}>
                            {isComplete ? 'Completo' : 'Incompleto'}
                        </span>
                    </div>
                    <div className="flex space-x-1">
                        <button
                            onClick={handleDownload}
                            className="p-1.5 text-gray-600 hover:bg-blue-50 rounded transition-colors"
                            title="Descargar PDF"
                        >
                            <DownloadIcon className="w-4 h-4" />
                        </button>
                        <button
                            onClick={() => onEdit(contract)}
                            className="p-1.5 text-gray-600 hover:bg-blue-50 rounded transition-colors"
                            title="Editar"
                        >
                            <Edit2Icon className="w-4 h-4" />
                        </button>
                        <button
                            onClick={() => {
                                if (confirm('¿Estás seguro de eliminar este contrato?')) {
                                    onDelete(contract);
                                }
                            }}
                            className="p-1.5 text-red-600 hover:bg-red-50 rounded transition-colors"
                            title="Eliminar"
                        >
                            <Trash2Icon className="w-4 h-4" />
                        </button>
                    </div>
                </div>

                {/* Información principal */}
                <div className="space-y-2">
                    <h3 className="font-semibold text-gray-900 text-sm line-clamp-2">
                        {contract.project_name || 'Sin nombre'}
                    </h3>
                    
                    <div className="text-xs text-gray-600 space-y-1">
                        <p>
                            <span className="font-medium">Cliente:</span> {contract.client || 'N/A'}
                        </p>
                        {contract.contract_number && (
                            <p>
                                <span className="font-medium">N° Contrato:</span> {contract.contract_number}
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Monto:</span> {formatCurrency(contract.amount, contract.currency)}
                        </p>
                        {contract.participation_percentage < 100 && (
                            <p>
                                <span className="font-medium">Participación:</span> {contract.participation_percentage}%
                                <span className="ml-1">({formatCurrency(contract.calculated_amount, contract.currency)})</span>
                            </p>
                        )}
                        <p>
                            <span className="font-medium">Fecha:</span> {formatDate(contract.contract_date)}
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}

