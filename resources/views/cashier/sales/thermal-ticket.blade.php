<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture #{{ $sale->invoice_number }}</title>
    <style>
        /* Reset */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        
        body {
            font-family: 'Courier New', Courier, monospace;
            font-size: 12px;
            line-height: 1.4;
            color: #000;
            background: #fff;
        }
        
        .thermal-ticket {
            width: 80mm;
            max-width: 80mm;
            margin: 0 auto;
            padding: 3mm;
        }
        
        .ticket-header { text-align: center; margin-bottom: 3mm; }
        .shop-name { font-size: 16px; font-weight: bold; }
        .shop-info { font-size: 10px; color: #333; }
        
        .ticket-title { text-align: center; font-size: 14px; font-weight: bold; margin: 2mm 0; padding: 2mm 0; border-top: 1px dashed #000; border-bottom: 1px dashed #000; }
        
        .ticket-info, .ticket-customer { margin: 2mm 0; }
        .info-row { display: flex; justify-content: space-between; padding: 1px 0; }
        .info-label { color: #555; }
        .info-value { font-weight: bold; }
        
        .ticket-separator { border: none; border-top: 1px dashed #000; margin: 2mm 0; }
        
        .customer-name { font-weight: bold; font-size: 11px; }
        
        .ticket-items { width: 100%; border-collapse: collapse; font-size: 11px; }
        .ticket-items th { text-align: left; border-bottom: 1px solid #000; padding: 2px; }
        .ticket-items td { padding: 2px; vertical-align: top; }
        .item-qty, .item-price { text-align: right; white-space: nowrap; }
        .item-name { max-width: 35mm; }
        .item-name small { font-size: 9px; color: #666; }
        
        .ticket-totals { margin: 3mm 0; }
        .total-row { display: flex; justify-content: space-between; padding: 1px 0; }
        .grand-total { font-size: 14px; font-weight: bold; border-top: 2px solid #000; margin-top: 2mm; padding-top: 2mm; }
        .change { color: #000; font-weight: bold; }
        
        .ticket-status { text-align: center; padding: 2mm; margin: 2mm 0; font-weight: bold; }
        .status-paid { background: #d4edda; border: 1px solid #28a745; }
        
        .ticket-qrcode { text-align: center; margin: 3mm 0; }
        .ticket-qrcode img { width: 25mm; height: 25mm; }
        .qr-label { font-size: 9px; color: #666; margin-top: 1mm; }
        
        .ticket-warranty { text-align: center; margin: 2mm 0; padding: 2mm; border: 1px dashed #000; }
        .warranty-days { font-weight: bold; }
        .warranty-until { font-size: 10px; color: #666; }
        
        .ticket-conditions { font-size: 9px; margin: 2mm 0; padding: 2mm; background: #f5f5f5; }
        .ticket-conditions ul { margin-left: 4mm; }
        .ticket-conditions li { margin: 1px 0; }
        
        .ticket-footer { text-align: center; margin-top: 3mm; padding-top: 2mm; border-top: 1px dashed #000; }
        .footer-message { font-style: italic; }
        .footer-date, .footer-copy { font-size: 9px; color: #666; }
        
        .ticket-separator.cut { border-top: 2px dashed #000; margin: 4mm 0; }
        
        /* Actions d'impression - VISIBLE à l'écran uniquement */
        .print-actions {
            text-align: center;
            padding: 15px;
            margin-top: 10px;
        }
        .print-actions .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }
        .btn-primary { background: #007bff; color: white; }
        .btn-secondary { background: #6c757d; color: white; }
        
        /* IMPRESSION - Masquer les boutons */
        @media print {
            @page { size: 80mm auto; margin: 0; }
            body { width: 80mm; margin: 0; padding: 0; }
            
            .print-actions,
            .no-print {
                display: none !important;
                visibility: hidden !important;
            }
        }
    </style>
</head>
<body>
    <div class="thermal-ticket">
        <!-- En-tête boutique -->
        <div class="ticket-header">
            <div class="shop-name">{{ $settings['shop_name'] ?? 'EGREGORE BUSINESS' }}</div>
            <div class="shop-info">
                @if(!empty($settings['shop_address']))
                    {{ $settings['shop_address'] }}<br>
                @endif
                @if(!empty($settings['shop_phone']))
                    Tél: {{ $settings['shop_phone'] }}
                @endif
            </div>
        </div>

        <!-- Titre du ticket -->
        <div class="ticket-title">
            🧾 FACTURE
        </div>

        <!-- Informations ticket -->
        <div class="ticket-info">
            <div class="info-row">
                <span class="info-label">N° Facture:</span>
                <span class="info-value"><strong>{{ $sale->invoice_number }}</strong></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span class="info-value">{{ $sale->created_at->format('d/m/Y H:i') }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Vendeur:</span>
                <span class="info-value">{{ $sale->user->name ?? 'N/A' }}</span>
            </div>
        </div>

        <hr class="ticket-separator">

        <!-- Informations client -->
        @if($sale->customer)
        <div class="ticket-customer">
            <div class="customer-name">{{ $sale->customer->full_name ?? $sale->customer->name }}</div>
            @if($sale->customer->phone)
                <div>📞 {{ $sale->customer->phone }}</div>
            @endif
        </div>
        @elseif($sale->reseller)
        <div class="ticket-customer">
            <div class="customer-name">🏢 {{ $sale->reseller->company_name }}</div>
            @if($sale->reseller->phone)
                <div>📞 {{ $sale->reseller->phone }}</div>
            @endif
        </div>
        @else
        <div class="ticket-customer">
            <div class="customer-name">Client de passage</div>
        </div>
        @endif

        <hr class="ticket-separator">

        <!-- Articles -->
        <table class="ticket-items">
            <thead>
                <tr>
                    <th>Article</th>
                    <th class="item-qty">Qté</th>
                    <th class="item-price">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sale->items as $item)
                <tr>
                    <td class="item-name">
                        {{ $item->product->name ?? $item->product_name ?? 'Article' }}
                        @if($item->unit_price)
                            <br><small>@ {{ number_format($item->unit_price, 0, ',', ' ') }} F</small>
                        @endif
                    </td>
                    <td class="item-qty">{{ $item->quantity }}</td>
                    <td class="item-price">{{ number_format($item->total_price ?? ($item->quantity * $item->unit_price), 0, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totaux -->
        <div class="ticket-totals">
            <div class="total-row">
                <span>Sous-total:</span>
                <span>{{ number_format($sale->subtotal ?? $sale->total_amount, 0, ',', ' ') }} F</span>
            </div>
            
            @if($sale->discount_amount > 0)
            <div class="total-row" style="color: #28a745;">
                <span>Remise:</span>
                <span>-{{ number_format($sale->discount_amount, 0, ',', ' ') }} F</span>
            </div>
            @endif

            @if($sale->tax_amount > 0)
            <div class="total-row">
                <span>TVA:</span>
                <span>{{ number_format($sale->tax_amount, 0, ',', ' ') }} F</span>
            </div>
            @endif

            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>{{ number_format($sale->total_amount, 0, ',', ' ') }} F</span>
            </div>

            <hr class="ticket-separator">

            <div class="total-row payment-received">
                <span>Mode:</span>
                <span>{{ ucfirst($sale->payment_method ?? 'Espèces') }}</span>
            </div>

            @if($sale->amount_paid > 0)
            <div class="total-row">
                <span>Payé:</span>
                <span>{{ number_format($sale->amount_paid, 0, ',', ' ') }} F</span>
            </div>
            @endif

            @if($sale->amount_paid > $sale->total_amount)
            <div class="total-row change">
                <span>MONNAIE:</span>
                <span>{{ number_format($sale->amount_paid - $sale->total_amount, 0, ',', ' ') }} F</span>
            </div>
            @endif
            
            @if($sale->amount_paid < $sale->total_amount && $sale->amount_paid > 0)
            <div class="total-row" style="color: #dc3545;">
                <span>RESTE:</span>
                <span>{{ number_format($sale->total_amount - $sale->amount_paid, 0, ',', ' ') }} F</span>
            </div>
            @endif
        </div>

        <!-- Statut -->
        @if($sale->payment_status === 'paid')
        <div class="ticket-status status-paid">
            ✅ PAYÉ
        </div>
        @elseif($sale->payment_status === 'partial')
        <div class="ticket-status" style="background: #fff3cd; color: #856404;">
            ⏳ PAIEMENT PARTIEL
        </div>
        @else
        <div class="ticket-status" style="background: #f8d7da; color: #842029;">
            ⚠️ EN ATTENTE
        </div>
        @endif

        <!-- QR Code -->
        @if(isset($qrCode))
        <div class="ticket-qrcode">
            <img src="data:image/svg+xml;base64,{{ $qrCode }}" alt="QR Code" style="width: 100px; height: 100px;">
            <div class="qr-label">Scannez pour votre facture</div>
        </div>
        @endif

        <!-- Informations garantie produits -->
        @if($sale->items->where('product.warranty_days', '>', 0)->count() > 0)
        <div class="ticket-warranty">
            <div class="warranty-days">GARANTIE INCLUSE</div>
            <div class="warranty-until">Conservez ce ticket</div>
        </div>
        @endif

        <!-- Conditions -->
        <div class="ticket-conditions">
            <strong>CONDITIONS:</strong>
            <ul>
                <li>Échange sous 24h avec ticket</li>
                <li>Aucun remboursement sur accessoires</li>
                <li>Garantie constructeur selon produit</li>
            </ul>
        </div>

        <!-- Pied de page -->
        <div class="ticket-footer">
            <div class="footer-message">Merci de votre visite !</div>
            <div class="footer-date">{{ now()->format('d/m/Y H:i') }}</div>
            <div class="footer-copy">{{ $settings['shop_name'] ?? 'EGREGORE BUSINESS' }}</div>
        </div>

        <!-- Ligne de coupe -->
        <hr class="ticket-separator cut">
    </div>

    <!-- Actions -->
    <div class="print-actions no-print">
        <button onclick="window.print()" class="btn btn-primary btn-lg">
            🖨️ Imprimer
        </button>
        <button onclick="fermerFenetre()" class="btn btn-secondary">
            ✕ Fermer
        </button>
    </div>

    <script>
        function fermerFenetre() {
            // Essayer de fermer la fenêtre
            if (window.opener || window.history.length <= 1) {
                window.close();
            }
            // Si ça ne marche pas, retour à la page précédente
            setTimeout(function() {
                window.history.back();
            }, 100);
        }
    </script>

    @if(request('auto') == 1)
    <script>
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Rediriger vers nouvelle vente après impression
        window.onafterprint = function() {
            if (window.opener) {
                window.close();
            } else {
                // Rediriger vers le formulaire de nouvelle vente (vide)
                window.location.href = "{{ route('cashier.sales.create') }}";
            }
        };
    </script>
    @endif
</body>
</html>
