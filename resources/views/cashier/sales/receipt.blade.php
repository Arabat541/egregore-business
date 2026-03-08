<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Ticket {{ $sale->invoice_number }}</title>
    <style>
        @page {
            margin: 0;
        }
        body {
            font-family: 'Courier New', monospace;
            font-size: 12px;
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0;
        }
        .header p {
            margin: 2px 0;
            font-size: 10px;
        }
        .info {
            border-top: 1px dashed #000;
            border-bottom: 1px dashed #000;
            padding: 5px 0;
            margin-bottom: 10px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: left;
            padding: 3px 0;
        }
        th {
            border-bottom: 1px solid #000;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .total-section {
            border-top: 1px dashed #000;
            padding-top: 5px;
            margin-top: 10px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            font-weight: bold;
        }
        .grand-total {
            font-size: 14px;
            border-top: 2px solid #000;
            padding-top: 5px;
            margin-top: 5px;
        }
        .footer {
            text-align: center;
            margin-top: 20px;
            font-size: 10px;
            border-top: 1px dashed #000;
            padding-top: 10px;
        }
        .barcode {
            text-align: center;
            font-size: 10px;
            letter-spacing: 3px;
            margin: 10px 0;
        }
        @media print {
            body {
                width: 80mm;
            }
            .no-print {
                display: none !important;
                visibility: hidden !important;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $settings['company_name'] ?? 'EGREGORE BUSINESS' }}</h1>
        <p>{{ $settings['company_address'] ?? '' }}</p>
        <p>Tél: {{ $settings['company_phone'] ?? '' }}</p>
    </div>

    <div class="info">
        <div class="info-row">
            <span>N°:</span>
            <span>{{ $sale->invoice_number }}</span>
        </div>
        <div class="info-row">
            <span>Date:</span>
            <span>{{ $sale->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-row">
            <span>Client:</span>
            <span>{{ $sale->client_name }}</span>
        </div>
        <div class="info-row">
            <span>Caisse:</span>
            <span>{{ $sale->user->name ?? '-' }}</span>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Article</th>
                <th class="text-right">Qté</th>
                <th class="text-right">Prix</th>
                <th class="text-right">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->items as $item)
            <tr>
                <td>{{ Str::limit($item->product->name ?? 'Produit', 15) }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 0, ',', ' ') }}</td>
                <td class="text-right">{{ number_format($item->total_price, 0, ',', ' ') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-section">
        <div class="total-row">
            <span>Sous-total:</span>
            <span>{{ number_format($sale->subtotal_amount, 0, ',', ' ') }} FCFA</span>
        </div>
        @if($sale->discount_amount > 0)
        <div class="total-row">
            <span>Remise:</span>
            <span>-{{ number_format($sale->discount_amount, 0, ',', ' ') }} FCFA</span>
        </div>
        @endif
        @if($sale->tax_amount > 0)
        <div class="total-row">
            <span>TVA:</span>
            <span>{{ number_format($sale->tax_amount, 0, ',', ' ') }} FCFA</span>
        </div>
        @endif
        <div class="total-row grand-total">
            <span>TOTAL:</span>
            <span>{{ number_format($sale->total_amount, 0, ',', ' ') }} FCFA</span>
        </div>
        @php
            $amountGiven = $sale->amount_given ?? $sale->amount_paid;
            $change = $amountGiven - $sale->total_amount;
        @endphp
        <div class="total-row">
            <span>Montant reçu:</span>
            <span>{{ number_format($amountGiven, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="total-row">
            <span>Mode paiement:</span>
            <span>{{ $sale->paymentMethod->name ?? '-' }}</span>
        </div>
        @if($change > 0)
        <div class="total-row" style="font-weight: bold; background: #ffffcc; padding: 3px;">
            <span>MONNAIE RENDUE:</span>
            <span>{{ number_format($change, 0, ',', ' ') }} FCFA</span>
        </div>
        @endif
        @if($sale->remaining_amount > 0)
        <div class="total-row">
            <span>Reste à payer:</span>
            <span>{{ number_format($sale->remaining_amount, 0, ',', ' ') }} FCFA</span>
        </div>
        @endif
    </div>

    <div class="barcode">
        {{ $sale->invoice_number }}
    </div>

    <div class="footer">
        <p>{{ $settings['receipt_footer'] ?? 'Merci de votre visite !' }}</p>
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px; padding: 15px; background: #f5f5f5;">
        <button onclick="window.print()" style="padding: 12px 25px; cursor: pointer; font-size: 14px; background: #007bff; color: white; border: none; border-radius: 5px;">
            🖨️ Réimprimer
        </button>
        <a href="{{ route('cashier.sales.create') }}" style="padding: 12px 25px; cursor: pointer; font-size: 14px; background: #28a745; color: white; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin-left: 10px;">
            ➕ Nouvelle vente
        </a>
        <a href="{{ route('cashier.sales.index') }}" style="padding: 12px 25px; cursor: pointer; font-size: 14px; background: #6c757d; color: white; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin-left: 10px;">
            📋 Historique
        </a>
    </div>

    <script>
        @if(request()->get('auto'))
        // Impression automatique après chargement
        window.onload = function() {
            window.print();
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
        @else
        // Fermer après impression si c'est une popup (réimpression manuelle)
        window.onafterprint = function() {
            if (window.opener) {
                window.close();
            }
        };
        @endif
    </script>
</body>
</html>
