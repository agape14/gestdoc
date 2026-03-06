<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateEjecutorObraRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $obra = $this->route('ejecutorObra');
        $cuiRule = 'required|string|max:50|unique:ejecutor_obras,cui,' . ($obra ? $obra->id : 'NULL');

        $rules = [
            'nombre_sigla_entidad' => 'required|string|max:255',
            'nomenclatura' => 'required|string|max:255',
            'descripcion_objeto' => 'required|string',
            'cui' => $cuiRule,
            'numero_contrato' => 'required|string|max:100',
            'fecha_firma_contrato' => 'required|date',
            'monto_total' => 'required|numeric|min:0',
            'fecha_recepcion' => 'nullable|date',
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
            'archivo_contrato' => 'nullable|file|mimes:pdf|max:10240',
            'archivo_acta_recepcion' => 'nullable|file|mimes:pdf|max:10240',
            'archivo_acta_inicio' => 'nullable|file|mimes:pdf|max:10240',
            'archivo_acta_suspension' => 'nullable|file|mimes:pdf|max:10240',
            'archivo_acta_reinicio' => 'nullable|file|mimes:pdf|max:10240',
            'archivo_acta_entrega_terreno' => 'nullable|file|mimes:pdf|max:10240',
            'archivo_resolucion_liquidacion' => 'nullable|file|mimes:pdf|max:10240',
        ];

        // En edición, archivo_contrato es requerido solo si no hay uno existente
        if (!$obra || !$obra->archivo_contrato) {
            $rules['archivo_contrato'] = 'required|file|mimes:pdf|max:10240';
        }

        $tieneSuspension = $this->input('tiene_suspension');
        if ($tieneSuspension === 'SI' || $tieneSuspension === '1' || $tieneSuspension === true) {
            $rules['fecha_suspension'] = 'required|date';
            $rules['fecha_reinicio'] = 'required|date';
            $rules['archivo_acta_suspension'] = 'required|file|mimes:pdf|max:10240';
            $rules['archivo_acta_reinicio'] = 'required|file|mimes:pdf|max:10240';
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
            'archivo_contrato.required' => 'Debe subir el archivo del contrato (PDF).',
            'archivo_contrato.mimes' => 'El contrato debe ser un archivo PDF.',
            'archivo_contrato.max' => 'El archivo del contrato no debe superar 10 MB.',
            'fecha_suspension.required' => 'Si indicó suspensión, la fecha de suspensión es obligatoria.',
            'fecha_reinicio.required' => 'Si indicó suspensión, la fecha de reinicio es obligatoria.',
            'archivo_acta_suspension.required' => 'Si indicó suspensión, debe subir el acta de suspensión (PDF).',
            'archivo_acta_reinicio.required' => 'Si indicó suspensión, debe subir el acta de reinicio (PDF).',
        ];
    }
}
