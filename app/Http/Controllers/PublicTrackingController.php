<?php

namespace App\Http\Controllers;

use App\Models\Repair;
use App\Models\Sale;
use App\Models\Setting;
use Illuminate\Http\Request;

/**
 * Contrôleur pour les pages publiques de suivi
 * Accessibles via QR code sans authentification
 */
class PublicTrackingController extends Controller
{
    /**
     * Afficher le suivi d'une réparation
     */
    public function repair(string $ticket)
    {
        $repair = Repair::with(['customer', 'parts.product', 'technician'])
            ->where('ticket_number', $ticket)
            ->orWhere('repair_number', $ticket)
            ->firstOrFail();

        $settings = [
            'company_name' => Setting::get('shop_name', 'EGREGORE BUSINESS'),
            'company_phone' => Setting::get('shop_phone', ''),
            'company_address' => Setting::get('shop_address', ''),
            'company_email' => Setting::get('shop_email', ''),
        ];

        return view('public.repair-tracking', compact('repair', 'settings'));
    }

    /**
     * Afficher le reçu d'une vente
     */
    public function sale(string $invoice)
    {
        $sale = Sale::with(['customer', 'reseller', 'items.product', 'user'])
            ->where('invoice_number', $invoice)
            ->firstOrFail();

        $settings = [
            'company_name' => Setting::get('shop_name', 'EGREGORE BUSINESS'),
            'company_phone' => Setting::get('shop_phone', ''),
            'company_address' => Setting::get('shop_address', ''),
            'company_email' => Setting::get('shop_email', ''),
            'company_siret' => Setting::get('shop_siret', ''),
        ];

        return view('public.sale-tracking', compact('sale', 'settings'));
    }
}
