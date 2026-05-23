<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Rapport des Caisses</title>
<style>
@page { margin: 12mm 0 12mm 0; size: A4 landscape; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 8.5px; color: #222; line-height: 1.4; }
.wrap { padding-left: 12mm; padding-right: 12mm; }

/* Header */
.header { display: table; width: 100%; border-bottom: 2px solid #198754; padding-bottom: 8px; margin-bottom: 10px; }
.header-left, .header-right { display: table-cell; vertical-align: middle; }
.header-right { text-align: right; }
.doc-title   { font-size: 15px; font-weight: bold; color: #198754; }
.doc-subtitle { font-size: 8.5px; color: #555; margin-top: 2px; }
.badge { display: inline-block; background: #e8f5e9; border: 1px solid #a5d6a7; border-radius: 3px; padding: 2px 7px; font-size: 8px; color: #1b5e20; margin-bottom: 2px; }

/* KPI bar */
.kpi-bar { display: table; width: 100%; margin-bottom: 10px; border-collapse: separate; border-spacing: 4px 0; }
.kpi { display: table-cell; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 5px 8px; text-align: center; }
.kpi-value { font-size: 12px; font-weight: bold; color: #212529; }
.kpi-label  { font-size: 7px; color: #6c757d; margin-top: 1px; }
.kpi-green  .kpi-value { color: #198754; }
.kpi-red    .kpi-value { color: #dc3545; }
.kpi-blue   .kpi-value { color: #0d6efd; }
.kpi-orange .kpi-value { color: #fd7e14; }

/* Table */
table { width: 100%; border-collapse: collapse; font-size: 8px; }
thead tr { background: #198754; color: #fff; }
thead th { padding: 4px 5px; font-weight: bold; white-space: nowrap; }
thead th.num { text-align: right; }
tbody tr:nth-child(even) { background: #f0faf2; }
tbody tr.row-open { background: #d4edda; }
tbody td { padding: 3px 5px; border-bottom: 1px solid #e0e0e0; }
tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }
tbody td.text-success { color: #198754; font-weight: bold; }
tbody td.text-danger  { color: #dc3545; font-weight: bold; }
tbody td.text-muted   { color: #888; }
tfoot tr { background: #212529; color: #fff; font-weight: bold; }
tfoot td { padding: 4px 5px; }
tfoot td.num { text-align: right; font-variant-numeric: tabular-nums; }

/* Footer */
.footer { margin-top: 8px; border-top: 1px solid #ccc; padding-top: 4px; font-size: 7px; color: #888; display: table; width: 100%; }
.footer-left  { display: table-cell; }
.footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>
<div class="wrap">

<div class="header">
    <div class="header-left">
        <div class="doc-title">Rapport des Caisses</div>
        <div class="doc-subtitle">Historique des ouvertures/fermetures · Soldes · Écarts · Transactions</div>
    </div>
    <div class="header-right">
        @if($dateFrom || $dateTo)
        <div class="badge">{{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '…' }} — {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '…' }}</div><br>
        @else
        <div class="badge">Toutes les dates</div><br>
        @endif
        @if($shop)<div class="badge">Boutique : {{ $shop->name }}</div><br>@else<div class="badge">Toutes boutiques</div><br>@endif
        <div class="badge">{{ $cashRegisters->count() }} caisse(s)</div><br>
        <div class="badge">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-bar">
    <div class="kpi">
        <div class="kpi-value">{{ $cashRegisters->count() }}</div>
        <div class="kpi-label">Caisses</div>
    </div>
    <div class="kpi kpi-green">
        <div class="kpi-value">{{ number_format($totalIncome, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Total entrées</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ number_format($totalExpense, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Total sorties</div>
    </div>
    <div class="kpi kpi-green">
        <div class="kpi-value">{{ number_format($totalPositive, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Excédents cumulés</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ number_format(abs($totalNegative), 0, ',', ' ') }} F</div>
        <div class="kpi-label">Déficits cumulés</div>
    </div>
    <div class="kpi {{ $totalDiff >= 0 ? 'kpi-green' : 'kpi-red' }}">
        <div class="kpi-value">{{ ($totalDiff >= 0 ? '+' : '') . number_format($totalDiff, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Écart net</div>
    </div>
</div>

{{-- Table --}}
<table>
    <thead>
        <tr>
            <th style="width:7%">Date</th>
            <th style="width:12%">Caissière</th>
            @if(!$shop)<th style="width:8%">Boutique</th>@endif
            <th style="width:6%">Ouverture</th>
            <th style="width:6%">Fermeture</th>
            <th class="num" style="width:9%">Solde ouvert. (F)</th>
            <th class="num" style="width:9%">+ Entrées (F)</th>
            <th class="num" style="width:9%">- Sorties (F)</th>
            <th class="num" style="width:9%">Solde attendu (F)</th>
            <th class="num" style="width:9%">Solde déclaré (F)</th>
            <th class="num" style="width:7%">Écart (F)</th>
            <th class="num" style="width:5%">Transac.</th>
            <th style="width:6%">Statut</th>
        </tr>
    </thead>
    <tbody>
    @forelse($cashRegisters as $cr)
    <tr class="{{ $cr->status === 'open' ? 'row-open' : '' }}">
        <td>
            <strong>{{ $cr->date->format('d/m/Y') }}</strong>
            @if($cr->date->isToday()) <em style="font-size:7px;color:#0d6efd">(auj.)</em>@endif
        </td>
        <td>{{ $cr->user?->name ?? 'N/A' }}</td>
        @if(!$shop)<td>{{ $cr->shop?->name ?? '—' }}</td>@endif
        <td>{{ $cr->opened_at?->format('H:i') ?? '—' }}</td>
        <td>{{ $cr->closed_at?->format('H:i') ?? '—' }}</td>
        <td class="num">{{ number_format($cr->opening_balance, 0, ',', ' ') }}</td>
        <td class="num text-success">{{ number_format($cr->total_income, 0, ',', ' ') }}</td>
        <td class="num text-danger">{{ number_format($cr->total_expense, 0, ',', ' ') }}</td>
        <td class="num"><strong>{{ number_format($cr->calculated_balance, 0, ',', ' ') }}</strong></td>
        <td class="num">
            @if($cr->closing_balance !== null)
                {{ number_format($cr->closing_balance, 0, ',', ' ') }}
            @else
                <span class="text-muted">—</span>
            @endif
        </td>
        <td class="num">
            @if($cr->difference !== null)
                @if($cr->difference > 0)
                    <span style="color:#198754;font-weight:bold">+{{ number_format($cr->difference, 0, ',', ' ') }}</span>
                @elseif($cr->difference < 0)
                    <span style="color:#dc3545;font-weight:bold">{{ number_format($cr->difference, 0, ',', ' ') }}</span>
                @else
                    <span style="color:#198754">0</span>
                @endif
            @else
                <span style="color:#888">—</span>
            @endif
        </td>
        <td class="num">{{ $cr->transactions->count() }}</td>
        <td style="font-size:7.5px">
            @if($cr->status === 'open')
                <span style="color:#198754;font-weight:bold">Ouverte</span>
            @else
                <span style="color:#6c757d">Fermée</span>
            @endif
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="{{ $shop ? 13 : 14 }}" style="text-align:center;color:#888;padding:15px">
            Aucune caisse trouvée.
        </td>
    </tr>
    @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="{{ $shop ? 4 : 5 }}">TOTAL ({{ $cashRegisters->count() }} caisses)</td>
            <td class="num">{{ number_format($cashRegisters->sum('opening_balance'), 0, ',', ' ') }}</td>
            <td class="num">{{ number_format($totalIncome, 0, ',', ' ') }}</td>
            <td class="num">{{ number_format($totalExpense, 0, ',', ' ') }}</td>
            <td class="num"></td>
            <td class="num">{{ number_format($cashRegisters->whereNotNull('closing_balance')->sum('closing_balance'), 0, ',', ' ') }}</td>
            <td class="num">{{ ($totalDiff >= 0 ? '+' : '') . number_format($totalDiff, 0, ',', ' ') }}</td>
            <td class="num">{{ $cashRegisters->sum(fn($cr) => $cr->transactions->count()) }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>

@if($totalNegative < 0)
<div style="margin-top:8px;padding:5px 8px;background:#fff3cd;border:1px solid #ffc107;border-radius:3px;font-size:8px;color:#856404">
    <strong>Note écarts :</strong>
    Excédents : +{{ number_format($totalPositive, 0, ',', ' ') }} F —
    Déficits : {{ number_format($totalNegative, 0, ',', ' ') }} F —
    Écart net : {{ ($totalDiff >= 0 ? '+' : '') . number_format($totalDiff, 0, ',', ' ') }} F
</div>
@endif

<div class="footer">
    <div class="footer-left">EGREGORE BUSINESS — Rapport des caisses · Document confidentiel</div>
    <div class="footer-right">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>

</div>
</body>
</html>
