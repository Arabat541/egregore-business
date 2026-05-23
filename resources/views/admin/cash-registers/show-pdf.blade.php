<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Détail Caisse — {{ $cashRegister->date->format('d/m/Y') }}</title>
<style>
@page { margin: 12mm 0 12mm 0; size: A4 portrait; }
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
.badge-closed { background: #f1f3f5; border-color: #adb5bd; color: #495057; }
.badge-open   { background: #d4edda; border-color: #28a745; color: #155724; }

/* Two-column layout */
.two-col { display: table; width: 100%; border-spacing: 6px 0; border-collapse: separate; margin-bottom: 10px; }
.col-left, .col-right { display: table-cell; vertical-align: top; }
.col-left  { width: 48%; }
.col-right { width: 48%; }

/* Info block */
.info-block { border: 1px solid #dee2e6; border-radius: 3px; margin-bottom: 8px; }
.info-block-title { background: #198754; color: #fff; font-size: 9px; font-weight: bold; padding: 4px 8px; }
.info-row { display: table; width: 100%; border-bottom: 1px solid #f0f0f0; }
.info-row:last-child { border-bottom: none; }
.info-label { display: table-cell; padding: 4px 8px; color: #555; width: 45%; }
.info-value { display: table-cell; padding: 4px 8px; font-weight: bold; text-align: right; }
.info-value.green  { color: #198754; }
.info-value.red    { color: #dc3545; }
.info-value.blue   { color: #0d6efd; }
.info-value.muted  { color: #888; font-weight: normal; }

/* Totals row */
.totals-block { background: #f8f9fa; border: 2px solid #198754; border-radius: 3px; padding: 6px 10px; margin-bottom: 10px; display: table; width: 100%; }
.total-cell { display: table-cell; text-align: center; }
.total-cell-value { font-size: 12px; font-weight: bold; }
.total-cell-label { font-size: 7.5px; color: #6c757d; margin-top: 1px; }
.separator { display: table-cell; width: 10px; text-align: center; font-size: 14px; color: #aaa; vertical-align: middle; }

/* Category summary */
table.cat-table { width: 100%; border-collapse: collapse; font-size: 8px; margin-bottom: 10px; }
table.cat-table thead tr { background: #0d6efd; color: #fff; }
table.cat-table thead th { padding: 3px 5px; }
table.cat-table tbody tr:nth-child(even) { background: #f0f4ff; }
table.cat-table tbody td { padding: 3px 5px; border-bottom: 1px solid #e0e0e0; }
table.cat-table tfoot tr { background: #343a40; color: #fff; font-weight: bold; }
table.cat-table tfoot td { padding: 3px 5px; }

/* Transactions table */
table.tx-table { width: 100%; border-collapse: collapse; font-size: 8px; }
table.tx-table thead tr { background: #198754; color: #fff; }
table.tx-table thead th { padding: 4px 5px; font-weight: bold; white-space: nowrap; }
table.tx-table thead th.num { text-align: right; }
table.tx-table tbody tr:nth-child(even) { background: #f0faf2; }
table.tx-table tbody tr.income-row { }
table.tx-table tbody tr.expense-row { }
table.tx-table tbody td { padding: 3px 5px; border-bottom: 1px solid #e0e0e0; }
table.tx-table tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }
table.tx-table tfoot tr { background: #212529; color: #fff; font-weight: bold; }
table.tx-table tfoot td { padding: 4px 5px; }
table.tx-table tfoot td.num { text-align: right; }
.text-success { color: #198754; font-weight: bold; }
.text-danger  { color: #dc3545; font-weight: bold; }
.text-muted   { color: #888; }
.section-title { font-size: 10px; font-weight: bold; color: #198754; border-bottom: 1px solid #198754; padding-bottom: 3px; margin-bottom: 6px; margin-top: 10px; }

/* Écart boxes */
.ecart-ok  { background: #d4edda; border: 1px solid #28a745; color: #155724; padding: 4px 8px; border-radius: 3px; }
.ecart-bad { background: #f8d7da; border: 1px solid #dc3545; color: #721c24; padding: 4px 8px; border-radius: 3px; }
.ecart-neutral { background: #f8f9fa; border: 1px solid #dee2e6; color: #495057; padding: 4px 8px; border-radius: 3px; }

/* Notes */
.notes-box { background: #fffbea; border: 1px solid #ffc107; border-radius: 3px; padding: 5px 8px; margin-bottom: 8px; font-size: 8px; }

/* Footer */
.footer { margin-top: 10px; border-top: 1px solid #ccc; padding-top: 4px; font-size: 7px; color: #888; display: table; width: 100%; }
.footer-left  { display: table-cell; }
.footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>
<div class="wrap">

{{-- Header --}}
<div class="header">
    <div class="header-left">
        <div class="doc-title">Détail de Caisse — {{ $cashRegister->date->format('d/m/Y') }}</div>
        <div class="doc-subtitle">
            {{ $cashRegister->user?->name ?? 'N/A' }}
            @if($cashRegister->shop) · {{ $cashRegister->shop->name }}@endif
            · {{ $transactions->count() }} transaction(s)
        </div>
    </div>
    <div class="header-right">
        <div class="badge {{ $cashRegister->status === 'open' ? 'badge-open' : 'badge-closed' }}">
            {{ $cashRegister->status === 'open' ? 'Ouverte' : 'Fermée' }}
        </div><br>
        @if($cashRegister->opened_at)
        <div class="badge">Ouv. {{ $cashRegister->opened_at->format('H:i') }}@if($cashRegister->closed_at) · Ferm. {{ $cashRegister->closed_at->format('H:i') }}@endif</div><br>
        @endif
        <div class="badge">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>

{{-- Totals summary bar --}}
<div class="totals-block">
    <div class="total-cell">
        <div class="total-cell-value">{{ number_format($cashRegister->opening_balance, 0, ',', ' ') }} F</div>
        <div class="total-cell-label">Fond d'ouverture</div>
    </div>
    <div class="separator">+</div>
    <div class="total-cell">
        <div class="total-cell-value" style="color:#198754">{{ number_format($cashRegister->total_income, 0, ',', ' ') }} F</div>
        <div class="total-cell-label">Entrées</div>
    </div>
    <div class="separator">−</div>
    <div class="total-cell">
        <div class="total-cell-value" style="color:#dc3545">{{ number_format($cashRegister->total_expense, 0, ',', ' ') }} F</div>
        <div class="total-cell-label">Sorties</div>
    </div>
    <div class="separator">=</div>
    <div class="total-cell">
        <div class="total-cell-value" style="color:#0d6efd">{{ number_format($cashRegister->calculated_balance, 0, ',', ' ') }} F</div>
        <div class="total-cell-label">Solde attendu</div>
    </div>
    @if($cashRegister->closing_balance !== null)
    <div class="separator">≠</div>
    <div class="total-cell">
        <div class="total-cell-value">{{ number_format($cashRegister->closing_balance, 0, ',', ' ') }} F</div>
        <div class="total-cell-label">Solde déclaré</div>
    </div>
    <div class="separator">→</div>
    <div class="total-cell">
        @if($cashRegister->difference > 0)
        <div class="total-cell-value" style="color:#198754">+{{ number_format($cashRegister->difference, 0, ',', ' ') }} F</div>
        @elseif($cashRegister->difference < 0)
        <div class="total-cell-value" style="color:#dc3545">{{ number_format($cashRegister->difference, 0, ',', ' ') }} F</div>
        @else
        <div class="total-cell-value" style="color:#198754">0 F</div>
        @endif
        <div class="total-cell-label">Écart</div>
    </div>
    @endif
</div>

{{-- Notes --}}
@if($cashRegister->opening_notes || $cashRegister->closing_notes)
<div class="notes-box">
    @if($cashRegister->opening_notes)
        <strong>Note d'ouverture :</strong> {{ $cashRegister->opening_notes }}<br>
    @endif
    @if($cashRegister->closing_notes)
        <strong>Note de fermeture :</strong> {{ $cashRegister->closing_notes }}
    @endif
</div>
@endif

{{-- Par catégorie --}}
@if($byCategory->count() > 0)
<div class="section-title">Résumé par catégorie</div>
<table class="cat-table">
    <thead>
        <tr>
            <th>Catégorie</th>
            <th style="text-align:right">Nb transac.</th>
            <th style="text-align:right">Entrées (F)</th>
            <th style="text-align:right">Sorties (F)</th>
            <th style="text-align:right">Net (F)</th>
        </tr>
    </thead>
    <tbody>
    @foreach($byCategory as $cat => $data)
    <tr>
        <td>
            @switch($cat)
                @case('sale')           Ventes @break
                @case('repair')         Réparations @break
                @case('reseller_payment') Paiements revendeur @break
                @case('expense')        Dépenses @break
                @case('cash_in')        Entrée caisse @break
                @case('cash_out')       Sortie caisse @break
                @default {{ $cat }}
            @endswitch
        </td>
        <td style="text-align:right">{{ $data['count'] }}</td>
        <td style="text-align:right; color:#198754">{{ number_format($data['income'], 0, ',', ' ') }}</td>
        <td style="text-align:right; color:#dc3545">{{ number_format($data['expense'], 0, ',', ' ') }}</td>
        <td style="text-align:right; font-weight:bold; color:{{ ($data['income'] - $data['expense']) >= 0 ? '#198754' : '#dc3545' }}">
            {{ ($data['income'] - $data['expense']) >= 0 ? '+' : '' }}{{ number_format($data['income'] - $data['expense'], 0, ',', ' ') }}
        </td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td>TOTAL</td>
            <td style="text-align:right">{{ $transactions->count() }}</td>
            <td style="text-align:right">{{ number_format($byCategory->sum('income'), 0, ',', ' ') }}</td>
            <td style="text-align:right">{{ number_format($byCategory->sum('expense'), 0, ',', ' ') }}</td>
            <td style="text-align:right">{{ number_format($byCategory->sum('income') - $byCategory->sum('expense'), 0, ',', ' ') }}</td>
        </tr>
    </tfoot>
</table>
@endif

{{-- Transactions list --}}
<div class="section-title">Détail des transactions ({{ $transactions->count() }})</div>
<table class="tx-table">
    <thead>
        <tr>
            <th style="width:7%">Heure</th>
            <th style="width:10%">Type</th>
            <th style="width:16%">Catégorie</th>
            <th style="width:35%">Description</th>
            <th style="width:12%">Mode</th>
            <th class="num" style="width:20%">Montant (F)</th>
        </tr>
    </thead>
    <tbody>
    @forelse($transactions as $tx)
    <tr class="{{ $tx->amount > 0 ? 'income-row' : 'expense-row' }}">
        <td>{{ $tx->created_at->format('H:i:s') }}</td>
        <td>
            @if($tx->amount > 0)
                <span style="color:#198754;font-weight:bold">Entrée</span>
            @else
                <span style="color:#dc3545;font-weight:bold">Sortie</span>
            @endif
        </td>
        <td>
            @switch($tx->category)
                @case('sale')             Vente @break
                @case('repair')           Réparation @break
                @case('reseller_payment') Paiement revendeur @break
                @case('expense')          Dépense @break
                @case('cash_in')          Entrée caisse @break
                @case('cash_out')         Sortie caisse @break
                @default {{ $tx->category }}
            @endswitch
        </td>
        <td>{{ $tx->description ?? '—' }}</td>
        <td>{{ $tx->payment_method ?? '—' }}</td>
        <td class="num">
            @if($tx->amount > 0)
                <span class="text-success">+{{ number_format($tx->amount, 0, ',', ' ') }}</span>
            @else
                <span class="text-danger">{{ number_format($tx->amount, 0, ',', ' ') }}</span>
            @endif
        </td>
    </tr>
    @empty
    <tr>
        <td colspan="6" style="text-align:center;color:#888;padding:12px">Aucune transaction enregistrée.</td>
    </tr>
    @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="5">SOLDE NET ({{ $transactions->count() }} transactions)</td>
            <td class="num">
                @php $net = $transactions->sum('amount'); @endphp
                {{ ($net >= 0 ? '+' : '') . number_format($net, 0, ',', ' ') }} F
            </td>
        </tr>
    </tfoot>
</table>

@if($cashRegister->closing_balance !== null && $cashRegister->difference < 0)
<div style="margin-top:8px;padding:5px 8px;background:#f8d7da;border:1px solid #dc3545;border-radius:3px;font-size:8px;color:#721c24">
    <strong>Écart négatif :</strong>
    Solde attendu {{ number_format($cashRegister->calculated_balance, 0, ',', ' ') }} F —
    Solde déclaré {{ number_format($cashRegister->closing_balance, 0, ',', ' ') }} F —
    Déficit de {{ number_format(abs($cashRegister->difference), 0, ',', ' ') }} F.
    Veuillez vérifier les transactions.
</div>
@endif

<div class="footer">
    <div class="footer-left">EGREGORE BUSINESS — Détail caisse {{ $cashRegister->date->format('d/m/Y') }} · Document confidentiel</div>
    <div class="footer-right">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>

</div>
</body>
</html>
