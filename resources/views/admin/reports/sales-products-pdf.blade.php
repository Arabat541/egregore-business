<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Rapport Produits Vendus</title>
    <style>
        @page {
            margin: 15mm 0 18mm 0;
        }
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.4;
            color: #222;
        }

        /* ── En-tête ── */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 14px;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 10px;
        }
        .header-left, .header-right {
            display: table-cell;
            vertical-align: middle;
        }
        .header-right { text-align: right; }

        .doc-title {
            font-size: 16px;
            font-weight: bold;
            color: #0d6efd;
        }
        .doc-subtitle {
            font-size: 10px;
            color: #555;
            margin-top: 2px;
        }
        .meta-badge {
            display: inline-block;
            background: #e9f0ff;
            border: 1px solid #b8d0ff;
            border-radius: 3px;
            padding: 3px 8px;
            font-size: 9px;
            color: #1a4fad;
            margin-bottom: 3px;
        }

        /* ── KPI bar ── */
        .kpi-bar {
            display: table;
            width: 100%;
            margin-bottom: 12px;
            border-collapse: separate;
            border-spacing: 4px 0;
        }
        .kpi {
            display: table-cell;
            width: 20%;
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 6px 8px;
            text-align: center;
        }
        .kpi-value { font-size: 13px; font-weight: bold; color: #212529; }
        .kpi-label { font-size: 8px; color: #6c757d; margin-top: 1px; }
        .kpi-green  .kpi-value { color: #198754; }
        .kpi-blue   .kpi-value { color: #0d6efd; }
        .kpi-orange .kpi-value { color: #fd7e14; }
        .kpi-red    .kpi-value { color: #dc3545; }

        /* ── Table ── */
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
        }
        thead tr {
            background: #0d6efd;
            color: #fff;
        }
        thead th {
            padding: 5px 6px;
            font-weight: bold;
            white-space: nowrap;
        }
        thead th.num { text-align: right; }

        tbody tr:nth-child(even) { background: #f0f4ff; }
        tbody tr:hover { background: #dce8ff; }

        tbody td {
            padding: 4px 6px;
            border-bottom: 1px solid #e0e0e0;
        }
        tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }

        /* Barre de marge inline */
        .margin-bar-wrap {
            display: table;
            width: 100%;
        }
        .margin-bar-pct {
            display: table-cell;
            text-align: right;
            white-space: nowrap;
            width: 38px;
            font-weight: bold;
        }
        .margin-bar-outer {
            display: table-cell;
            width: 50px;
            padding-left: 4px;
            vertical-align: middle;
        }
        .margin-bar-inner {
            height: 6px;
            border-radius: 2px;
            background: #198754;
        }
        .margin-bar-inner.low  { background: #dc3545; }
        .margin-bar-inner.mid  { background: #fd7e14; }

        /* Ligne de totaux */
        tfoot tr {
            background: #212529;
            color: #fff;
            font-weight: bold;
        }
        tfoot td {
            padding: 5px 6px;
        }
        tfoot td.num {
            text-align: right;
            font-variant-numeric: tabular-nums;
        }

        /* Pied de page */
        .footer {
            margin-top: 10px;
            border-top: 1px solid #ccc;
            padding-top: 5px;
            font-size: 7.5px;
            color: #888;
            display: table;
            width: 100%;
        }
        .footer-left  { display: table-cell; }
        .footer-right { display: table-cell; text-align: right; }

        /* Annulées */
        .section-title-cancelled { font-size: 10px; font-weight: bold; color: #dc3545; border-bottom: 1px solid #dc3545; padding-bottom: 3px; margin: 14px 0 6px 0; }
        tbody tr.cancelled-row { background: #fff5f5 !important; color: #888; }
    </style>
</head>
<body>
<div style="padding-left: 15mm; padding-right: 15mm;">

{{-- ── En-tête ── --}}
<div class="header">
    <div class="header-left">
        <div class="doc-title">Rapport des Ventes par Produit</div>
        <div class="doc-subtitle">Cumul des quantités vendues · CA brut · Remises · CA net · Marge brute</div>
    </div>
    <div class="header-right">
        <div class="meta-badge">
            Période : {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
        </div><br>
        @if($shop)
        <div class="meta-badge">Boutique : {{ $shop->name }}</div><br>
        @else
        <div class="meta-badge">Toutes boutiques</div><br>
        @endif
        <div class="meta-badge">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>

{{-- ── KPIs ── --}}
<div class="kpi-bar">
    <div class="kpi kpi-blue">
        <div class="kpi-value">{{ $rows->count() }}</div>
        <div class="kpi-label">Produits vendus</div>
    </div>
    <div class="kpi">
        <div class="kpi-value">{{ number_format($totals['qty'], 0, ',', ' ') }}</div>
        <div class="kpi-label">Unités vendues</div>
    </div>
    <div class="kpi kpi-blue">
        <div class="kpi-value">{{ number_format($totals['revenue'], 0, ',', ' ') }} F</div>
        <div class="kpi-label">CA brut</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ number_format($totals['discount'], 0, ',', ' ') }} F</div>
        <div class="kpi-label">Remises accordées</div>
    </div>
    <div class="kpi kpi-green">
        <div class="kpi-value">{{ number_format($totals['margin'], 0, ',', ' ') }} F</div>
        <div class="kpi-label">Marge brute ({{ $totals['margin_pct'] }} %)</div>
    </div>
</div>

{{-- ── Tableau ── --}}
<table>
    <thead>
        <tr>
            <th style="width:3%">#</th>
            <th style="width:22%">Produit</th>
            <th style="width:8%">SKU</th>
            <th class="num" style="width:6%">Qté</th>
            <th class="num" style="width:12%">CA brut (F)</th>
            <th class="num" style="width:10%">Remise (F)</th>
            <th class="num" style="width:12%">CA net (F)</th>
            <th class="num" style="width:11%">Coût d'achat</th>
            <th class="num" style="width:10%">Marge (F)</th>
            <th style="width:6%" class="num">% Marge</th>
        </tr>
    </thead>
    <tbody>
        @forelse($rows as $i => $row)
        @php
            $barWidth   = min((int) abs($row->margin_pct), 100);
            $barClass   = $row->margin_pct < 0 ? 'low' : ($row->margin_pct < 15 ? 'mid' : '');
        @endphp
        <tr>
            <td style="color:#888">{{ $i + 1 }}</td>
            <td><strong>{{ $row->product_name }}</strong></td>
            <td style="color:#666">{{ $row->sku ?? '—' }}</td>
            <td class="num">{{ number_format($row->total_qty, 0, ',', ' ') }}</td>
            <td class="num" style="color:#666">{{ number_format($row->total_revenue, 0, ',', ' ') }}</td>
            <td class="num" style="color:#dc3545">
                @if($row->total_discount > 0)
                    −{{ number_format($row->total_discount, 0, ',', ' ') }}
                @else
                    —
                @endif
            </td>
            <td class="num"><strong>{{ number_format($row->net_revenue, 0, ',', ' ') }}</strong></td>
            <td class="num" style="color:#666">{{ number_format($row->total_cost, 0, ',', ' ') }}</td>
            <td class="num" style="color: {{ $row->margin >= 0 ? '#198754' : '#dc3545' }}; font-weight:bold">
                {{ $row->margin >= 0 ? '+' : '' }}{{ number_format($row->margin, 0, ',', ' ') }}
            </td>
            <td>
                <div class="margin-bar-wrap">
                    <div class="margin-bar-pct" style="color: {{ $row->margin_pct < 0 ? '#dc3545' : ($row->margin_pct < 15 ? '#fd7e14' : '#198754') }}">
                        {{ $row->margin_pct }} %
                    </div>
                    <div class="margin-bar-outer">
                        <div class="margin-bar-inner {{ $barClass }}" style="width: {{ $barWidth }}%"></div>
                    </div>
                </div>
            </td>
        </tr>
        @empty
        <tr>
            <td colspan="10" style="text-align:center; color:#888; padding:20px">
                Aucune vente sur cette période.
            </td>
        </tr>
        @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="3"><strong>TOTAL ({{ $rows->count() }} produits)</strong></td>
            <td class="num">{{ number_format($totals['qty'], 0, ',', ' ') }}</td>
            <td class="num">{{ number_format($totals['revenue'], 0, ',', ' ') }}</td>
            <td class="num">−{{ number_format($totals['discount'], 0, ',', ' ') }}</td>
            <td class="num">{{ number_format($totals['net_revenue'], 0, ',', ' ') }}</td>
            <td class="num">{{ number_format($totals['cost'], 0, ',', ' ') }}</td>
            <td class="num">{{ number_format($totals['margin'], 0, ',', ' ') }}</td>
            <td class="num">{{ $totals['margin_pct'] }} %</td>
        </tr>
    </tfoot>
</table>

{{-- ── Ventes annulées ── --}}
@if($cancelledSales->count() > 0)
<div class="section-title-cancelled">
    Ventes annulées ({{ $cancelledSales->count() }}) — exclues des totaux ci-dessus
</div>
<table>
    <thead>
        <tr style="background:#dc3545;">
            <th style="width:12%">N° Facture</th>
            <th style="width:9%">Date</th>
            <th style="width:35%">Articles annulés</th>
            <th class="num" style="width:10%">Qté</th>
            <th class="num" style="width:14%">Montant (F)</th>
            <th style="width:10%">Caissier</th>
        </tr>
    </thead>
    <tbody>
    @foreach($cancelledSales as $sale)
    <tr class="cancelled-row">
        <td><strong>{{ $sale->invoice_number }}</strong></td>
        <td>{{ $sale->created_at->format('d/m/Y') }}</td>
        <td style="font-size:7.5px">
            @foreach($sale->items as $item)
                {{ $item->product->name ?? '?' }} ({{ number_format($item->unit_price, 0, ',', ' ') }} F)@if(!$loop->last), @endif
            @endforeach
        </td>
        <td class="num">{{ $sale->items->sum('quantity') }}</td>
        <td class="num">{{ number_format($sale->total_amount, 0, ',', ' ') }}</td>
        <td>{{ $sale->user->name ?? '—' }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr style="background:#c82333;">
            <td colspan="3">TOTAL ANNULÉ</td>
            <td class="num">{{ number_format($cancelledSales->sum(fn($s) => $s->items->sum('quantity')), 0, ',', ' ') }}</td>
            <td class="num">{{ number_format($cancelledSales->sum('total_amount'), 0, ',', ' ') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@endif

{{-- ── Pied de page ── --}}
<div class="footer">
    <div class="footer-left">
        CA net = CA brut − Remises · Marge = CA net − Coût d'achat · % Marge = Marge / CA net × 100
    </div>
    <div class="footer-right">
        Page 1
    </div>
</div>

</div>{{-- /padding wrapper --}}
</body>
</html>
