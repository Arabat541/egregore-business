<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Reçu Paiement {{ $reseller->company_name }}</title>
    <style>
        @page { margin: 0; }
        * { color: #000 !important; }
        body {
            font-family: 'Courier New', monospace;
            font-size: 15px;
            font-weight: 700;
            width: 80mm;
            margin: 0 auto;
            padding: 10px;
        }
        .header {
            text-align: center;
            margin-bottom: 12px;
        }
        .header h1 {
            font-size: 20px;
            font-weight: 900;
            margin: 0;
            letter-spacing: 1px;
        }
        .header p {
            margin: 3px 0;
            font-size: 13px;
            font-weight: 700;
        }
        .title-band {
            text-align: center;
            border-top: 3px solid #000;
            border-bottom: 3px solid #000;
            padding: 6px 0;
            margin-bottom: 10px;
            font-size: 14px;
            font-weight: 900;
            letter-spacing: 1px;
        }
        .info-block {
            border-bottom: 2px dashed #000;
            padding-bottom: 8px;
            margin-bottom: 8px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            font-size: 13px;
            font-weight: 700;
            padding: 2px 0;
        }
        .info-row.bold {
            font-size: 14px;
            font-weight: 900;
        }
        /* ── Bloc montant ── */
        .amount-block {
            border: 3px solid #000;
            padding: 10px 6px;
            margin: 10px 0;
            text-align: center;
        }
        .amount-label {
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .amount-value {
            font-size: 22px;
            font-weight: 900;
            letter-spacing: 1px;
            margin-top: 4px;
        }
        /* ── Facture(s) concernée(s) ── */
        .invoice-block {
            border: 2px solid #000;
            padding: 6px;
            margin: 8px 0;
            font-weight: 900;
            font-size: 13px;
        }
        .invoice-block .inv-title {
            text-decoration: underline;
            font-size: 12px;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }
        .invoice-row {
            display: flex;
            justify-content: space-between;
            padding: 2px 0;
        }
        /* ── Bilan dette ── */
        .debt-block {
            border-top: 2px dashed #000;
            border-bottom: 2px dashed #000;
            padding: 6px 0;
            margin: 8px 0;
        }
        /* ── Retours produits ── */
        .returns-block {
            margin: 8px 0;
            font-size: 12px;
        }
        .returns-block .ret-title {
            font-size: 12px;
            font-weight: 900;
            text-decoration: underline;
            margin-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        td { padding: 2px 0; font-weight: 700; }
        /* ── Signatures ── */
        .signatures {
            display: flex;
            justify-content: space-between;
            margin-top: 20px;
            font-size: 12px;
            font-weight: 700;
        }
        .sig-box {
            text-align: center;
            width: 45%;
        }
        .sig-line {
            border-top: 1px solid #000;
            margin-top: 20px;
            padding-top: 3px;
        }
        .footer {
            text-align: center;
            margin-top: 15px;
            font-size: 12px;
            font-weight: 700;
            border-top: 2px dashed #000;
            padding-top: 8px;
        }
        @media print {
            body { width: 80mm; }
            .no-print { display: none !important; }
        }
    </style>
</head>
<body>

    {{-- En-tête boutique --}}
    <div class="header">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" style="max-height:180px;width:auto;margin-bottom:6px;">
        <h1>{{ $settings['shop_name'] }}</h1>
        @if($settings['shop_address'])
        <p>{{ $settings['shop_address'] }}</p>
        @endif
        @if($settings['shop_phone'])
        <p>Tél : {{ $settings['shop_phone'] }}</p>
        @endif
    </div>

    {{-- Titre du document --}}
    <div class="title-band">
        @if($payment->is_cancelled)
            *** ANNULÉ *** REÇU DE PAIEMENT CRÉANCE *** ANNULÉ ***
        @else
            REÇU DE PAIEMENT CRÉANCE
        @endif
    </div>

    {{-- Bandeau annulation --}}
    @if($payment->is_cancelled)
    <div style="border:3px solid #000;padding:8px;margin-bottom:10px;text-align:center;background:#000;color:#fff;">
        <div style="font-size:18px;font-weight:900;letter-spacing:2px;">PAIEMENT ANNULÉ</div>
        <div style="font-size:12px;margin-top:4px;">
            Le {{ $payment->cancelled_at->format('d/m/Y à H:i') }}
            @if($payment->cancelledBy) — par {{ $payment->cancelledBy->name }} @endif
        </div>
        @if($payment->cancellation_reason)
        <div style="font-size:12px;margin-top:2px;font-style:italic;">
            Motif : {{ $payment->cancellation_reason }}
        </div>
        @endif
    </div>
    @endif

    {{-- Infos document --}}
    <div class="info-block">
        <div class="info-row bold">
            <span>Réf :</span>
            <span>PAY-{{ str_pad($payment->id, 5, '0', STR_PAD_LEFT) }}</span>
        </div>
        <div class="info-row">
            <span>Date :</span>
            <span>{{ $payment->created_at->format('d/m/Y H:i') }}</span>
        </div>
        <div class="info-row">
            <span>Caissier :</span>
            <span>{{ $payment->user->name ?? '—' }}</span>
        </div>
        <div class="info-row">
            <span>Mode :</span>
            <span>{{ $payment->payment_method_label }}</span>
        </div>
    </div>

    {{-- Revendeur --}}
    <div class="info-block">
        <div class="info-row bold">
            <span>Réparateur :</span>
            <span>{{ $reseller->company_name }}</span>
        </div>
        <div class="info-row">
            <span>Contact :</span>
            <span>{{ $reseller->contact_name }}</span>
        </div>
        @if($reseller->phone)
        <div class="info-row">
            <span>Tél :</span>
            <span>{{ $reseller->phone }}</span>
        </div>
        @endif
    </div>

    {{-- Montant payé (mis en évidence) --}}
    <div class="amount-block">
        <div class="amount-label">MONTANT VERSÉ</div>
        <div class="amount-value">{{ number_format($payment->amount, 0, ',', ' ') }} FCFA</div>
        @if($payment->cash_amount > 0 && $payment->return_amount > 0)
        <div style="font-size:12px;margin-top:4px;">
            Espèces : {{ number_format($payment->cash_amount, 0, ',', ' ') }} F
            &nbsp;|&nbsp;
            Retours : {{ number_format($payment->return_amount, 0, ',', ' ') }} F
        </div>
        @endif
    </div>

    {{-- Facture(s) concernée(s) --}}
    @if($payment->sale)
    <div class="invoice-block">
        <div class="inv-title">FACTURE CONCERNÉE</div>
        <div class="invoice-row">
            <span>N° :</span>
            <span>{{ $payment->sale->invoice_number }}</span>
        </div>
        <div class="invoice-row">
            <span>Total facture :</span>
            <span>{{ number_format($payment->sale->total_amount, 0, ',', ' ') }} F</span>
        </div>
        @php $remainingAfter = max(0, (float)$payment->sale->amount_due); @endphp
        <div class="invoice-row">
            <span>Reste après paiement :</span>
            <span>{{ number_format($remainingAfter, 0, ',', ' ') }} F</span>
        </div>
    </div>
    @endif

    {{-- Retours de produits --}}
    @if($payment->productReturns->isNotEmpty())
    <div class="returns-block">
        <div class="ret-title">RETOURS PRODUITS INCLUS</div>
        <table>
            @foreach($payment->productReturns as $ret)
            <tr>
                <td>{{ $ret->product->name ?? 'Produit' }}</td>
                <td style="text-align:right;">x{{ $ret->quantity }}</td>
                <td style="text-align:right;">{{ number_format($ret->total_value, 0, ',', ' ') }} F</td>
            </tr>
            @endforeach
        </table>
    </div>
    @endif

    {{-- Bilan dette --}}
    <div class="debt-block">
        <div class="info-row">
            <span>Dette avant :</span>
            <span>{{ number_format($payment->debt_before, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="info-row">
            <span>Paiement :</span>
            <span>- {{ number_format($payment->amount, 0, ',', ' ') }} FCFA</span>
        </div>
        <div class="info-row bold">
            <span>Nouvelle dette :</span>
            <span>{{ number_format($payment->debt_after, 0, ',', ' ') }} FCFA</span>
        </div>
    </div>

    {{-- Signatures --}}
    <div class="signatures">
        <div class="sig-box">
            <div class="sig-line">Signature Réparateur</div>
        </div>
        <div class="sig-box">
            <div class="sig-line">Signature Boutique</div>
        </div>
    </div>

    {{-- Pied de page --}}
    <div class="footer">
        <p>Document valant reçu de paiement</p>
        <p>{{ now()->format('d/m/Y H:i:s') }}</p>
    </div>

    {{-- Boutons no-print --}}
    <div class="no-print" style="text-align:center;margin-top:20px;padding:15px;background:#f5f5f5;">
        @if(!$payment->is_cancelled)
        <button onclick="window.print()"
                style="padding:12px 25px;cursor:pointer;font-size:14px;background:#007bff;color:white;border:none;border-radius:5px;">
            Imprimer
        </button>
        @endif
        <a href="{{ route('cashier.reseller-payments.show', $reseller) }}"
           style="padding:12px 25px;font-size:14px;background:#6c757d;color:white;border-radius:5px;text-decoration:none;display:inline-block;margin-left:10px;">
            Retour
        </a>
        @if(!$payment->is_cancelled)
        <a href="{{ route('cashier.reseller-payments.cancel-form', [$reseller, $payment]) }}"
           style="padding:12px 25px;font-size:14px;background:#dc3545;color:white;border-radius:5px;text-decoration:none;display:inline-block;margin-left:10px;">
            Annuler ce paiement
        </a>
        @endif
    </div>

    <script>
        @if(!$payment->is_cancelled)
        window.onload = function() { window.print(); };
        window.onafterprint = function() {
            if (window.opener) { window.close(); }
        };
        @endif
    </script>
</body>
</html>
