<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Rapport Financier</title>
<style>
@page { margin: 15mm 0 15mm 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #222; line-height: 1.4; }
.wrap { padding-left: 15mm; padding-right: 15mm; }
.header { display: table; width: 100%; border-bottom: 2px solid #6f42c1; padding-bottom: 8px; margin-bottom: 12px; }
.header-left, .header-right { display: table-cell; vertical-align: middle; }
.header-right { text-align: right; }
.doc-title { font-size: 16px; font-weight: bold; color: #6f42c1; }
.doc-subtitle { font-size: 9px; color: #555; margin-top: 2px; }
.badge { display: inline-block; background: #f3e8ff; border: 1px solid #d0adff; border-radius: 3px; padding: 2px 7px; font-size: 8.5px; color: #5a20a0; margin-bottom: 2px; }
.kpi-bar { display: table; width: 100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 4px 0; }
.kpi { display: table-cell; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 6px 8px; text-align: center; }
.kpi-value { font-size: 12px; font-weight: bold; color: #212529; }
.kpi-label { font-size: 7.5px; color: #6c757d; margin-top: 1px; }
.kpi-purple .kpi-value { color: #6f42c1; }
.kpi-green .kpi-value { color: #198754; }
.kpi-red .kpi-value { color: #dc3545; }
.kpi-blue .kpi-value { color: #0d6efd; }
.section-title { font-size: 10px; font-weight: bold; color: #6f42c1; border-bottom: 1px solid #dee2e6; padding-bottom: 3px; margin: 10px 0 6px 0; }
table { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 8px; }
thead tr { background: #6f42c1; color: #fff; }
thead th { padding: 4px 6px; font-weight: bold; }
thead th.num { text-align: right; }
tbody tr:nth-child(even) { background: #f8f0ff; }
tbody td { padding: 3px 6px; border-bottom: 1px solid #e8e8e8; }
tbody td.num { text-align: right; }
tfoot tr { background: #212529; color: #fff; font-weight: bold; }
tfoot td { padding: 4px 6px; }
tfoot td.num { text-align: right; }
.cols { display: table; width: 100%; border-spacing: 6px; }
.col { display: table-cell; width: 50%; vertical-align: top; }
.summary-row { display: table; width: 100%; border-collapse: collapse; margin-bottom: 6px; }
.summary-cell { display: table-cell; padding: 4px 8px; border: 1px solid #dee2e6; font-size: 9px; }
.summary-cell.label { width: 60%; background: #f8f9fa; }
.summary-cell.value { width: 40%; text-align: right; font-weight: bold; }
.footer { margin-top: 10px; border-top: 1px solid #ccc; padding-top: 5px; font-size: 7.5px; color: #888; display: table; width: 100%; }
.footer-left { display: table-cell; }
.footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>
<div class="wrap">

<div class="header">
    <div class="header-left">
        <div class="doc-title">Rapport Financier</div>
        <div class="doc-subtitle">Revenus · Dépenses · Marge brute · Impact S.A.V</div>
    </div>
    <div class="header-right">
        <div class="badge">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div><br>
        @if($shop)<div class="badge">Boutique : {{ $shop->name }}</div><br>@else<div class="badge">Toutes boutiques</div><br>@endif
        <div class="badge">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>

<div class="kpi-bar">
    <div class="kpi kpi-blue">
        <div class="kpi-value">{{ number_format($salesRevenue, 0, ',', ' ') }} F</div>
        <div class="kpi-label">CA Ventes</div>
    </div>
    <div class="kpi kpi-green">
        <div class="kpi-value">{{ number_format($repairsRevenue, 0, ',', ' ') }} F</div>
        <div class="kpi-label">CA Réparations</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ number_format($savRefunds + $savExchangeLosses, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Impact S.A.V</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ number_format($totalExpenses, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Dépenses</div>
    </div>
    <div class="kpi {{ $finalNetProfit >= 0 ? 'kpi-green' : 'kpi-red' }}">
        <div class="kpi-value">{{ number_format($finalNetProfit, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Bénéfice net</div>
    </div>
</div>

<div class="cols">
<div class="col">
    <div class="section-title">Synthèse des revenus</div>
    <table>
        <thead><tr><th>Poste</th><th class="num">Montant (F)</th></tr></thead>
        <tbody>
            <tr><td>CA Ventes encaissées</td><td class="num" style="color:#0d6efd">+{{ number_format($salesRevenue, 0, ',', ' ') }}</td></tr>
            <tr><td>CA Réparations (MO)</td><td class="num" style="color:#198754">+{{ number_format($repairsRevenue, 0, ',', ' ') }}</td></tr>
            <tr><td>Gains échanges S.A.V</td><td class="num" style="color:#198754">+{{ number_format($savExchangeGains, 0, ',', ' ') }}</td></tr>
            <tr><td>Remboursements S.A.V</td><td class="num" style="color:#dc3545">-{{ number_format($savRefunds, 0, ',', ' ') }}</td></tr>
            <tr><td>Pertes échanges S.A.V</td><td class="num" style="color:#dc3545">-{{ number_format($savExchangeLosses, 0, ',', ' ') }}</td></tr>
        </tbody>
        <tfoot>
            <tr><td>Revenu net</td><td class="num">{{ number_format($netRevenue, 0, ',', ' ') }}</td></tr>
        </tfoot>
    </table>

    <div class="section-title">Marge brute estimée</div>
    <table>
        <thead><tr><th>Poste</th><th class="num">Montant (F)</th></tr></thead>
        <tbody>
            <tr><td>CA Ventes</td><td class="num">{{ number_format($salesRevenue, 0, ',', ' ') }}</td></tr>
            <tr><td>Coût d'achat vendu</td><td class="num" style="color:#dc3545">-{{ number_format($costOfGoodsSold, 0, ',', ' ') }}</td></tr>
            <tr><td>Marge brute ventes</td><td class="num" style="color:#198754">{{ number_format($grossProfit, 0, ',', ' ') }}</td></tr>
            <tr><td>% Marge</td><td class="num">{{ $profitMargin }} %</td></tr>
        </tbody>
        <tfoot>
            <tr><td>Bénéfice net final</td><td class="num">{{ number_format($finalNetProfit, 0, ',', ' ') }}</td></tr>
        </tfoot>
    </table>
</div>
<div class="col">
    <div class="section-title">Ventes par mode de paiement</div>
    <table>
        <thead><tr><th>Mode</th><th class="num">CA encaissé (F)</th></tr></thead>
        <tbody>
        @foreach($revenueByPayment as $row)
        <tr><td>{{ ucfirst($row->payment_method) }}</td><td class="num">{{ number_format($row->total, 0, ',', ' ') }}</td></tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr><td>TOTAL</td><td class="num">{{ number_format($revenueByPayment->sum('total'), 0, ',', ' ') }}</td></tr>
        </tfoot>
    </table>

    <div class="section-title">Dépenses par catégorie</div>
    <table>
        <thead><tr><th>Catégorie</th><th class="num">Montant (F)</th></tr></thead>
        <tbody>
        @forelse($expensesByCategory as $row)
        <tr><td>{{ $row->category->name ?? 'Sans catégorie' }}</td><td class="num">{{ number_format($row->total, 0, ',', ' ') }}</td></tr>
        @empty
        <tr><td colspan="2" style="text-align:center;color:#888">Aucune dépense</td></tr>
        @endforelse
        </tbody>
        <tfoot>
            <tr><td>TOTAL dépenses</td><td class="num">{{ number_format($totalExpenses, 0, ',', ' ') }}</td></tr>
        </tfoot>
    </table>
</div>
</div>

<div class="footer">
    <div class="footer-left">EGREGORE BUSINESS — Rapport financier confidentiel</div>
    <div class="footer-right">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>
</div>
</body>
</html>
