<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Rapport des Ventes</title>
<style>
@page { margin: 15mm 0 15mm 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #222; line-height: 1.4; }
.wrap { padding-left: 15mm; padding-right: 15mm; }

/* Header */
.header { display: table; width: 100%; border-bottom: 2px solid #0d6efd; padding-bottom: 8px; margin-bottom: 12px; }
.header-left, .header-right { display: table-cell; vertical-align: middle; }
.header-right { text-align: right; }
.doc-title { font-size: 16px; font-weight: bold; color: #0d6efd; }
.doc-subtitle { font-size: 9px; color: #555; margin-top: 2px; }
.badge { display: inline-block; background: #e9f0ff; border: 1px solid #b8d0ff; border-radius: 3px; padding: 2px 7px; font-size: 8.5px; color: #1a4fad; margin-bottom: 2px; }

/* KPI bar */
.kpi-bar { display: table; width: 100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 4px 0; }
.kpi { display: table-cell; width: 20%; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 6px 8px; text-align: center; }
.kpi-value { font-size: 13px; font-weight: bold; color: #212529; }
.kpi-label { font-size: 7.5px; color: #6c757d; margin-top: 1px; }
.kpi-blue .kpi-value { color: #0d6efd; }
.kpi-green .kpi-value { color: #198754; }
.kpi-red .kpi-value { color: #dc3545; }

/* Section title */
.section-title { font-size: 10px; font-weight: bold; color: #0d6efd; border-bottom: 1px solid #dee2e6; padding-bottom: 3px; margin: 10px 0 6px 0; }

/* Tables */
table { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 8px; }
thead tr { background: #0d6efd; color: #fff; }
thead th { padding: 4px 6px; font-weight: bold; }
thead th.num { text-align: right; }
tbody tr:nth-child(even) { background: #f0f4ff; }
tbody td { padding: 3px 6px; border-bottom: 1px solid #e8e8e8; }
tbody td.num { text-align: right; }
tfoot tr { background: #212529; color: #fff; font-weight: bold; }
tfoot td { padding: 4px 6px; }
tfoot td.num { text-align: right; }

/* Two-column layout */
.cols { display: table; width: 100%; border-spacing: 6px; }
.col { display: table-cell; width: 50%; vertical-align: top; }

/* Cancelled */
.cancelled-section { margin-top: 12px; }
tbody tr.cancelled-row { background: #fff5f5 !important; color: #888; }
tbody td.st-cancelled { color: #dc3545; font-weight: bold; }

/* Footer */
.footer { margin-top: 10px; border-top: 1px solid #ccc; padding-top: 5px; font-size: 7.5px; color: #888; display: table; width: 100%; }
.footer-left { display: table-cell; }
.footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>
<div class="wrap">

{{-- Header --}}
<div class="header">
    <div class="header-left">
        <div class="doc-title">Rapport des Ventes</div>
        <div class="doc-subtitle">Synthèse des ventes sur la période sélectionnée</div>
    </div>
    <div class="header-right">
        <div class="badge">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div><br>
        @if($shop)
        <div class="badge">Boutique : {{ $shop->name }}</div><br>
        @else
        <div class="badge">Toutes boutiques</div><br>
        @endif
        <div class="badge">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-bar">
    <div class="kpi kpi-blue">
        <div class="kpi-value">{{ number_format($totalSales, 0, ',', ' ') }}</div>
        <div class="kpi-label">Ventes</div>
    </div>
    <div class="kpi kpi-blue">
        <div class="kpi-value">{{ number_format($totalRevenue, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Chiffre d'affaires</div>
    </div>
    <div class="kpi kpi-green">
        <div class="kpi-value">{{ number_format($totalPaid, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Montant encaissé</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ number_format($totalCredit, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Créances</div>
    </div>
    <div class="kpi" style="background:#fff8e1;border-color:#ffe082;">
        <div class="kpi-value" style="color:#e65100;">{{ number_format($totalDiscount, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Remises accordées</div>
    </div>
</div>

{{-- Two columns: payment modes + client types --}}
<div class="cols">
<div class="col">
    <div class="section-title">Ventes par mode de paiement</div>
    <table>
        <thead><tr><th>Mode de paiement</th><th class="num">Ventes</th><th class="num">Montant (F)</th></tr></thead>
        <tbody>
        @foreach($salesByPayment as $row)
        <tr>
            <td>{{ ucfirst($row->payment_method) }}</td>
            <td class="num">{{ $row->count }}</td>
            <td class="num">{{ number_format($row->total, 0, ',', ' ') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="col">
    <div class="section-title">Ventes par type de client</div>
    <table>
        <thead><tr><th>Type</th><th class="num">Ventes</th><th class="num">Montant (F)</th></tr></thead>
        <tbody>
        @foreach($salesByClientType as $row)
        <tr>
            <td>{{ $row->client_type === 'reseller' ? 'Réparateur/Revendeur' : ($row->client_type === 'customer' ? 'Client enregistré' : 'Comptoir') }}</td>
            <td class="num">{{ $row->count }}</td>
            <td class="num">{{ number_format($row->total, 0, ',', ' ') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>

{{-- Top products --}}
<div class="section-title">Top {{ $topProducts->count() }} produits vendus</div>
<table>
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:50%">Produit</th>
            <th class="num" style="width:15%">Qté vendue</th>
            <th class="num" style="width:31%">CA (FCFA)</th>
        </tr>
    </thead>
    <tbody>
    @forelse($topProducts as $i => $row)
    <tr>
        <td style="color:#888">{{ $i+1 }}</td>
        <td><strong>{{ $row->product->name ?? '—' }}</strong></td>
        <td class="num">{{ number_format($row->total_qty, 0, ',', ' ') }}</td>
        <td class="num">{{ number_format($row->total_revenue, 0, ',', ' ') }}</td>
    </tr>
    @empty
    <tr><td colspan="4" style="text-align:center;color:#888;padding:10px">Aucune vente sur la période</td></tr>
    @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="2">TOTAL</td>
            <td class="num">{{ number_format($topProducts->sum('total_qty'), 0, ',', ' ') }}</td>
            <td class="num">{{ number_format($topProducts->sum('total_revenue'), 0, ',', ' ') }}</td>
        </tr>
    </tfoot>
</table>

{{-- Evolution par jour --}}
@if($salesByDay->count() > 0)
<div class="section-title">Évolution quotidienne</div>
<table>
    <thead><tr><th>Date</th><th class="num">Nb ventes</th><th class="num">CA (F)</th></tr></thead>
    <tbody>
    @foreach($salesByDay as $day)
    <tr>
        <td>{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</td>
        <td class="num">{{ $day->count }}</td>
        <td class="num">{{ number_format($day->total, 0, ',', ' ') }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>TOTAL</td>
            <td class="num">{{ number_format($salesByDay->sum('count'), 0, ',', ' ') }}</td>
            <td class="num">{{ number_format($salesByDay->sum('total'), 0, ',', ' ') }}</td>
        </tr>
    </tfoot>
</table>
@endif

{{-- Ventes annulées --}}
@if($cancelledSales->count() > 0)
<div class="cancelled-section">
    <div class="section-title" style="color:#dc3545;border-color:#dc3545;">
        Ventes annulées ({{ $cancelledSales->count() }}) — exclues des totaux ci-dessus
    </div>
    <table>
        <thead>
            <tr style="background:#dc3545;">
                <th style="width:12%">N° Facture</th>
                <th style="width:9%">Date</th>
                <th style="width:18%">Client</th>
                <th style="width:7%">Type</th>
                <th style="width:22%">Articles</th>
                <th class="num" style="width:10%">Montant (F)</th>
                <th style="width:10%">Mode</th>
                <th style="width:12%">Caissier</th>
            </tr>
        </thead>
        <tbody>
        @foreach($cancelledSales as $sale)
        <tr class="cancelled-row">
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
            <td class="num">{{ number_format($sale->total_amount, 0, ',', ' ') }}</td>
            <td>{{ ucfirst($sale->payment_method ?? '—') }}</td>
            <td>{{ $sale->user->name ?? '—' }}</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr style="background:#c82333;">
                <td colspan="5">TOTAL ANNULÉ</td>
                <td class="num">{{ number_format($cancelledSales->sum('total_amount'), 0, ',', ' ') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>
</div>
@endif

<div class="footer">
    <div class="footer-left">EGREGORE BUSINESS — Rapport confidentiel</div>
    <div class="footer-right">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>

</div>
</body>
</html>
