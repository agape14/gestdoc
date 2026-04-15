<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreEjecutorObraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $rules = [
            'nombre_sigla_entidad' => 'required|string|max:255',
            'nomenclatura' => 'required|string|max:255',
            'descripcion_objeto' => 'required|string',
            'cui' => 'required|string|max:50|unique:ejecutor_obras,cui',
            'numero_contrato' => 'required|string|max:100',
            'fecha_firma_contrato' => 'required|date',
            'monto_total' => 'required|numeric|min:0',
            'plazo' => 'required|integer|min:0',
            'fecha_inicio' => 'nullable|date',
            'fecha_suspension' => 'nullable|date',
            'fecha_reinicio' => 'nullable|date',
            'fecha_final' => 'nullable|date',
            'porcentaje_participacion' => 'nullable|numeric|min:0|max:100',
            'monto_neto' => 'nullable|numeric|min:0',
            'liquidado_recepcionado' => 'nullable|boolean',
            'fecha_entrega_terreno' => 'nullable|date',
            'fecha_recepcion_obra' => 'nullable|date',
            'fecha_aprobacion_liquidacion' => 'nullable|date',
            'folder_id' => 'nullable|exists:folders,id',
            'tiene_adicional_obra' => 'nullable|in:SI,NO',
            'tiene_deductivo_obra' => 'nullable|in:SI,NO',
            'tiene_aprobacion_acto_resolutivo' => 'nullable|in:SI,NO',
            'fecha_adicional_obra' => 'required_if:tiene_adicional_obra,SI|nullable|date',
            'monto_adicional' => 'nullable|numeric|min:0',
            'plazo_adicional' => 'nullable|integer|min:0',
            'fecha_deductivo_obra' => 'required_if:tiene_deductivo_obra,SI|nullable|date',
            'monto_deductivo' => 'nullable|numeric|min:0',
            'plazo_deductivo' => 'nullable|integer|min:0',
            'fecha_aprobacion_acto_resolutivo' => 'required_if:tiene_aprobacion_acto_resolutivo,SI|nullable|date',
            'monto_aprobacion_acto_resolutivo' => 'nullable|numeric|min:0',
            'plazo_aprobacion_acto_resolutivo' => 'nullable|integer|min:0',
            'archivo_contrato' => 'nullable|file|mimes:pdf|max:25600',
            'archivo_acta_recepcion' => 'nullable|file|mimes:pdf|max:25600',
            'archivo_acta_inicio' => 'nullable|file|mimes:pdf|max:25600',
            'archivo_acta_suspension' => 'nullable|file|mimes:pdf|max:25600',
            'archivo_acta_reinicio' => 'nullable|file|mimes:pdf|max:25600',
            'archivo_acta_entrega_terreno' => 'nullable|file|mimes:pdf|max:25600',
            'archivo_acta_adicional' => 'nullable|file|mimes:pdf|max:25600',
            'archivo_acta_deductivo' => 'nullable|file|mimes:pdf|max:25600',
            'archivo_aprobacion_acto_resolutivo' => 'nullable|file|mimes:pdf|max:25600',
            'documentos' => 'nullable|array',
            'documentos.*.nombre' => 'nullable|string|max:255',
            'documentos.*.archivo' => 'nullable|file|mimes:pdf|max:25600',
        ];

        $tieneSuspension = $this->input('tiene_suspension');
        if ($tieneSuspension === 'SI' || $tieneSuspension === '1' || $tieneSuspension === true) {
            $rules['fecha_suspension'] = 'required|date';
            $rules['fecha_reinicio'] = 'required|date';
            $rules['archivo_acta_suspension'] = 'nullable|file|mimes:pdf|max:25600';
            $rules['archivo_acta_reinicio'] = 'nullable|file|mimes:pdf|max:25600';
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'nombre_sigla_entidad.required' => 'El nombre o sigla de la entidad es obligatorio.',
            'nomenclatura.required' => 'La nomenclatura es obligatoria.',
            'descripcion_objeto.required' => 'La descripción del objeto es obligatoria.',
            'cui.required' => 'El CUI es obligatorio.',
            'cui.unique' => 'El CUI ya está registrado.',
            'numero_contrato.required' => 'El número de contrato es obligatorio.',
            'fecha_firma_contrato.required' => 'La fecha de firma del contrato es obligatoria.',
            'monto_total.required' => 'El monto total es obligatorio.',
            'plazo.required' => 'El plazo (días) es obligatorio.',
            'archivo_contrato.mimes' => 'El contrato debe ser un archivo PDF.',
            'archivo_contrato.max' => 'El archivo del contrato no debe superar 25 MB.',
            'fecha_suspension.required' => 'Si indicó suspensión, la fecha de suspensión es obligatoria.',
            'fecha_reinicio.required' => 'Si indicó suspensión, la fecha de reinicio es obligatoria.',
            'fecha_adicional_obra.required_if' => 'Si indicó adicional de obra, la fecha es obligatoria.',
            'fecha_deductivo_obra.required_if' => 'Si indicó deductivo de obra, la fecha es obligatoria.',
            'fecha_aprobacion_acto_resolutivo.required_if' => 'Si indicó aprobación mediante acto resolutivo, la fecha es obligatoria.',
        ];
    }
}
