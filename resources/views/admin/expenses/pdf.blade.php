<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Rapport des Dépenses</title>
<style>
@page { margin: 15mm 0 15mm 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #222; line-height: 1.4; }
.wrap { padding-left: 15mm; padding-right: 15mm; }
.header { display: table; width: 100%; border-bottom: 2px solid #dc3545; padding-bottom: 8px; margin-bottom: 12px; }
.header-left, .header-right { display: table-cell; vertical-align: middle; }
.header-right { text-align: right; }
.doc-title { font-size: 16px; font-weight: bold; color: #dc3545; }
.doc-subtitle { font-size: 9px; color: #555; margin-top: 2px; }
.badge { display: inline-block; background: #fce8e8; border: 1px solid #f5c6cb; border-radius: 3px; padding: 2px 7px; font-size: 8.5px; color: #721c24; margin-bottom: 2px; }
.kpi-bar { display: table; width: 100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 4px 0; }
.kpi { display: table-cell; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 6px 8px; text-align: center; }
.kpi-value { font-size: 13px; font-weight: bold; color: #212529; }
.kpi-label { font-size: 7.5px; color: #6c757d; margin-top: 1px; }
.kpi-red .kpi-value { color: #dc3545; }
.kpi-orange .kpi-value { color: #fd7e14; }
.kpi-green .kpi-value { color: #198754; }
.section-title { font-size: 10px; font-weight: bold; color: #dc3545; border-bottom: 1px solid #dee2e6; padding-bottom: 3px; margin: 10px 0 6px 0; }
table { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 8px; }
thead tr { background: #dc3545; color: #fff; }
thead th { padding: 4px 6px; font-weight: bold; }
thead th.num { text-align: right; }
tbody tr:nth-child(even) { background: #fff5f5; }
tbody td { padding: 3px 6px; border-bottom: 1px solid #e8e8e8; }
tbody td.num { text-align: right; }
tfoot tr { background: #212529; color: #fff; font-weight: bold; }
tfoot td { padding: 4px 6px; }
tfoot td.num { text-align: right; }
.cols { display: table; width: 100%; border-spacing: 6px; }
.col { display: table-cell; width: 50%; vertical-align: top; }
.status-approved { color: #198754; font-weight: bold; }
.status-pending { color: #fd7e14; font-weight: bold; }
.status-rejected { color: #dc3545; }
.footer { margin-top: 10px; border-top: 1px solid #ccc; padding-top: 5px; font-size: 7.5px; color: #888; display: table; width: 100%; }
.footer-left { display: table-cell; }
.footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>
<div class="wrap">

<div class="header">
    <div class="header-left">
        <div class="doc-title">Rapport des Dépenses</div>
        <div class="doc-subtitle">Liste et synthèse des dépenses enregistrées</div>
    </div>
    <div class="header-right">
        @if($dateFrom || $dateTo)
        <div class="badge">{{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '…' }} — {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '…' }}</div><br>
        @else
        <div class="badge">Toutes les dates</div><br>
        @endif
        @if($shop)<div class="badge">Boutique : {{ $shop->name }}</div><br>@else<div class="badge">Toutes boutiques</div><br>@endif
        <div class="badge">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>

<div class="kpi-bar">
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ $expenses->count() }}</div>
        <div class="kpi-label">Dépenses totales</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ number_format($totalApproved, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Montant approuvé</div>
    </div>
    <div class="kpi kpi-orange">
        <div class="kpi-value">{{ number_format($totalPending, 0, ',', ' ') }} F</div>
        <div class="kpi-label">En attente</div>
    </div>
    <div class="kpi">
        <div class="kpi-value">{{ number_format($totalAll, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Total</div>
    </div>
</div>

<div class="cols">
<div class="col">
    <div class="section-title">Par catégorie (approuvées)</div>
    <table>
        <thead><tr><th>Catégorie</th><th class="num">Nb</th><th class="num">Montant (F)</th></tr></thead>
        <tbody>
        @foreach($byCategory as $cat => $data)
        <tr>
            <td>{{ $cat }}</td>
            <td class="num">{{ $data['count'] }}</td>
            <td class="num">{{ number_format($data['total'], 0, ',', ' ') }}</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL</td>
                <td class="num">{{ $byCategory->sum('count') }}</td>
                <td class="num">{{ number_format($byCategory->sum('total'), 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<div class="col" style="vertical-align:top;padding-left:4px">
    <div style="background:#fff3cd;border:1px solid #ffc107;border-radius:4px;padding:8px;font-size:8.5px">
        <strong>Résumé</strong><br>
        Dépenses approuvées : <strong>{{ number_format($totalApproved, 0, ',', ' ') }} F</strong><br>
        Dépenses en attente : <strong>{{ number_format($totalPending, 0, ',', ' ') }} F</strong><br>
        Total général : <strong>{{ number_format($totalAll, 0, ',', ' ') }} F</strong>
    </div>
</div>
</div>

<div class="section-title">Détail des dépenses ({{ $expenses->count() }} entrées)</div>
<table>
    <thead>
        <tr>
            <th style="width:8%">Date</th>
            <th style="width:10%">Réf.</th>
            <th style="width:22%">Description</th>
            <th style="width:14%">Catégorie</th>
            <th style="width:14%">Bénéficiaire</th>
            <th style="width:10%">Mode paiement</th>
            <th class="num" style="width:12%">Montant (F)</th>
            <th style="width:10%">Statut</th>
        </tr>
    </thead>
    <tbody>
    @forelse($expenses as $expense)
    <tr>
        <td>{{ \Carbon\Carbon::parse($expense->expense_date)->format('d/m/Y') }}</td>
        <td>{{ $expense->reference ?? '—' }}</td>
        <td>{{ \Illuminate\Support\Str::limit($expense->description, 35) }}</td>
        <td>{{ $expense->category->name ?? '—' }}</td>
        <td>{{ \Illuminate\Support\Str::limit($expense->beneficiary ?? '—', 18) }}</td>
        <td>{{ ucfirst($expense->payment_method ?? '—') }}</td>
        <td class="num">{{ number_format($expense->amount, 0, ',', ' ') }}</td>
        <td class="{{ $expense->status === 'approved' ? 'status-approved' : ($expense->status === 'pending' ? 'status-pending' : 'status-rejected') }}">
            {{ $expense->status === 'approved' ? 'Approuvé' : ($expense->status === 'pending' ? 'En attente' : ucfirst($expense->status)) }}
        </td>
    </tr>
    @empty
    <tr><td colspan="8" style="text-align:center;color:#888;padding:10px">Aucune dépense trouvée.</td></tr>
    @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6">TOTAL ({{ $expenses->count() }} dépenses)</td>
            <td class="num">{{ number_format($totalAll, 0, ',', ' ') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    <div class="footer-left">EGREGORE BUSINESS — Rapport des dépenses · Document confidentiel</div>
    <div class="footer-right">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>
</div>
</body>
</html>
