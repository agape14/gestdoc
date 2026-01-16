import React, { useEffect, useState } from 'react';
import { useForm } from '@inertiajs/react';

export default function ContractModal({ show, onClose, contract = null, folderId = null, allFolders = [] }) {
    const isEditing = !!contract;
    const [calculatedAmount, setCalculatedAmount] = useState(0);

    const { data, setData, post, processing, errors, reset } = useForm({
        folder_id: folderId || '',
        client: '',
        project_name: '',
        contract_object: '',
        contract_number: '',
        currency: 'PEN',
        amount: '',
        participation_percentage: '100.00',
        contract_date: '',
        conformity_date: '',
        exchange_rate: '',
        status: 'incompleto',
        file: null,
    });

    useEffect(() => {
        if (contract) {
            setData({
                folder_id: contract.folder_id || folderId || '',
                client: contract.client || '',
                project_name: contract.project_name || '',
                contract_object: contract.contract_object || '',
                contract_number: contract.contract_number || '',
                currency: contract.currency || 'PEN',
                amount: contract.amount || '',
                participation_percentage: contract.participation_percentage || '100.00',
                contract_date: contract.contract_date || '',
                conformity_date: contract.conformity_date || '',
                exchange_rate: contract.exchange_rate || '',
                status: contract.status || 'incompleto',
                file: null,
            });
        } else {
            reset();
            setData({
                folder_id: folderId || '',
                client: '',
                project_name: '',
                contract_object: '',
                contract_number: '',
                currency: 'PEN',
                amount: '',
                participation_percentage: '100.00',
                contract_date: '',
                conformity_date: '',
                exchange_rate: '',
                status: 'incompleto',
                file: null,
            });
        }
    }, [contract, folderId, show]);

    // Calcular monto facturado en tiempo real
    useEffect(() => {
        if (data.amount && data.participation_percentage) {
            const amount = parseFloat(data.amount) || 0;
            const percentage = parseFloat(data.participation_percentage) || 0;
            
            if (percentage < 100) {
                setCalculatedAmount((amount * percentage / 100).toFixed(2));
            } else {
                setCalculatedAmount(amount.toFixed(2));
            }
        }
    }, [data.amount, data.participation_percentage]);

    const handleSubmit = (e) => {
        e.preventDefault();

        const formData = new FormData();
        Object.keys(data).forEach((key) => {
            if (data[key] !== null && data[key] !== '') {
                formData.append(key, data[key]);
            }
        });

        if (isEditing) {
            formData.append('_method', 'PUT');
            post(route('contracts.update', contract.id), {
                data: formData,
                preserveScroll: true,
                forceFormData: true,
                onSuccess: () => {
                    onClose();
                    reset();
                },
            });
        } else {
            post(route('contracts.store'), {
                data: formData,
                preserveScroll: true,
                forceFormData: true,
                onSuccess: () => {
                    onClose();
                    reset();
                },
            });
        }
    };

    const renderFolderOptions = (folders, level = 0) => {
        if (!folders || folders.length === 0) return null;
        
        return folders.map((folder) => (
            <React.Fragment key={folder.id}>
                <option value={folder.id}>
                    {'—'.repeat(level)} {folder.name}
                </option>
                {folder.children && folder.children.length > 0 && renderFolderOptions(folder.children, level + 1)}
            </React.Fragment>
        ));
    };

    if (!show) return null;

    return (
        <>
            <div className="modal show d-block" tabIndex="-1" style={{ backgroundColor: 'rgba(0,0,0,0.5)' }}>
                <div className="modal-dialog modal-dialog-centered modal-xl modal-dialog-scrollable">
                    <div className="modal-content border-0 shadow-lg rounded-4">
                        <div className="modal-header border-0 pb-0">
                            <h5 className="modal-title fw-bold">
                                {isEditing ? 'Editar Contrato' : 'Nuevo Contrato'}
                            </h5>
                            <button type="button" className="btn-close" onClick={onClose}></button>
                        </div>
                        <form onSubmit={handleSubmit}>
                            <div className="modal-body">
                                <div className="row g-3">
                                    {/* Carpeta */}
                                    <div className="col-12">
                                        <label htmlFor="folder_id" className="form-label fw-semibold">
                                            Carpeta <span className="text-danger">*</span>
                                        </label>
                                        <select
                                            id="folder_id"
                                            className={`form-select ${errors.folder_id ? 'is-invalid' : ''}`}
                                            value={data.folder_id}
                                            onChange={(e) => setData('folder_id', e.target.value)}
                                            required
                                        >
                                            <option value="">Seleccione una carpeta</option>
                                            {allFolders && renderFolderOptions(allFolders)}
                                        </select>
                                        {errors.folder_id && (
                                            <div className="invalid-feedback">{errors.folder_id}</div>
                                        )}
                                    </div>

                                    {/* Cliente */}
                                    <div className="col-md-6">
                                        <label htmlFor="client" className="form-label fw-semibold">
                                            Cliente <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="client"
                                            className={`form-control ${errors.client ? 'is-invalid' : ''}`}
                                            value={data.client}
                                            onChange={(e) => setData('client', e.target.value)}
                                            required
                                        />
                                        {errors.client && (
                                            <div className="invalid-feedback">{errors.client}</div>
                                        )}
                                    </div>

                                    {/* Nombre del Proyecto */}
                                    <div className="col-md-6">
                                        <label htmlFor="project_name" className="form-label fw-semibold">
                                            Nombre del Proyecto <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            type="text"
                                            id="project_name"
                                            className={`form-control ${errors.project_name ? 'is-invalid' : ''}`}
                                            value={data.project_name}
                                            onChange={(e) => setData('project_name', e.target.value)}
                                            required
                                        />
                                        {errors.project_name && (
                                            <div className="invalid-feedback">{errors.project_name}</div>
                                        )}
                                    </div>

                                    {/* Objeto del Contrato */}
                                    <div className="col-12">
                                        <label htmlFor="contract_object" className="form-label fw-semibold">
                                            Objeto del Contrato
                                        </label>
                                        <textarea
                                            id="contract_object"
                                            className={`form-control ${errors.contract_object ? 'is-invalid' : ''}`}
                                            rows="2"
                                            value={data.contract_object}
                                            onChange={(e) => setData('contract_object', e.target.value)}
                                        />
                                        {errors.contract_object && (
                                            <div className="invalid-feedback">{errors.contract_object}</div>
                                        )}
                                    </div>

                                    {/* Número de Contrato */}
                                    <div className="col-md-4">
                                        <label htmlFor="contract_number" className="form-label fw-semibold">
                                            N° de Contrato / O/S / Comprobante
                                        </label>
                                        <input
                                            type="text"
                                            id="contract_number"
                                            className={`form-control ${errors.contract_number ? 'is-invalid' : ''}`}
                                            value={data.contract_number}
                                            onChange={(e) => setData('contract_number', e.target.value)}
                                        />
                                        {errors.contract_number && (
                                            <div className="invalid-feedback">{errors.contract_number}</div>
                                        )}
                                    </div>

                                    {/* Moneda */}
                                    <div className="col-md-4">
                                        <label htmlFor="currency" className="form-label fw-semibold">
                                            Moneda <span className="text-danger">*</span>
                                        </label>
                                        <select
                                            id="currency"
                                            className={`form-select ${errors.currency ? 'is-invalid' : ''}`}
                                            value={data.currency}
                                            onChange={(e) => setData('currency', e.target.value)}
                                            required
                                        >
                                            <option value="PEN">Soles (PEN)</option>
                                            <option value="USD">Dólares (USD)</option>
                                        </select>
                                        {errors.currency && (
                                            <div className="invalid-feedback">{errors.currency}</div>
                                        )}
                                    </div>

                                    {/* Monto Contratado */}
                                    <div className="col-md-4">
                                        <label htmlFor="amount" className="form-label fw-semibold">
                                            Monto Contratado <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            id="amount"
                                            step="0.01"
                                            min="0"
                                            className={`form-control ${errors.amount ? 'is-invalid' : ''}`}
                                            value={data.amount}
                                            onChange={(e) => setData('amount', e.target.value)}
                                            required
                                        />
                                        {errors.amount && (
                                            <div className="invalid-feedback">{errors.amount}</div>
                                        )}
                                    </div>

                                    {/* Porcentaje de Participación */}
                                    <div className="col-md-6">
                                        <label htmlFor="participation_percentage" className="form-label fw-semibold">
                                            % de Participación <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            type="number"
                                            id="participation_percentage"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            className={`form-control ${errors.participation_percentage ? 'is-invalid' : ''}`}
                                            value={data.participation_percentage}
                                            onChange={(e) => setData('participation_percentage', e.target.value)}
                                            required
                                        />
                                        {parseFloat(data.participation_percentage) < 100 && (
                                            <div className="form-text text-primary">
                                                Monto facturado: {data.currency === 'USD' ? '$' : 'S/'} {calculatedAmount}
                                            </div>
                                        )}
                                        {errors.participation_percentage && (
                                            <div className="invalid-feedback">{errors.participation_percentage}</div>
                                        )}
                                    </div>

                                    {/* Tipo de Cambio */}
                                    <div className="col-md-6">
                                        <label htmlFor="exchange_rate" className="form-label fw-semibold">
                                            Tipo de Cambio
                                        </label>
                                        <input
                                            type="number"
                                            id="exchange_rate"
                                            step="0.0001"
                                            min="0"
                                            className={`form-control ${errors.exchange_rate ? 'is-invalid' : ''}`}
                                            value={data.exchange_rate}
                                            onChange={(e) => setData('exchange_rate', e.target.value)}
                                        />
                                        {errors.exchange_rate && (
                                            <div className="invalid-feedback">{errors.exchange_rate}</div>
                                        )}
                                    </div>

                                    {/* Fecha de Contrato */}
                                    <div className="col-md-6">
                                        <label htmlFor="contract_date" className="form-label fw-semibold">
                                            Fecha de Contrato / O/S / CP <span className="text-danger">*</span>
                                        </label>
                                        <input
                                            type="date"
                                            id="contract_date"
                                            className={`form-control ${errors.contract_date ? 'is-invalid' : ''}`}
                                            value={data.contract_date}
                                            onChange={(e) => setData('contract_date', e.target.value)}
                                            required
                                        />
                                        {errors.contract_date && (
                                            <div className="invalid-feedback">{errors.contract_date}</div>
                                        )}
                                    </div>

                                    {/* Fecha de Conformidad */}
                                    <div className="col-md-6">
                                        <label htmlFor="conformity_date" className="form-label fw-semibold">
                                            Fecha de la Conformidad
                                        </label>
                                        <input
                                            type="date"
                                            id="conformity_date"
                                            className={`form-control ${errors.conformity_date ? 'is-invalid' : ''}`}
                                            value={data.conformity_date}
                                            onChange={(e) => setData('conformity_date', e.target.value)}
                                        />
                                        {errors.conformity_date && (
                                            <div className="invalid-feedback">{errors.conformity_date}</div>
                                        )}
                                    </div>

                                    {/* Estado */}
                                    <div className="col-12">
                                        <label className="form-label fw-semibold">
                                            Estado del Contrato <span className="text-danger">*</span>
                                        </label>
                                        <div className="d-flex gap-3">
                                            <div className="form-check">
                                                <input
                                                    className="form-check-input"
                                                    type="radio"
                                                    name="status"
                                                    id="status_complete"
                                                    value="completo"
                                                    checked={data.status === 'completo'}
                                                    onChange={(e) => setData('status', e.target.value)}
                                                />
                                                <label className="form-check-label" htmlFor="status_complete">
                                                    Completo
                                                </label>
                                            </div>
                                            <div className="form-check">
                                                <input
                                                    className="form-check-input"
                                                    type="radio"
                                                    name="status"
                                                    id="status_incomplete"
                                                    value="incompleto"
                                                    checked={data.status === 'incompleto'}
                                                    onChange={(e) => setData('status', e.target.value)}
                                                />
                                                <label className="form-check-label" htmlFor="status_incomplete">
                                                    Incompleto
                                                </label>
                                            </div>
                                        </div>
                                        {errors.status && (
                                            <div className="text-danger small mt-1">{errors.status}</div>
                                        )}
                                    </div>

                                    {/* Archivo PDF */}
                                    <div className="col-12">
                                        <label htmlFor="file" className="form-label fw-semibold">
                                            Contrato (PDF) {!isEditing && <span className="text-danger">*</span>}
                                        </label>
                                        <input
                                            type="file"
                                            id="file"
                                            className={`form-control ${errors.file ? 'is-invalid' : ''}`}
                                            accept=".pdf"
                                            onChange={(e) => setData('file', e.target.files[0])}
                                            required={!isEditing}
                                        />
                                        <div className="form-text">
                                            Máximo 10 MB, formato PDF
                                        </div>
                                        {errors.file && (
                                            <div className="invalid-feedback d-block">{errors.file}</div>
                                        )}
                                    </div>
                                </div>
                            </div>
                            <div className="modal-footer border-0 pt-0">
                                <button type="button" className="btn btn-secondary" onClick={onClose}>
                                    Cancelar
                                </button>
                                <button type="submit" className="btn btn-primary" disabled={processing}>
                                    {processing ? (
                                        <>
                                            <span className="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Guardando...
                                        </>
                                    ) : (
                                        isEditing ? 'Actualizar' : 'Guardar Contrato'
                                    )}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </>
    );
}
