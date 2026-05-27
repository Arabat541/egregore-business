<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Fiche Réparation {{ $repair->repair_number }}</title>
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
            border-bottom: 2px double #000;
            padding-bottom: 10px;
        }
        .header h1 {
            font-size: 16px;
            margin: 0;
        }
        .header p {
            margin: 2px 0;
            font-size: 10px;
        }
        .repair-number {
            font-size: 18px;
            font-weight: bold;
            text-align: center;
            margin: 10px 0;
            padding: 5px;
            border: 2px solid #000;
        }
        .section {
            margin-bottom: 10px;
            padding-bottom: 5px;
            border-bottom: 1px dashed #000;
        }
        .section-title {
            font-weight: bold;
            text-transform: uppercase;
            font-size: 10px;
            margin-bottom: 5px;
        }
        .row {
            display: flex;
            justify-content: space-between;
        }
        .label {
            font-weight: bold;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            padding-top: 10px;
            border-top: 2px double #000;
            font-size: 10px;
        }
        .signature {
            margin-top: 30px;
            border-top: 1px solid #000;
            padding-top: 5px;
            text-align: center;
        }
        .barcode {
            text-align: center;
            font-size: 10px;
            letter-spacing: 3px;
            margin: 10px 0;
        }
        @media print {
            body { width: 80mm; }
            .no-print { 
                display: none !important; 
                visibility: hidden !important;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        @if(($settings['print_logo'] ?? '1') == '1')
            <img src="{{ asset('images/logo.png') }}" alt="Logo" style="max-height: 60px; margin-bottom: 5px;">
        @endif
        <h1>{{ $settings['company_name'] ?? 'EGREGORE BUSINESS' }}</h1>
        @if(!empty($settings['company_address']))
            <p>{{ $settings['company_address'] }}</p>
        @endif
        @if(!empty($settings['company_phone']))
            <p>Tél: {{ $settings['company_phone'] }}</p>
        @endif
        <p style="font-weight: bold; margin-top: 5px;">FICHE DE RÉPARATION</p>
    </div>

    <div class="repair-number">
        {{ $repair->repair_number }}
    </div>

    <div class="section">
        <div class="section-title">📅 Date & Heure</div>
        <div class="row">
            <span>Dépôt:</span>
            <span>{{ $repair->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="row">
            <span>Retrait prévu:</span>
            <span>{{ $repair->estimated_completion_date ? $repair->estimated_completion_date->format('d/m/Y') : '-' }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">👤 Client</div>
        <div class="row">
            <span>Nom:</span>
            <span>{{ $repair->customer->full_name }}</span>
        </div>
        <div class="row">
            <span>Tél:</span>
            <span>{{ $repair->customer->phone }}</span>
        </div>
    </div>

    <div class="section">
        <div class="section-title">📱 Appareil</div>
        <div class="row">
            <span>Type:</span>
            <span>{{ ucfirst($repair->device_type) }}</span>
        </div>
        <div class="row">
            <span>Marque:</span>
            <span>{{ $repair->device_brand }}</span>
        </div>
        <div class="row">
            <span>Modèle:</span>
            <span>{{ $repair->device_model }}</span>
        </div>
        @if($repair->device_serial)
        <div class="row">
            <span>IMEI/SN:</span>
            <span>{{ $repair->device_serial }}</span>
        </div>
        @endif
        @if($repair->accessories)
        <div class="row">
            <span>Accessoires:</span>
            <span>{{ $repair->accessories }}</span>
        </div>
        @endif
    </div>

    <div class="section">
        <div class="section-title">🔍 Diagnostic du technicien</div>
        <p style="margin: 5px 0;">{{ $repair->diagnosis }}</p>
        @if($repair->reported_issue)
        <p style="margin: 5px 0; font-size: 10px;"><em>Problème signalé: {{ $repair->reported_issue }}</em></p>
        @endif
    </div>

    <div class="section">
        <div class="section-title">💰 Coût total</div>
        <div class="row" style="font-weight: bold; margin-top: 5px; padding-top: 5px;">
            <span>TOTAL:</span>
            <span>{{ number_format($repair->final_cost ?? $repair->estimated_cost, 0, ',', ' ') }} FCFA</span>
        </div>
        @if($repair->amount_paid > 0)
        <div class="row" style="margin-top: 5px;">
            <span>Montant reçu:</span>
            <span>{{ number_format($repair->amount_paid, 0, ',', ' ') }} FCFA</span>
        </div>
        @endif
        @php
            $amountGiven = request('amount_given', $repair->amount_paid);
            $change = request('change', 0);
        @endphp
        @if($amountGiven > $repair->amount_paid)
        <div class="row">
            <span>Montant donné:</span>
            <span>{{ number_format($amountGiven, 0, ',', ' ') }} FCFA</span>
        </div>
        @endif
        @if($change > 0)
        <div class="row" style="font-weight: bold; background: #ffffcc; padding: 3px;">
            <span>MONNAIE RENDUE:</span>
            <span>{{ number_format($change, 0, ',', ' ') }} FCFA</span>
        </div>
        @endif
        @if(($repair->final_cost ?? $repair->estimated_cost) - $repair->amount_paid > 0)
        <div class="row" style="color: red;">
            <span>Reste à payer:</span>
            <span>{{ number_format(($repair->final_cost ?? $repair->estimated_cost) - $repair->amount_paid, 0, ',', ' ') }} FCFA</span>
        </div>
        @endif
    </div>

    @if($repair->parts && $repair->parts->count() > 0)
    <div class="section">
        <div class="section-title">🔧 Pièces utilisées</div>
        <table style="width:100%; border-collapse:collapse; font-size:10px; margin-top:3px;">
            <thead>
                <tr>
                    <th style="text-align:left; border-bottom:1px solid #000; padding:2px; width:46%">Article</th>
                    <th style="text-align:right; border-bottom:1px solid #000; padding:2px; width:10%">Qté</th>
                    <th style="text-align:right; border-bottom:1px solid #000; padding:2px; width:22%">P.U.</th>
                    <th style="text-align:right; border-bottom:1px solid #000; padding:2px; width:22%">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($repair->parts as $part)
                <tr>
                    <td style="padding:2px;">{{ Str::limit($part->product->name ?? ($part->description ?? 'Pièce'), 14) }}</td>
                    <td style="text-align:right; padding:2px;">{{ $part->quantity }}</td>
                    <td style="text-align:right; padding:2px;">{{ number_format($part->unit_cost, 0, ',', ' ') }}</td>
                    <td style="text-align:right; padding:2px;">{{ number_format($part->total_cost ?? ($part->unit_cost * $part->quantity), 0, ',', ' ') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endif

    <div class="section">
        <div class="section-title">📝 Conditions</div>
        <p style="font-size: 9px; margin: 5px 0;">
            • Le client s'engage à récupérer son appareil dans un délai de 7 jours après notification.<br>
            • L'établissement décline toute responsabilité pour les données perdues.
        </p>
    </div>

    <div class="signature">
        Signature du client
    </div>

    <div class="barcode">
        {{ $repair->repair_number }}
    </div>

    <div class="footer">
        <p>{{ $settings['receipt_footer'] ?? 'Merci de votre confiance !' }}</p>
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    <div class="no-print" style="text-align: center; margin-top: 20px; padding: 15px; background: #f5f5f5;">
        <button onclick="window.print()" style="padding: 12px 25px; cursor: pointer; font-size: 14px; background: #007bff; color: white; border: none; border-radius: 5px;">
            🖨️ Réimprimer
        </button>
        <a href="{{ route('cashier.repairs.create') }}" style="padding: 12px 25px; cursor: pointer; font-size: 14px; background: #ffc107; color: #000; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin-left: 10px;">
            ➕ Nouvelle réparation
        </a>
        <a href="{{ route('cashier.repairs.index') }}" style="padding: 12px 25px; cursor: pointer; font-size: 14px; background: #6c757d; color: white; border: none; border-radius: 5px; text-decoration: none; display: inline-block; margin-left: 10px;">
            📋 Liste réparations
        </a>
    </div>

    <script>
        @if(request()->get('auto'))
        // Impression automatique après chargement
        window.onload = function() {
            window.print();
        };
        @endif
        
        // Fermer après impression si c'est une popup
        window.onafterprint = function() {
            if (window.opener) {
                window.close();
            }
        };
    </script>
</body>
</html>
