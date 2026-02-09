<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket #{{ $repair->repair_number }}</title>
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
        
        .ticket-device { margin: 2mm 0; padding: 2mm; background: #f5f5f5; }
        .device-type { font-weight: bold; font-size: 12px; }
        .device-info { font-size: 10px; }
        
        .ticket-issue { margin: 2mm 0; }
        .issue-label { font-weight: bold; font-size: 10px; }
        .issue-text { font-size: 11px; padding: 1mm 0; }
        
        .ticket-parts { width: 100%; border-collapse: collapse; font-size: 10px; margin: 2mm 0; }
        .ticket-parts th { text-align: left; border-bottom: 1px solid #000; padding: 2px; }
        .ticket-parts td { padding: 2px; }
        
        .ticket-totals { margin: 3mm 0; }
        .total-row { display: flex; justify-content: space-between; padding: 1px 0; }
        .grand-total { font-size: 14px; font-weight: bold; border-top: 2px solid #000; margin-top: 2mm; padding-top: 2mm; }
        .change { color: #000; font-weight: bold; }
        
        .ticket-status { text-align: center; padding: 2mm; margin: 2mm 0; font-weight: bold; }
        .status-pending { background: #fff3cd; border: 1px solid #ffc107; }
        .status-in_repair { background: #cce5ff; border: 1px solid #007bff; }
        .status-completed, .status-delivered { background: #d4edda; border: 1px solid #28a745; }
        
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
    @php
        $shop = $ticketData['shop'] ?? [];
        $qrCode = $ticketData['qr_code'] ?? null;
        $trackingUrl = $ticketData['tracking_url'] ?? '';
        $amountGiven = $ticketData['amount_given'] ?? $repair->amount_paid;
        $change = $ticketData['change'] ?? 0;
        $warrantyUntil = $ticketData['warranty_until'] ?? now()->addDays(7);
    @endphp

    <div class="thermal-ticket">
        <!-- En-tête boutique -->
        <div class="ticket-header">
            <div class="shop-name">{{ $shop['shop_name'] ?? 'EGREGORE BUSINESS' }}</div>
            <div class="shop-info">
                @if(!empty($shop['shop_address']))
                    {{ $shop['shop_address'] }}<br>
                @endif
                @if(!empty($shop['shop_phone']))
                    Tél: {{ $shop['shop_phone'] }}
                @endif
            </div>
        </div>

        <!-- Titre du ticket -->
        <div class="ticket-title">
            🔧 RÉPARATION
        </div>

        <!-- Informations ticket -->
        <div class="ticket-info">
            <div class="info-row">
                <span class="info-label">N° Ticket:</span>
                <span class="info-value"><strong>{{ $repair->repair_number }}</strong></span>
            </div>
            <div class="info-row">
                <span class="info-label">Date:</span>
                <span class="info-value">{{ $repair->created_at->format('d/m/Y H:i') }}</span>
            </div>
            @if($repair->technician)
            <div class="info-row">
                <span class="info-label">Technicien:</span>
                <span class="info-value">{{ $repair->technician->name }}</span>
            </div>
            @endif
        </div>

        <hr class="ticket-separator">

        <!-- Informations client -->
        <div class="ticket-customer">
            <div class="customer-name">{{ $repair->customer->name ?? $repair->customer->full_name }}</div>
            <div>📞 {{ $repair->customer->phone }}</div>
        </div>

        <!-- Détails appareil -->
        <div class="ticket-repair-details">
            <div class="device-info">
                📱 {{ $repair->device_brand }} {{ $repair->device_model }}
            </div>
            @if($repair->device_imei)
                <div style="font-size: 10px;">IMEI: {{ $repair->device_imei }}</div>
            @endif
            
            <div class="problem-description">
                <strong>Diagnostic:</strong><br>
                {{ $repair->diagnosis ?? $repair->reported_issue }}
            </div>
        </div>

        <hr class="ticket-separator">

        <!-- Pièces utilisées -->
        @if($repair->parts && $repair->parts->count() > 0)
        <div style="margin-bottom: 3mm;">
            <strong>Pièces utilisées:</strong>
            <table class="ticket-items">
                <thead>
                    <tr>
                        <th>Article</th>
                        <th class="item-qty">Qté</th>
                        <th class="item-price">Prix</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($repair->parts as $part)
                    <tr>
                        <td class="item-name">{{ $part->product->name ?? 'Pièce' }}</td>
                        <td class="item-qty">{{ $part->quantity }}</td>
                        <td class="item-price">{{ number_format($part->total_cost, 0, ',', ' ') }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Totaux -->
        <div class="ticket-totals">
            @if($repair->parts_cost > 0)
            <div class="total-row">
                <span>Pièces:</span>
                <span>{{ number_format($repair->parts_cost, 0, ',', ' ') }} F</span>
            </div>
            @endif
            @if($repair->labor_cost > 0)
            <div class="total-row">
                <span>Main d'œuvre:</span>
                <span>{{ number_format($repair->labor_cost, 0, ',', ' ') }} F</span>
            </div>
            @endif
            <div class="total-row grand-total">
                <span>TOTAL:</span>
                <span>{{ number_format($repair->total_cost ?? ($repair->parts_cost + $repair->labor_cost), 0, ',', ' ') }} F</span>
            </div>
            
            @if($repair->amount_paid > 0)
            <div class="total-row payment-received">
                <span>Payé:</span>
                <span>{{ number_format($repair->amount_paid, 0, ',', ' ') }} F</span>
            </div>
            @endif

            @php
                $remaining = ($repair->total_cost ?? 0) - ($repair->amount_paid ?? 0);
            @endphp
            @if($remaining > 0)
            <div class="total-row" style="font-weight: bold; color: #c00;">
                <span>Reste à payer:</span>
                <span>{{ number_format($remaining, 0, ',', ' ') }} F</span>
            </div>
            @endif

            @if($amountGiven > $repair->amount_paid && $change > 0)
            <hr class="ticket-separator">
            <div class="total-row">
                <span>Reçu:</span>
                <span>{{ number_format($amountGiven, 0, ',', ' ') }} F</span>
            </div>
            <div class="total-row change">
                <span>MONNAIE:</span>
                <span>{{ number_format($change, 0, ',', ' ') }} F</span>
            </div>
            @endif
        </div>

        <!-- Statut -->
        @php
            $statusClass = match($repair->status) {
                'delivered' => 'status-delivered',
                'repaired', 'ready_for_pickup' => 'status-paid',
                default => 'status-pending'
            };
            $statusLabel = match($repair->status) {
                'delivered' => 'LIVRÉ',
                'repaired' => 'RÉPARÉ',
                'ready_for_pickup' => 'PRÊT',
                'in_repair' => 'EN COURS',
                default => 'EN ATTENTE'
            };
        @endphp
        <div class="ticket-status {{ $statusClass }}">
            {{ $statusLabel }}
        </div>

        <!-- QR Code -->
        @if($qrCode)
        <div class="ticket-qrcode">
            <img src="{{ $qrCode }}" alt="QR Code">
            <div class="qr-label">Scannez pour suivre votre réparation</div>
        </div>
        @endif

        <!-- Garantie -->
        <div class="ticket-warranty">
            <div class="warranty-days">GARANTIE 7 JOURS</div>
            <div class="warranty-until">Valable jusqu'au {{ $warrantyUntil->format('d/m/Y') }}</div>
        </div>

        <!-- Conditions -->
        <div class="ticket-conditions">
            <strong>CONDITIONS:</strong>
            <ul>
                <li>Appareil à récupérer sous 30 jours</li>
                <li>La garantie ne couvre pas les dommages physiques</li>
                <li>Ticket obligatoire pour retrait</li>
                <li>Aucun remboursement après réparation</li>
            </ul>
        </div>

        <!-- Pied de page -->
        <div class="ticket-footer">
            <div class="footer-message">{{ $shop['footer_message'] ?? 'Merci de votre confiance !' }}</div>
            <div class="footer-date">Imprimé le {{ now()->format('d/m/Y à H:i') }}</div>
            <div class="footer-copy">{{ $shop['shop_name'] ?? 'EGREGORE BUSINESS' }}</div>
        </div>

        <!-- Ligne de coupe -->
        <hr class="ticket-separator cut">
    </div>

    <!-- Actions (visible uniquement à l'écran) -->
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
        // Impression automatique au chargement
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
        
        // Rediriger vers nouvelle réparation après impression
        window.onafterprint = function() {
            if (window.opener) {
                window.close();
            } else {
                // Rediriger vers le formulaire de nouvelle réparation (vide)
                window.location.href = "{{ route('cashier.repairs.create') }}";
            }
        };
    </script>
    @endif
</body>
</html>
