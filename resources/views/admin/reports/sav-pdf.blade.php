<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Rapport S.A.V</title>
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
.filter-badge { display: inline-block; background: #fff3cd; border: 1px solid #ffc107; border-radius: 3px; padding: 2px 8px; font-size: 8px; color: #856404; margin-right: 4px; margin-bottom: 6px; }
.kpi-bar { display: table; width: 100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 4px 0; }
.kpi { display: table-cell; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 6px 8px; text-align: center; }
.kpi-value { font-size: 13px; font-weight: bold; color: #212529; }
.kpi-label { font-size: 7.5px; color: #6c757d; margin-top: 1px; }
.kpi-red .kpi-value { color: #dc3545; }
.kpi-orange .kpi-value { color: #fd7e14; }
.kpi-blue .kpi-value { color: #0d6efd; }
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
.alert-tag { display: inline-block; background: #dc3545; color: #fff; border-radius: 2px; padding: 1px 4px; font-size: 7px; margin-right: 2px; }
.footer { margin-top: 10px; border-top: 1px solid #ccc; padding-top: 5px; font-size: 7.5px; color: #888; display: table; width: 100%; }
.footer-left { display: table-cell; }
.footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>
<div class="wrap">

<div class="header">
    <div class="header-left">
        <div class="doc-title">Rapport S.A.V</div>
        <div class="doc-subtitle">Service Après-Vente · Retours · Remboursements · Échanges · Audit</div>
    </div>
    <div class="header-right">
        <div class="badge">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div><br>
        @if($shop)<div class="badge">Boutique : {{ $shop->name }}</div><br>@else<div class="badge">Toutes boutiques</div><br>@endif
        <div class="badge">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>

@if($savType || $customerType)
<div style="margin-bottom:8px">
    @if($savType)<span class="filter-badge">Type SAV : {{ ucfirst($savType) }}</span>@endif
    @if($customerType)<span class="filter-badge">Type client : {{ $customerType === 'reseller' ? 'Réparateurs' : 'Clients particuliers' }}</span>@endif
</div>
@endif

<div class="kpi-bar">
    <div class="kpi kpi-blue">
        <div class="kpi-value">{{ $totalTickets }}</div>
        <div class="kpi-label">Total tickets</div>
    </div>
    <div class="kpi kpi-orange">
        <div class="kpi-value">{{ $openTickets }}</div>
        <div class="kpi-label">Ouverts</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ number_format($totalRefunds, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Remboursements</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ number_format($totalExchangeLosses, 0, ',', ' ') }} F</div>
        <div class="kpi-label">Pertes échanges</div>
    </div>
</div>

<div class="cols">
<div class="col">
    <div class="section-title">Tickets par type</div>
    <table>
        <thead><tr><th>Type</th><th class="num">Nb</th><th class="num">Remboursé (F)</th></tr></thead>
        <tbody>
        @foreach($ticketsByType as $row)
        @php
        $typeLabels = [
            'return' => 'Retour', 'exchange' => 'Échange', 'refund' => 'Remboursement',
            'warranty' => 'Garantie', 'repair_warranty' => 'Garantie répar.',
            'complaint' => 'Réclamation', 'other' => 'Autre'
        ];
        @endphp
        <tr>
            <td>{{ $typeLabels[$row->type] ?? ucfirst($row->type) }}</td>
            <td class="num">{{ $row->count }}</td>
            <td class="num">{{ number_format($row->total_refunds, 0, ',', ' ') }}</td>
        </tr>
        @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td>TOTAL</td>
                <td class="num">{{ $ticketsByType->sum('count') }}</td>
                <td class="num">{{ number_format($ticketsByType->sum('total_refunds'), 0, ',', ' ') }}</td>
            </tr>
        </tfoot>
    </table>
</div>
<div class="col">
    <div class="section-title">Tickets par statut</div>
    <table>
        <thead><tr><th>Statut</th><th class="num">Nb</th></tr></thead>
        <tbody>
        @foreach($ticketsByStatus as $row)
        @php
        $statusLabels = [
            'open' => 'Ouvert', 'in_progress' => 'En cours', 'waiting_customer' => 'Attente client',
            'waiting_parts' => 'Attente pièces', 'resolved' => 'Résolu', 'closed' => 'Fermé', 'rejected' => 'Rejeté'
        ];
        @endphp
        <tr><td>{{ $statusLabels[$row->status] ?? ucfirst($row->status) }}</td><td class="num">{{ $row->count }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <div class="section-title" style="margin-top:6px">Par créateur (risque fraude)</div>
    <table>
        <thead><tr><th>Employé</th><th class="num">Tickets</th><th class="num">Remboursé (F)</th></tr></thead>
        <tbody>
        @foreach($ticketsByCreator as $row)
        <tr>
            <td>{{ $row->creator->name ?? '—' }}</td>
            <td class="num">{{ $row->total_tickets }}</td>
            <td class="num {{ $row->total_refunds > 50000 ? 'style=color:#dc3545;font-weight:bold' : '' }}">
                {{ number_format($row->total_refunds, 0, ',', ' ') }}
            </td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>

<div class="section-title">Détail des remboursements et retours ({{ $recentRefunds->count() }} opérations)</div>
@if($recentRefunds->count() > 0)
<table>
    <thead>
        <tr>
            <th style="width:10%">N° Ticket</th>
            <th style="width:8%">Date</th>
            <th style="width:10%">Type</th>
            <th style="width:20%">Client</th>
            <th style="width:14%">Vente liée</th>
            <th style="width:14%">Créé par</th>
            <th class="num" style="width:12%">Remboursé (F)</th>
            <th style="width:12%">Résolution</th>
        </tr>
    </thead>
    <tbody>
    @foreach($recentRefunds as $ticket)
    <tr>
        <td><strong>{{ $ticket->ticket_number }}</strong></td>
        <td>{{ $ticket->created_at->format('d/m/Y') }}</td>
        <td>{{ $typeLabels[$ticket->type] ?? $ticket->type }}</td>
        <td>{{ $ticket->customer->full_name ?? 'Anonyme' }}</td>
        <td>{{ $ticket->sale->invoice_number ?? '—' }}</td>
        <td>{{ $ticket->creator->name ?? '—' }}</td>
        <td class="num" style="font-weight:bold;color:#dc3545">{{ number_format($ticket->refund_amount ?? 0, 0, ',', ' ') }}</td>
        <td>{{ $ticket->resolved_at ? $ticket->resolved_at->format('d/m/Y') : '—' }}</td>
    </tr>
    @endforeach
    </tbody>
    <tfoot>
        <tr>
            <td colspan="6">TOTAL remboursements</td>
            <td class="num">{{ number_format($recentRefunds->sum('refund_amount'), 0, ',', ' ') }}</td>
            <td></td>
        </tr>
    </tfoot>
</table>
@else
<p style="color:#888;font-size:8.5px;text-align:center;padding:10px">Aucun remboursement sur la période.</p>
@endif

<div class="footer">
    <div class="footer-left">EGREGORE BUSINESS — Rapport S.A.V confidentiel · Usage interne uniquement</div>
    <div class="footer-right">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>
</div>
</body>
</html>
