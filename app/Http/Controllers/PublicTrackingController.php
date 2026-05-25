<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * Contrôleur pour les pages publiques de suivi
 * Accessibles via QR code sans authentification.
 *
 * SÉCURITÉ : Ces pages sont publiques — ne jamais exposer :
 *   - L'IMEI complet de l'appareil (masqué partiellement)
 *   - Le mot de passe de l'appareil (exclu du chargement)
 *   - Le numéro de téléphone complet du client (masqué partiellement)
 *   - Les prix d'achat ou marges
 *   - L'identité interne des employés
 */
class PublicTrackingController extends Controller
{
    /**
     * Masque partiellement un numéro de téléphone.
     * Ex: "0612345678" → "06****5678"
     */
    private function maskPhone(?string $phone): string
    {
        if (!$phone || strlen($phone) < 6) {
            return $phone ?? '';
        }
        $visible = 2;
        return substr($phone, 0, $visible)
            . str_repeat('*', max(0, strlen($phone) - $visible * 2))
            . substr($phone, -$visible);
    }

    /**
     * Masque un IMEI : affiche seulement les 4 derniers chiffres.
     * Ex: "356938035643809" → "***********3809"
     */
    private function maskImei(?string $imei): string
    {
        if (!$imei || strlen($imei) < 4) {
            return '****';
        }
        return str_repeat('*', strlen($imei) - 4) . substr($imei, -4);
    }

    /**
     * Afficher le suivi d'une réparation (vue publique via QR code)
     */
    public function repair(string $ticket)
    {
        // Charger uniquement les données nécessaires au suivi public.
        // device_password est exclu (hidden dans le modèle Repair).
        // On ne charge pas 'technician' pour ne pas exposer les noms d'employés.
        $repair = Repair::with(['customer:id,first_name,last_name,phone', 'parts.product:id,name'])
            ->where('repair_number', $ticket)
            ->firstOrFail();

        // Données publiques limitées (pas l'IMEI complet, pas le téléphone complet)
        $publicRepair = [
            'repair_number'     => $repair->repair_number,
            'status'            => $repair->status,
            'status_label'      => $repair->status_label,
            'status_color'      => $repair->status_color,
            'device_type'       => $repair->device_type,
            'device_brand'      => $repair->device_brand,
            'device_model'      => $repair->device_model,
            'device_imei_masked' => $this->maskImei($repair->device_imei),
            'customer_name'     => $repair->customer
                ? $repair->customer->first_name . ' ' . substr($repair->customer->last_name, 0, 1) . '.'
                : 'Client',
            'customer_phone_masked' => $this->maskPhone($repair->customer->phone ?? null),
            'estimated_completion_date' => $repair->estimated_completion_date,
            'parts'             => $repair->parts->map(fn($p) => [
                'name'     => $p->product->name ?? 'Pièce',
                'quantity' => $p->quantity,
            ]),
        ];

        $settings = [
            'company_name'    => Setting::get('shop_name', 'EGREGORE BUSINESS'),
            'company_phone'   => Setting::get('shop_phone', ''),
            'company_address' => Setting::get('shop_address', ''),
            'company_email'   => Setting::get('shop_email', ''),
        ];

        return view('public.repair-tracking', compact('publicRepair', 'settings'));
    }

    /**
     * Afficher le reçu d'une vente (vue publique via QR code)
     */
    public function sale(string $invoice)
    {
        // Ne pas charger 'user' pour ne pas exposer le nom de l'employé.
        $sale = Sale::with(['customer:id,first_name,last_name,phone', 'reseller:id,company_name,phone', 'items.product:id,name'])
            ->where('invoice_number', $invoice)
            ->firstOrFail();

        // Données publiques limitées (pas les prix d'achat)
        $publicSale = [
            'invoice_number'  => $sale->invoice_number,
            'created_at'      => $sale->created_at,
            'total_amount'    => $sale->total_amount,
            'payment_status'  => $sale->payment_status,
            'customer_name'   => $sale->client_name,
            'customer_phone_masked' => $this->maskPhone(
                $sale->customer->phone ?? $sale->reseller->phone ?? null
            ),
            'items' => $sale->items->map(fn($i) => [
                'name'       => $i->product->name ?? 'Article',
                'quantity'   => $i->quantity,
                'unit_price' => $i->unit_price,
                'total'      => $i->total_price,
            ]),
        ];

        $settings = [
            'company_name'    => Setting::get('shop_name', 'EGREGORE BUSINESS'),
            'company_phone'   => Setting::get('shop_phone', ''),
            'company_address' => Setting::get('shop_address', ''),
            'company_email'   => Setting::get('shop_email', ''),
            'company_siret'   => Setting::get('shop_siret', ''),
        ];

        return view('public.sale-tracking', compact('publicSale', 'settings'));
    }
}
