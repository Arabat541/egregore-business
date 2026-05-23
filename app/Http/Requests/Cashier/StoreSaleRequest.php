<?php

declare(strict_types=1);

namespace App\Http\Requests\Cashier;

use Illuminate\Foundation\Http\FormRequest;

class StoreSaleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('caissiere') ?? false;
    }

    public function rules(): array
    {
        return [
            'client_type'          => 'required|in:walk-in,customer,reseller',
            'customer_id'          => 'required_if:client_type,customer|nullable|exists:customers,id',
            'reseller_id'          => 'required_if:client_type,reseller|nullable|exists:resellers,id',
            'items'                => 'required|array|min:1',
            'items.*.product_id'   => 'required|exists:products,id',
            'items.*.quantity'     => 'required|integer|min:1',
            'items.*.unit_price'   => 'required|numeric|min:0',
            'items.*.discount'     => 'nullable|numeric|min:0',
            'discount_amount'      => 'nullable|numeric|min:0',
            'payment_method_id'    => 'required|exists:payment_methods,id',
            'paid_amount'          => 'required|numeric|min:0',
            'is_credit'            => 'nullable|boolean',
            'notes'                => 'nullable|string',
        ];
    }

    public function messages(): array
    {
        return [
            'client_type.required' => 'Le type de client est obligatoire.',
            'client_type.in'       => 'Type de client invalide.',
            'customer_id.required_if' => 'Veuillez sélectionner un client.',
            'reseller_id.required_if' => 'Veuillez sélectionner un réparateur.',
            'items.required'       => 'La vente doit contenir au moins un article.',
            'items.*.product_id.required' => 'Produit manquant sur la ligne :position.',
            'items.*.quantity.min' => 'La quantité doit être supérieure à 0.',
            'items.*.unit_price.min' => 'Le prix unitaire ne peut pas être négatif.',
            'payment_method_id.required' => 'Veuillez choisir un mode de paiement.',
            'paid_amount.required' => 'Le montant payé est obligatoire.',
        ];
    }
}
