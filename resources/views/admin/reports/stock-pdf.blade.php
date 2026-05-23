<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Rapport du Stock</title>
<style>
@page { margin: 15mm 0 15mm 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #222; line-height: 1.4; }
.wrap { padding-left: 15mm; padding-right: 15mm; }
.header { display: table; width: 100%; border-bottom: 2px solid #fd7e14; padding-bottom: 8px; margin-bottom: 12px; }
.header-left, .header-right { display: table-cell; vertical-align: middle; }
.header-right { text-align: right; }
.doc-title { font-size: 16px; font-weight: bold; color: #fd7e14; }
.doc-subtitle { font-size: 9px; color: #555; margin-top: 2px; }
.badge { display: inline-block; background: #fff3cd; border: 1px solid #ffc107; border-radius: 3px; padding: 2px 7px; font-size: 8.5px; color: #856404; margin-bottom: 2px; }
.kpi-bar { display: table; width: 100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 4px 0; }
.kpi { display: table-cell; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 6px 8px; text-align: center; }
.kpi-value { font-size: 13px; font-weight: bold; color: #212529; }
.kpi-label { font-size: 7.5px; color: #6c757d; margin-top: 1px; }
.kpi-orange .kpi-value { color: #fd7e14; }
.kpi-green .kpi-value { color: #198754; }
.kpi-red .kpi-value { color: #dc3545; }
.section-title { font-size: 10px; font-weight: bold; color: #fd7e14; border-bottom: 1px solid #dee2e6; padding-bottom: 3px; margin: 10px 0 6px 0; }
table { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 8px; }
thead tr { background: #fd7e14; color: #fff; }
thead th { padding: 4px 6px; font-weight: bold; }
thead th.num { text-align: right; }
tbody tr:nth-child(even) { background: #fff8f0; }
tbody td { padding: 3px 6px; border-bottom: 1px solid #e8e8e8; }
tbody td.num { text-align: right; }
tbody td.alert { color: #dc3545; font-weight: bold; }
tfoot tr { background: #212529; color: #fff; font-weight: bold; }
tfoot td { padding: 4px 6px; }
tfoot td.num { text-align: right; }
.cols { display: table; width: 100%; border-spacing: 6px; }
.col { display: table-cell; width: 50%; vertical-align: top; }
.footer { margin-top: 10px; border-top: 1px solid #ccc; padding-top: 5px; font-size: 7.5px; color: #888; display: table; width: 100%; }
.footer-left { display: table-cell; }
.footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>
<div class="wrap">

<div class="header">
    <div class="header-left">
        <div class="doc-title">Rapport du Stock</div>
        <div class="doc-subtitle">Valorisation · Produits à commander · Rentabilité</div>
    </div>
    <div class="header-right">
        <div class="badge">Au {{ now()->format('d/m/Y') }}</div><br>
        @if($shop)<div class="badge">Boutique : {{ $shop->name }}</div><br>@else<div class="badge">Toutes boutiques</div><br>@endif
        @if($category)<div class="badge">Catégorie : {{ $category->name }}</div><br>@endif
        <div class="badge">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>

<div class="kpi-bar">
    <div class="kpi kpi-orange">
        <div class="kpi-value">{{ $totalProducts }}</div>
        <div class="kpi-label">Produits</div>
    </div>
    <div class="kpi kpi-orange">
        <div class="kpi-value">{{ number_format($totalStockValue, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Valeur stock (achat)</div>
    </div>
    <div class="kpi kpi-green">
        <div class="kpi-value">{{ number_format($totalSellingValue, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Valeur vente potentielle</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ $outOfStock }}</div>
        <div class="kpi-label">Ruptures</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ $lowStock }}</div>
        <div class="kpi-label">Stock faible</div>
    </div>
</div>

<div class="cols">
<div class="col">
    <div class="section-title">Stock par catégorie</div>
    <table>
        <thead><tr><th>Catégorie</th><th class="num">Produits</th><th class="num">Qtés</th><th class="num">Valeur (F)</th></tr></thead>
        <tbody>
        @foreach($stockByCategory as $row)
        <tr>
            <td>{{ $row->category->name ?? 'Sans catégorie' }}</td>
            <td class="num">{{ $row->count }}</td>
            <td class="num">{{ number_format($row->total_qty, 0, ',', ' ') }}</td>
            <td class="num">{{ number_format($row->total_value, 0, ',', ' ') }}</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL</td>
                <td class="num">{{ $stockByCategory->sum('count') }}</td>
                <td class="num">{{ number_format($stockByCategory->sum('total_qty'), 0, ',', ' ') }}</td>
                <td class="num">{{ number_format($stockByCategory->sum('total_value'), 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<div class="col">
    <div class="section-title">Top 10 produits les plus rentables</div>
    <table>
        <thead><tr><th>Produit</th><th class="num">Stock</th><th class="num">Marge/u (F)</th></tr></thead>
        <tbody>
        @foreach($mostProfitable as $p)
        <tr>
            <td>{{ $p->name }}</td>
            <td class="num">{{ $p->quantity_in_stock }}</td>
            <td class="num" style="color:#198754;font-weight:bold">{{ number_format($p->profit_margin, 0, ',', ' ') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>

<div class="section-title">Produits à commander (stock faible ou épuisé)</div>
@if($productsToOrder->count() > 0)
<table>
    <thead>
        <tr>
            <th style="width:30%">Produit</th>
            <th style="width:15%">Catégorie</th>
            <th class="num" style="width:12%">En stock</th>
            <th class="num" style="width:12%">Seuil alerte</th>
            <th class="num" style="width:15%">Prix achat (F)</th>
            <th class="num" style="width:16%">Prix vente (F)</th>
        </tr>
    </thead>
    <tbody>
    @foreach($productsToOrder as $p)
    <tr>
        <td><strong>{{ $p->name }}</strong><br><span style="color:#999;font-size:7.5px">{{ $p->sku }}</span></td>
        <td>{{ $p->category->name ?? '—' }}</td>
        <td class="num {{ $p->quantity_in_stock == 0 ? 'alert' : '' }}">{{ $p->quantity_in_stock }}</td>
        <td class="num">{{ $p->stock_alert_threshold }}</td>
        <td class="num">{{ number_format($p->purchase_price, 0, ',', ' ') }}</td>
        <td class="num">{{ number_format($p->normal_price, 0, ',', ' ') }}</td>
    </tr>
    @endforeach
    </tbody>
</table>
@else
<p style="color:#888;font-size:8.5px;text-align:center;padding:10px">Aucun produit en rupture ou stock faible.</p>
@endif

<div class="footer">
    <div class="footer-left">EGREGORE BUSINESS — Rapport confidentiel</div>
    <div class="footer-right">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>
</div>
</body>
</html>
