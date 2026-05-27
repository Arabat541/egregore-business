<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Historique des Ventes</title>
<style>
@page { margin: 12mm 0 12mm 0; size: A4 landscape; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5px; color: #222; line-height: 1.4; }
.wrap { padding-left: 12mm; padding-right: 12mm; }
.header { display: table; width: 100%; border-bottom: 2px solid #0d6efd; padding-bottom: 8px; margin-bottom: 10px; }
.header-left, .header-right { display: table-cell; vertical-align: middle; }
.header-right { text-align: right; }
.doc-title { font-size: 15px; font-weight: bold; color: #0d6efd; }
.doc-subtitle { font-size: 8.5px; color: #555; margin-top: 2px; }
.badge { display: inline-block; background: #e9f0ff; border: 1px solid #b8d0ff; border-radius: 3px; padding: 2px 7px; font-size: 8px; color: #1a4fad; margin-bottom: 2px; }
.kpi-bar { display: table; width: 100%; margin-bottom: 10px; border-collapse: separate; border-spacing: 4px 0; }
.kpi { display: table-cell; width: 20%; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 5px 8px; text-align: center; }
.kpi-value { font-size: 12px; font-weight: bold; color: #212529; }
.kpi-label { font-size: 7px; color: #6c757d; margin-top: 1px; }
.kpi-blue .kpi-value { color: #0d6efd; }
.kpi-green .kpi-value { color: #198754; }
.kpi-red .kpi-value { color: #dc3545; }
.kpi-orange .kpi-value { color: #fd7e14; }
table { width: 100%; border-collapse: collapse; font-size: 8px; }
thead tr { background: #0d6efd; color: #fff; }
thead th { padding: 4px 5px; font-weight: bold; }
thead th.num { text-align: right; }
tbody tr:nth-child(even) { background: #f0f4ff; }
tbody tr.cancelled { background: #fff5f5; color: #888; }
tbody td { padding: 3px 5px; border-bottom: 1px solid #e8e8e8; }
tbody td.num { text-align: right; }
tfoot tr { background: #212529; color: #fff; font-weight: bold; }
tfoot td { padding: 4px 5px; }
tfoot td.num { text-align: right; }
.st-paid { color: #198754; font-weight: bold; }
.st-credit { color: #e67e00; font-weight: bold; }
.st-cancelled { color: #dc3545; font-weight: bold; }
.footer { margin-top: 8px; border-top: 1px solid #ccc; padding-top: 4px; font-size: 7px; color: #888; display: table; width: 100%; }
.footer-left { display: table-cell; }
.footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>
<div class="wrap">

<div class="header">
    <div class="header-left">
        <div class="doc-title">Historique des Ventes</div>
        <div class="doc-subtitle">Liste des ventes enregistrées · EGREGORE BUSINESS</div>
    </div>
    <div class="header-right">
        @if($dateFrom || $dateTo)
        <div class="badge">{{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '…' }} — {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '…' }}</div><br>
        @else
        <div class="badge">Toutes les dates</div><br>
        @endif
        <div class="badge">{{ $sales->where('payment_status','!=','cancelled')->count() }} vente(s) active(s){{ $sales->where('payment_status','cancelled')->count() > 0 ? ' · ' . $sales->where('payment_status','cancelled')->count() . ' annulée(s)' : '' }}</div><br>
        <div class="badge">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>

<div class="kpi-bar">
    <div class="kpi kpi-blue">
        <div class="kpi-value">{{ $sales->where('payment_status','!=','cancelled')->count() }}</div>
        <div class="kpi-label">Ventes actives</div>
    </div>
    <div class="kpi kpi-blue">
        <div class="kpi-value">{{ number_format($totalRevenue, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Chiffre d'affaires</div>
    </div>
    <div class="kpi kpi-green">
        <div class="kpi-value">{{ number_format($totalPaid, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Encaissé</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ number_format($totalCredit, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Créances</div>
    </div>
    <div class="kpi kpi-orange">
        <div class="kpi-value">{{ number_format($totalDiscount, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Remises accordées</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:9%">N° Facture</th>
            <th style="width:7%">Date</th>
            <th style="width:14%">Client</th>
            <th style="width:7%">Type</th>
            <th style="width:19%">Articles</th>
            <th class="num" style="width:8%">Brut (F)</th>
            <th class="num" style="width:7%">Remise (F)</th>
            <th class="num" style="width:8%">Total (F)</th>
            <th class="num" style="width:7%">Payé (F)</th>
            <th style="width:7%">Mode</th>
            <th style="width:7%">Statut</th>
            <th style="width:6%">Caissier</th>
        </tr>
    </thead>
    <tbody>
    @forelse($sales as $sale)
    @php
        $isCancelled = $sale->payment_status === 'cancelled';
        $grossAmount = $sale->subtotal ?? ($sale->total_amount + ($sale->discount_amount ?? 0));
        $discount    = (float) ($sale->discount_amount ?? 0);
    @endphp
    <tr class="{{ $isCancelled ? 'cancelled' : '' }}">
        <td><strong>{{ $sale->invoice_number }}</strong></td>
        <td>{{ $sale->created_at->format('d/m/Y') }}</td>
        <td>
            @if($sale->client_type === 'customer' && $sale->customer)
                {{ $sale->customer->full_name }}
            @elseif($sale->client_type === 'reseller' && $sale->reseller)
                {{ $sale->reseller->company_name }}
            @else
                Comptoir
            @endif
        </td>
        <td>{{ $sale->client_type === 'reseller' ? 'Réparateur' : ($sale->client_type === 'customer' ? 'Client' : 'Comptoir') }}</td>
        <td style="font-size:7.5px">
            @foreach($sale->items->take(3) as $item)
                {{ $item->product->name ?? '?' }} x{{ $item->quantity }}@if(!$loop->last), @endif
            @endforeach
            @if($sale->items->count() > 3) <em>+{{ $sale->items->count() - 3 }} autre(s)</em>@endif
        </td>
        <td class="num">{{ number_format($grossAmount, 0, ',', ' ') }}</td>
        <td class="num">{{ $discount > 0 ? number_format($discount, 0, ',', ' ') : '—' }}</td>
        <td class="num">{{ number_format($sale->total_amount, 0, ',', ' ') }}</td>
        <td class="num">{{ number_format($sale->amount_paid, 0, ',', ' ') }}</td>
        <td>{{ ucfirst($sale->payment_method ?? '—') }}</td>
        <td class="{{ $isCancelled ? 'st-cancelled' : ($sale->payment_status === 'paid' ? 'st-paid' : 'st-credit') }}">
            @if($isCancelled) Annulé
            @elseif($sale->payment_status === 'paid') Payé
            @else Crédit
            @endif
        </td>
        <td>{{ $sale->user->name ?? '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="12" style="text-align:center;color:#888;padding:12px">Aucune vente trouvée.</td></tr>
    @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5">TOTAL — {{ $sales->where('payment_status','!=','cancelled')->count() }} ventes actives</td>
            <td class="num">{{ number_format($totalRevenue + $totalDiscount, 0, ',', ' ') }}</td>
            <td class="num">{{ number_format($totalDiscount, 0, ',', ' ') }}</td>
            <td class="num">{{ number_format($totalRevenue, 0, ',', ' ') }}</td>
            <td class="num">{{ number_format($totalPaid, 0, ',', ' ') }}</td>
            <td colspan="3"></td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    <div class="footer-left">EGREGORE BUSINESS — Historique des ventes · Document confidentiel</div>
    <div class="footer-right">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>
</div>
</body>
</html>
