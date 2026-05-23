<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Rapport Clients</title>
<style>
@page { margin: 15mm 0 15mm 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #222; line-height: 1.4; }
.wrap { padding-left: 15mm; padding-right: 15mm; }
.header { display: table; width: 100%; border-bottom: 2px solid #0dcaf0; padding-bottom: 8px; margin-bottom: 12px; }
.header-left, .header-right { display: table-cell; vertical-align: middle; }
.header-right { text-align: right; }
.doc-title { font-size: 16px; font-weight: bold; color: #0dcaf0; }
.doc-subtitle { font-size: 9px; color: #555; margin-top: 2px; }
.badge { display: inline-block; background: #cff4fc; border: 1px solid #9eeaf9; border-radius: 3px; padding: 2px 7px; font-size: 8.5px; color: #055160; margin-bottom: 2px; }
.kpi-bar { display: table; width: 100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 4px 0; }
.kpi { display: table-cell; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 6px 8px; text-align: center; }
.kpi-value { font-size: 13px; font-weight: bold; color: #212529; }
.kpi-label { font-size: 7.5px; color: #6c757d; margin-top: 1px; }
.kpi-cyan .kpi-value { color: #0dcaf0; }
.kpi-blue .kpi-value { color: #0d6efd; }
.kpi-red .kpi-value { color: #dc3545; }
.section-title { font-size: 10px; font-weight: bold; color: #0dcaf0; border-bottom: 1px solid #dee2e6; padding-bottom: 3px; margin: 10px 0 6px 0; }
table { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 8px; }
thead tr { background: #0dcaf0; color: #000; }
thead th { padding: 4px 6px; font-weight: bold; }
thead th.num { text-align: right; }
tbody tr:nth-child(even) { background: #f0fbff; }
tbody td { padding: 3px 6px; border-bottom: 1px solid #e8e8e8; }
tbody td.num { text-align: right; }
tfoot tr { background: #212529; color: #fff; font-weight: bold; }
tfoot td { padding: 4px 6px; }
tfoot td.num { text-align: right; }
.cols { display: table; width: 100%; border-spacing: 6px; }
.col { display: table-cell; width: 50%; vertical-align: top; }
.filter-badge { display: inline-block; background: #fff3cd; border: 1px solid #ffc107; border-radius: 3px; padding: 2px 8px; font-size: 8px; color: #856404; margin-bottom: 6px; }
.footer { margin-top: 10px; border-top: 1px solid #ccc; padding-top: 5px; font-size: 7.5px; color: #888; display: table; width: 100%; }
.footer-left { display: table-cell; }
.footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>
<div class="wrap">

<div class="header">
    <div class="header-left">
        <div class="doc-title">Rapport Clients &amp; Réparateurs</div>
        <div class="doc-subtitle">Top clients · Fidélité · Revendeurs &amp; réparateurs</div>
    </div>
    <div class="header-right">
        <div class="badge">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div><br>
        @if($shop)<div class="badge">Boutique : {{ $shop->name }}</div><br>@else<div class="badge">Toutes boutiques</div><br>@endif
        <div class="badge">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>

@if($customerType)
<div style="margin-bottom:8px">
    <span class="filter-badge">Filtre : {{ $customerType === 'reseller' ? 'Réparateurs / Revendeurs uniquement' : 'Clients particuliers uniquement' }}</span>
</div>
@endif

<div class="kpi-bar">
    @if(!$customerType || $customerType === 'customer')
    <div class="kpi kpi-cyan">
        <div class="kpi-value">{{ number_format($totalCustomers, 0, ',', ' ') }}</div>
        <div class="kpi-label">Clients enregistrés</div>
    </div>
    @endif
    @if(!$customerType || $customerType === 'reseller')
    <div class="kpi kpi-blue">
        <div class="kpi-value">{{ number_format($totalResellers, 0, ',', ' ') }}</div>
        <div class="kpi-label">Réparateurs</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ number_format($totalResellerDebt, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Créances réparateurs</div>
    </div>
    @endif
</div>

@if(!$customerType || $customerType === 'customer')
<div class="section-title">Top 20 clients par chiffre d'affaires</div>
<table>
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:42%">Client</th>
            <th class="num" style="width:14%">Achats</th>
            <th class="num" style="width:20%">CA total (F)</th>
            <th class="num" style="width:20%">Moy. par achat (F)</th>
        </tr>
    </thead>
    <tbody>
    @forelse($topCustomersByRevenue->filter(fn($c) => ($c->sales_sum_total_amount ?? 0) > 0)->take(20) as $i => $c)
    <tr>
        <td style="color:#888">{{ $i+1 }}</td>
        <td><strong>{{ $c->full_name }}</strong><br><span style="color:#999;font-size:7.5px">{{ $c->phone }}</span></td>
        <td class="num">{{ $c->sales_count }}</td>
        <td class="num">{{ number_format($c->sales_sum_total_amount ?? 0, 0, ',', ' ') }}</td>
        <td class="num">{{ $c->sales_count > 0 ? number_format(($c->sales_sum_total_amount ?? 0) / $c->sales_count, 0, ',', ' ') : '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="5" style="text-align:center;color:#888;padding:10px">Aucun client avec des achats sur la période</td></tr>
    @endforelse
    </tbody>
</table>
@endif

@if(!$customerType || $customerType === 'reseller')
<div class="section-title">Top réparateurs / revendeurs</div>
<table>
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:38%">Réparateur / Revendeur</th>
            <th class="num" style="width:14%">Commandes</th>
            <th class="num" style="width:20%">CA (F)</th>
            <th class="num" style="width:24%">Crédit en cours (F)</th>
        </tr>
    </thead>
    <tbody>
    @forelse($topResellers->filter(fn($r) => ($r->sales_sum_total_amount ?? 0) > 0) as $i => $r)
    <tr>
        <td style="color:#888">{{ $i+1 }}</td>
        <td><strong>{{ $r->company_name }}</strong><br><span style="color:#999;font-size:7.5px">{{ $r->contact_name }}</span></td>
        <td class="num">{{ $r->sales_count }}</td>
        <td class="num">{{ number_format($r->sales_sum_total_amount ?? 0, 0, ',', ' ') }}</td>
        <td class="num {{ $r->current_debt > 0 ? 'style="color:#dc3545"' : '' }}">{{ number_format($r->current_debt, 0, ',', ' ') }}</td>
    </tr>
    @empty
    <tr><td colspan="5" style="text-align:center;color:#888;padding:10px">Aucun réparateur avec des achats sur la période</td></tr>
    @endforelse
    </tbody>
</table>
@endif

<div class="footer">
    <div class="footer-left">EGREGORE BUSINESS — Rapport confidentiel</div>
    <div class="footer-right">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>
</div>
</body>
</html>
