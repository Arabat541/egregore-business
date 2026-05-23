<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Rapport des Réparations</title>
<style>
@page { margin: 15mm 0 15mm 0; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #222; line-height: 1.4; }
.wrap { padding-left: 15mm; padding-right: 15mm; }
.header { display: table; width: 100%; border-bottom: 2px solid #198754; padding-bottom: 8px; margin-bottom: 12px; }
.header-left, .header-right { display: table-cell; vertical-align: middle; }
.header-right { text-align: right; }
.doc-title { font-size: 16px; font-weight: bold; color: #198754; }
.doc-subtitle { font-size: 9px; color: #555; margin-top: 2px; }
.badge { display: inline-block; background: #e8f5e9; border: 1px solid #a8d5b0; border-radius: 3px; padding: 2px 7px; font-size: 8.5px; color: #155724; margin-bottom: 2px; }
.kpi-bar { display: table; width: 100%; margin-bottom: 12px; border-collapse: separate; border-spacing: 4px 0; }
.kpi { display: table-cell; width: 20%; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px; padding: 6px 8px; text-align: center; }
.kpi-value { font-size: 13px; font-weight: bold; color: #212529; }
.kpi-label { font-size: 7.5px; color: #6c757d; margin-top: 1px; }
.kpi-green .kpi-value { color: #198754; }
.kpi-blue .kpi-value { color: #0d6efd; }
.section-title { font-size: 10px; font-weight: bold; color: #198754; border-bottom: 1px solid #dee2e6; padding-bottom: 3px; margin: 10px 0 6px 0; }
table { width: 100%; border-collapse: collapse; font-size: 8.5px; margin-bottom: 8px; }
thead tr { background: #198754; color: #fff; }
thead th { padding: 4px 6px; font-weight: bold; }
thead th.num { text-align: right; }
tbody tr:nth-child(even) { background: #f0faf2; }
tbody td { padding: 3px 6px; border-bottom: 1px solid #e8e8e8; }
tbody td.num { text-align: right; }
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
        <div class="doc-title">Rapport des Réparations</div>
        <div class="doc-subtitle">Synthèse des réparations · Performance techniciens · Appareils</div>
    </div>
    <div class="header-right">
        <div class="badge">{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</div><br>
        @if($shop)<div class="badge">Boutique : {{ $shop->name }}</div><br>@else<div class="badge">Toutes boutiques</div><br>@endif
        <div class="badge">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>

<div class="kpi-bar">
    <div class="kpi kpi-blue">
        <div class="kpi-value">{{ $totalRepairs }}</div>
        <div class="kpi-label">Réparations</div>
    </div>
    <div class="kpi kpi-blue">
        <div class="kpi-value">{{ number_format($totalRevenue, 0, ',', ' ') }} F</div>
        <div class="kpi-label">CA main-d'œuvre</div>
    </div>
    <div class="kpi kpi-green">
        <div class="kpi-value">{{ $successRate }} %</div>
        <div class="kpi-label">Taux de succès</div>
    </div>
    <div class="kpi">
        <div class="kpi-value">{{ $deliveredCount }}</div>
        <div class="kpi-label">Livrées</div>
    </div>
    <div class="kpi">
        <div class="kpi-value">{{ $avgRepairTime ? round($avgRepairTime, 1).'h' : '—' }}</div>
        <div class="kpi-label">Délai moyen</div>
    </div>
</div>

<div class="cols">
<div class="col">
    <div class="section-title">Par statut</div>
    <table>
        <thead><tr><th>Statut</th><th class="num">Nb</th></tr></thead>
        <tbody>
        @foreach($repairsByStatus as $row)
        <tr><td>{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td><td class="num">{{ $row->count }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="col">
    <div class="section-title">Par type d'appareil</div>
    <table>
        <thead><tr><th>Type</th><th class="num">Nb</th><th class="num">CA (F)</th></tr></thead>
        <tbody>
        @foreach($repairsByDevice as $row)
        <tr>
            <td>{{ $row->device_type ?? '—' }}</td>
            <td class="num">{{ $row->count }}</td>
            <td class="num">{{ number_format($row->total, 0, ',', ' ') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>

<div class="section-title">Performance des techniciens</div>
<table>
    <thead><tr><th>Technicien</th><th class="num">Total</th><th class="num">Complétées</th><th class="num">CA (F)</th></tr></thead>
    <tbody>
    @forelse($techPerformance as $tech)
    <tr>
        <td>{{ $tech->technician->name ?? '—' }}</td>
        <td class="num">{{ $tech->total_repairs }}</td>
        <td class="num">{{ $tech->completed }}</td>
        <td class="num">{{ number_format($tech->total_revenue, 0, ',', ' ') }}</td>
    </tr>
    @empty
    <tr><td colspan="4" style="text-align:center;color:#888">Aucun technicien</td></tr>
    @endforelse
    </tbody>
</table>

<div class="cols">
<div class="col">
    <div class="section-title">Top marques</div>
    <table>
        <thead><tr><th>Marque</th><th class="num">Nb</th></tr></thead>
        <tbody>
        @foreach($repairsByBrand as $row)
        <tr><td>{{ $row->device_brand }}</td><td class="num">{{ $row->count }}</td></tr>
        @endforeach
        </tbody>
    </table>
</div>
<div class="col">
    <div class="section-title">Évolution quotidienne</div>
    <table>
        <thead><tr><th>Date</th><th class="num">Nb</th><th class="num">CA (F)</th></tr></thead>
        <tbody>
        @foreach($repairsByDay as $day)
        <tr>
            <td>{{ \Carbon\Carbon::parse($day->date)->format('d/m/Y') }}</td>
            <td class="num">{{ $day->count }}</td>
            <td class="num">{{ number_format($day->total, 0, ',', ' ') }}</td>
        </tr>
        @endforeach
        </tbody>
    </table>
</div>
</div>

<div class="footer">
    <div class="footer-left">EGREGORE BUSINESS — Rapport confidentiel</div>
    <div class="footer-right">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>
</div>
</body>
</html>
