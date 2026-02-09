<?php

namespace App\Services;

use App\Models\Repair;
use App\Models\Sale;
use App\Models\Setting;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * Service pour la gestion des impressions thermiques
 * Format optimisé pour imprimantes 80mm (ESC/POS)
 */
class ThermalPrinterService
{
    // Largeur standard pour imprimante 80mm (48 caractères)
    const PAPER_WIDTH_80MM = 48;
    const PAPER_WIDTH_58MM = 32;

    protected int $paperWidth;
    protected array $settings;

    public function __construct()
    {
        $this->paperWidth = self::PAPER_WIDTH_80MM;
        $this->loadSettings();
    }

    /**
     * Charger les paramètres d'impression depuis la configuration
     */
    protected function loadSettings(): void
    {
        $this->settings = [
            'shop_name' => Setting::get('shop_name', 'EGREGORE BUSINESS'),
            'shop_address' => Setting::get('shop_address', ''),
            'shop_phone' => Setting::get('shop_phone', ''),
            'shop_email' => Setting::get('shop_email', ''),
            'footer_message' => Setting::get('receipt_footer', 'Merci de votre confiance !'),
            'show_qr_code' => Setting::get('receipt_show_qr', true),
            'warranty_days' => Setting::get('repair_warranty_days', 7),
        ];
    }

    /**
     * Générer un QR Code en base64 pour inclusion dans le ticket
     */
    public function generateQrCode(string $data, int $size = 150): string
    {
        try {
            $qrCode = QrCode::format('svg')
                ->size($size)
                ->margin(1)
                ->generate($data);
            
            return 'data:image/svg+xml;base64,' . base64_encode($qrCode);
        } catch (\Exception $e) {
            return '';
        }
    }

    /**
     * Générer l'URL de suivi pour une réparation
     */
    public function getRepairTrackingUrl(Repair $repair): string
    {
        $ticketNumber = $repair->ticket_number ?? $repair->repair_number;
        return route('track.repair', ['ticket' => $ticketNumber]);
    }

    /**
     * Générer les données du ticket de réparation
     */
    public function getRepairTicketData(Repair $repair, array $options = []): array
    {
        $trackingUrl = $this->getRepairTrackingUrl($repair);
        
        return [
            'shop' => $this->settings,
            'repair' => $repair,
            'customer' => $repair->customer,
            'parts' => $repair->parts()->with('product')->get(),
            'qr_code' => $this->settings['show_qr_code'] ? $this->generateQrCode($trackingUrl) : null,
            'tracking_url' => $trackingUrl,
            'amount_given' => $options['amount_given'] ?? $repair->amount_paid,
            'change' => $options['change'] ?? 0,
            'print_date' => now(),
            'warranty_until' => $repair->delivered_at 
                ? $repair->delivered_at->addDays($this->settings['warranty_days']) 
                : now()->addDays($this->settings['warranty_days']),
        ];
    }

    /**
     * Générer les données du ticket de vente
     */
    public function getSaleTicketData(Sale $sale, array $options = []): array
    {
        $trackingUrl = route('track.sale', ['invoice' => $sale->invoice_number]);
        
        return [
            'shop' => $this->settings,
            'sale' => $sale,
            'customer' => $sale->customer,
            'items' => $sale->items()->with('product')->get(),
            'qr_code' => $this->settings['show_qr_code'] 
                ? $this->generateQrCode($trackingUrl) 
                : null,
            'tracking_url' => $trackingUrl,
            'amount_given' => $options['amount_given'] ?? $sale->total_amount,
            'change' => $options['change'] ?? 0,
            'print_date' => now(),
        ];
    }

    /**
     * Formater un montant pour affichage
     */
    public function formatAmount(float $amount): string
    {
        return number_format($amount, 0, ',', ' ') . ' F';
    }

    /**
     * Centrer un texte sur la largeur du papier
     */
    public function centerText(string $text): string
    {
        $textLength = mb_strlen($text);
        if ($textLength >= $this->paperWidth) {
            return $text;
        }
        $padding = floor(($this->paperWidth - $textLength) / 2);
        return str_repeat(' ', $padding) . $text;
    }

    /**
     * Créer une ligne de séparation
     */
    public function separator(string $char = '-'): string
    {
        return str_repeat($char, $this->paperWidth);
    }

    /**
     * Formater une ligne avec libellé et valeur alignés
     */
    public function formatLine(string $label, string $value, string $separator = '.'): string
    {
        $labelLength = mb_strlen($label);
        $valueLength = mb_strlen($value);
        $dotsCount = $this->paperWidth - $labelLength - $valueLength - 2;
        
        if ($dotsCount < 1) {
            return $label . ' ' . $value;
        }
        
        return $label . ' ' . str_repeat($separator, $dotsCount) . ' ' . $value;
    }
}
