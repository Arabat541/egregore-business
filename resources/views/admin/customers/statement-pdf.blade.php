<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Relevé de créance — {{ $reseller->company_name }}</title>
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: 'DejaVu Sans', Arial, sans-serif; font-size: 10px; color: #1a1a1a; }
    .page { padding: 18mm 15mm; }

    /* En-tête */
    .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8mm; border-bottom: 2px solid #1a56db; padding-bottom: 4mm; }
    .company-name { font-size: 16px; font-weight: 700; color: #1a56db; }
    .company-info { font-size: 9px; color: #666; margin-top: 2px; }
    .doc-title { text-align: right; }
    .doc-title h1 { font-size: 14px; font-weight: 700; color: #1a56db; }
    .doc-title .period { font-size: 9px; color: #666; margin-top: 2px; }

    /* Bloc client */
    .client-block { background: #f0f4ff; border-left: 4px solid #1a56db; padding: 4mm 5mm; margin-bottom: 6mm; border-radius: 2px; }
    .client-block .label { font-size: 8px; color: #666; text-transform: uppercase; letter-spacing: 0.5px; }
    .client-block .name { font-size: 13px; font-weight: 700; color: #1a1a1a; margin: 1mm 0; }
    .client-block .contact { font-size: 9px; color: #444; }

    /* KPIs */
    .kpis { display: flex; gap: 3mm; margin-bottom: 6mm; }
    .kpi { flex: 1; border: 1px solid #e5e7eb; border-radius: 3px; padding: 3mm 4mm; text-align: center; }
    .kpi.red { border-color: #ef4444; background: #fff5f5; }
    .kpi.green { border-color: #10b981; background: #f0fdf4; }
    .kpi-value { font-size: 12px; font-weight: 700; }
    .kpi-value.red { color: #dc2626; }
    .kpi-value.green { color: #059669; }
    .kpi-label { font-size: 7.5px; color: #666; margin-top: 1mm; }

    /* Tables */
    .section-title { font-size: 11px; font-weight: 700; color: #1a1a1a; margin: 5mm 0 2mm; padding-bottom: 1mm; border-bottom: 1px solid #e5e7eb; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 4mm; }
    thead tr { background: #1a56db; color: white; }
    th { padding: 2.5mm 2mm; font-size: 8.5px; font-weight: 600; text-align: left; }
    td { padding: 2mm 2mm; font-size: 8.5px; border-bottom: 1px solid #f3f4f6; }
    tr:nth-child(even) { background: #f9fafb; }
    .text-right { text-align: right; }
    .text-red { color: #dc2626; font-weight: 700; }
    .text-green { color: #059669; }
    .shop-badge { background: #e5e7eb; padding: 1px 4px; border-radius: 2px; font-size: 7.5px; }
    tfoot tr { background: #f1f5f9; font-weight: 700; }
    tfoot td { padding: 2.5mm 2mm; border-top: 1.5px solid #cbd5e1; }

    /* Récap final */
    .summary-box { border: 2px solid #dc2626; border-radius: 3px; padding: 5mm; margin-top: 4mm; display: flex; justify-content: space-between; align-items: center; background: #fff5f5; }
    .summary-box .label { font-size: 12px; font-weight: 700; color: #dc2626; }
    .summary-box .sub { font-size: 8.5px; color: #666; margin-top: 1mm; }
    .summary-box .amount { font-size: 20px; font-weight: 700; color: #dc2626; text-align: right; }
    .summary-box .amount-sub { font-size: 8.5px; color: #888; text-align: right; }

    /* Pied de page */
    .footer { margin-top: 6mm; font-size: 8px; color: #aaa; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 3mm; }
    .empty { text-align: center; padding: 6mm; color: #888; font-style: italic; }
    .products-list { font-size: 7.5px; color: #444; }
</style>
</head>
<body>
<div class="page">

    <!-- En-tête -->
    <div class="header">
        <div>
            <div class="company-name">{{ strtoupper($companyName) }}</div>
            <div class="company-info">
                @if($companyAddress){{ $companyAddress }}<br>@endif
                @if($companyPhone)Tél : {{ $companyPhone }}@endif
            </div>
        </div>
        <div class="doc-title">
            <h1>RELEVÉ DE CRÉANCE</h1>
            <div class="period">
                Du {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}
                au {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            </div>
            <div class="period">Imprimé le {{ now()->format('d/m/Y à H:i') }}</div>
        </div>
    </div>

    <!-- Bloc revendeur -->
    <div class="client-block">
        <div class="label">Revendeur</div>
        <div class="name">{{ strtoupper($reseller->company_name) }}</div>
        <div class="contact">
            Contact : {{ $reseller->contact_name }}
            &nbsp;|&nbsp; Tél : {{ $reseller->phone }}
            @if($reseller->email) &nbsp;|&nbsp; Email : {{ $reseller->email }} @endif
        </div>
        <div class="contact" style="margin-top:1mm;">
            Plafond crédit : <strong>{{ number_format($reseller->credit_limit, 0, ',', ' ') }} F</strong>
            &nbsp;|&nbsp; Dette actuelle totale : <strong style="color:#dc2626">{{ number_format($reseller->current_debt, 0, ',', ' ') }} F</strong>
        </div>
    </div>

    <!-- KPIs -->
    <div class="kpis">
        <div class="kpi red">
            <div class="kpi-value red">{{ number_format($totalOutstanding, 0, ',', ' ') }} F</div>
            <div class="kpi-label">Restant dû (période)</div>
        </div>
        <div class="kpi">
            <div class="kpi-value">{{ number_format($totalAmount, 0, ',', ' ') }} F</div>
            <div class="kpi-label">Crédit accordé</div>
        </div>
        <div class="kpi green">
            <div class="kpi-value green">{{ number_format($totalPaid, 0, ',', ' ') }} F</div>
            <div class="kpi-label">Déjà payé</div>
        </div>
        <div class="kpi">
            <div class="kpi-value">{{ $sales->count() }}</div>
            <div class="kpi-label">Commande(s)</div>
        </div>
    </div>

    <!-- Commandes à crédit -->
    @if($sales->count() > 0)
    <div class="section-title">Commandes à crédit ({{ $sales->count() }})</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>N° Facture</th>
                <th>Boutique</th>
                <th>Vendeur</th>
                <th>Produit(s)</th>
                <th class="text-right">Montant</th>
                <th class="text-right">Payé</th>
                <th class="text-right">Restant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sales as $sale)
            <tr>
                <td>{{ $sale->created_at->format('d/m/Y') }}</td>
                <td><strong>{{ $sale->invoice_number }}</strong></td>
                <td><span class="shop-badge">{{ $sale->shop->name ?? '—' }}</span></td>
                <td>{{ $sale->user->name ?? '—' }}</td>
                <td class="products-list">
                    @foreach($sale->items as $item)
                        {{ $item->product->name ?? '—' }} ×{{ $item->quantity }}@if(!$loop->last), @endif
                    @endforeach
                </td>
                <td class="text-right">{{ number_format($sale->total_amount, 0, ',', ' ') }} F</td>
                <td class="text-right text-green">{{ number_format($sale->amount_paid, 0, ',', ' ') }} F</td>
                <td class="text-right text-red">{{ number_format($sale->amount_due, 0, ',', ' ') }} F</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-right">Sous-total ventes</td>
                <td class="text-right">{{ number_format($totalAmount, 0, ',', ' ') }} F</td>
                <td class="text-right text-green">{{ number_format($totalPaid, 0, ',', ' ') }} F</td>
                <td class="text-right text-red">{{ number_format($totalOutstanding, 0, ',', ' ') }} F</td>
            </tr>
        </tfoot>
    </table>
    @endif

    <!-- Paiements reçus -->
    @if($payments->count() > 0)
    <div class="section-title">Paiements reçus ({{ $payments->count() }})</div>
    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Facture liée</th>
                <th>Encaissé par</th>
                <th>Mode</th>
                <th class="text-right">Montant</th>
                <th class="text-right">Dette avant</th>
                <th class="text-right">Dette après</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $payment)
            <tr>
                <td>{{ $payment->created_at->format('d/m/Y') }}</td>
                <td>{{ $payment->sale->invoice_number ?? '—' }}</td>
                <td>{{ $payment->user->name ?? '—' }}</td>
                <td>{{ $payment->payment_method ?? 'Espèces' }}</td>
                <td class="text-right text-green"><strong>{{ number_format($payment->amount, 0, ',', ' ') }} F</strong></td>
                <td class="text-right">{{ number_format($payment->debt_before, 0, ',', ' ') }} F</td>
                <td class="text-right text-green">{{ number_format($payment->debt_after, 0, ',', ' ') }} F</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-right">Total paiements reçus</td>
                <td class="text-right text-green">{{ number_format($totalPaymentsAmount, 0, ',', ' ') }} F</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
    @endif

    @if($sales->count() === 0)
        <div class="empty">Aucune créance sur cette période.</div>
    @endif

    <!-- Récapitulatif final -->
    @if($totalOutstanding > 0)
    <div class="summary-box">
        <div>
            <div class="label">TOTAL DÛ PAR {{ strtoupper($reseller->company_name) }}</div>
            <div class="sub">Période : {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div>
            <div class="sub">Dette totale actuelle : {{ number_format($reseller->current_debt, 0, ',', ' ') }} F</div>
        </div>
        <div>
            <div class="amount">{{ number_format($totalOutstanding, 0, ',', ' ') }} F</div>
            <div class="amount-sub">sur {{ number_format($totalAmount, 0, ',', ' ') }} F accordés (période)</div>
        </div>
    </div>
    @endif

    <div class="footer">
        Document généré automatiquement par {{ $companyName }} — {{ now()->format('d/m/Y à H:i') }}
    </div>
</div>
</body>
</html>
