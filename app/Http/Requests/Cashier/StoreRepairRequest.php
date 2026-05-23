<?php

declare(strict_types=1);

namespace App\Http\Requests\Cashier;

use Illuminate\Foundation\Http\FormRequest;

class StoreRepairRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('caissiere') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id'               => 'required|exists:customers,id',
            'device_type'               => 'required|string|max:100',
            'device_brand'              => 'required|string|max:100',
            'device_model'              => 'required|string|max:100',
            'device_imei'               => 'nullable|string|max:50',
            'device_password'           => 'nullable|string|max:50',
            'device_condition'          => 'nullable|string',
            'accessories_received'      => 'nullable|string',
            'reported_issue'            => 'required|string',
            'diagnosis'                 => 'required|string',
            'repair_notes'              => 'nullable|string',
            'technician_id'             => 'required|exists:users,id',
            'labor_cost'                => 'nullable|numeric|min:0',
            'final_cost'                => 'required|numeric|min:0',
            'amount_paid'               => 'required|numeric|min:0',
            'amount_given'              => 'nullable|numeric|min:0',
            'payment_method_id'         => 'required|exists:payment_methods,id',
            'estimated_completion_date' => 'required|date',
            'print_ticket'              => 'nullable',
            // Pièces de rechange
            'parts'                     => 'nullable|array',
            'parts.*.product_id'        => 'required_with:parts|exists:products,id',
            'parts.*.quantity'          => 'required_with:parts|integer|min:1',
            'parts.*.unit_price'        => 'required_with:parts|numeric|min:0',
        ];
    }

    public function messages(): array
    {
        return [
            'customer_id.required'               => 'Veuillez sélectionner un client.',
            'device_type.required'               => 'Le type d\'appareil est obligatoire.',
            'device_brand.required'              => 'La marque de l\'appareil est obligatoire.',
            'device_model.required'              => 'Le modèle de l\'appareil est obligatoire.',
            'reported_issue.required'            => 'La description du problème est obligatoire.',
            'diagnosis.required'                 => 'Le diagnostic est obligatoire.',
            'technician_id.required'             => 'Veuillez assigner un technicien.',
            'final_cost.required'                => 'Le coût total est obligatoire.',
            'amount_paid.required'               => 'Le montant avancé est obligatoire.',
            'payment_method_id.required'         => 'Veuillez choisir un mode de paiement.',
            'estimated_completion_date.required' => 'La date de livraison estimée est obligatoire.',
            'estimated_completion_date.date'     => 'La date de livraison n\'est pas valide.',
            'parts.*.product_id.required_with'   => 'Veuillez sélectionner une pièce sur la ligne :position.',
            'parts.*.quantity.min'               => 'La quantité de pièce doit être supérieure à 0.',
        ];
    }
}
