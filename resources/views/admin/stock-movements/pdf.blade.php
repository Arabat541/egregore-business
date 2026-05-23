<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<title>Journal des Mouvements de Stock</title>
<style>
@page { margin: 10mm 0 10mm 0; size: A4 landscape; }
* { margin: 0; padding: 0; box-sizing: border-box; }
body { font-family: 'DejaVu Sans', sans-serif; font-size: 8px; color: #222; line-height: 1.4; }
.wrap { padding-left: 10mm; padding-right: 10mm; }

.header { display: table; width: 100%; border-bottom: 2px solid #0d6efd; padding-bottom: 8px; margin-bottom: 10px; }
.header-left, .header-right { display: table-cell; vertical-align: middle; }
.header-right { text-align: right; }
.doc-title   { font-size: 14px; font-weight: bold; color: #0d6efd; }
.doc-subtitle { font-size: 8px; color: #555; margin-top: 2px; }
.badge { display: inline-block; background: #e7f1ff; border: 1px solid #90b9f8; border-radius: 3px; padding: 2px 6px; font-size: 7.5px; color: #0d47a1; margin-bottom: 2px; }

.kpi-bar { display: table; width: 100%; margin-bottom: 10px; border-collapse: separate; border-spacing: 4px 0; }
.kpi { display: table-cell; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 3px; padding: 5px 8px; text-align: center; }
.kpi-value { font-size: 11px; font-weight: bold; }
.kpi-label  { font-size: 7px; color: #6c757d; margin-top: 1px; }
.kpi-green .kpi-value { color: #198754; }
.kpi-red   .kpi-value { color: #dc3545; }
.kpi-blue  .kpi-value { color: #0d6efd; }

table { width: 100%; border-collapse: collapse; font-size: 7.5px; }
thead tr { background: #0d6efd; color: #fff; }
thead th { padding: 3px 4px; font-weight: bold; white-space: nowrap; }
thead th.num { text-align: right; }
tbody tr:nth-child(even) { background: #f0f4ff; }
tbody td { padding: 2.5px 4px; border-bottom: 1px solid #e0e0e0; }
tbody td.num { text-align: right; font-variant-numeric: tabular-nums; }
tfoot tr { background: #212529; color: #fff; font-weight: bold; }
tfoot td { padding: 3px 4px; }
tfoot td.num { text-align: right; }

.badge-in  { display: inline-block; background: #d4edda; color: #155724; border-radius: 2px; padding: 1px 4px; font-size: 7px; }
.badge-out { display: inline-block; background: #f8d7da; color: #721c24; border-radius: 2px; padding: 1px 4px; font-size: 7px; }
.qty-in  { color: #198754; font-weight: bold; }
.qty-out { color: #dc3545; font-weight: bold; }

.footer { margin-top: 8px; border-top: 1px solid #ccc; padding-top: 4px; font-size: 7px; color: #888; display: table; width: 100%; }
.footer-left  { display: table-cell; }
.footer-right { display: table-cell; text-align: right; }
</style>
</head>
<body>
<div class="wrap">

<div class="header">
    <div class="header-left">
        <div class="doc-title">Journal des Mouvements de Stock</div>
        <div class="doc-subtitle">Historique complet · Entrées / Sorties / Ajustements · Traçabilité</div>
    </div>
    <div class="header-right">
        @if($dateFrom || $dateTo)
        <div class="badge">{{ $dateFrom ? \Carbon\Carbon::parse($dateFrom)->format('d/m/Y') : '…' }} — {{ $dateTo ? \Carbon\Carbon::parse($dateTo)->format('d/m/Y') : '…' }}</div><br>
        @else
        <div class="badge">Toutes les dates</div><br>
        @endif
        @if($shop)<div class="badge">Boutique : {{ $shop->name }}</div><br>@else<div class="badge">Toutes boutiques</div><br>@endif
        @if($typeLabel)<div class="badge">Type : {{ $typeLabel }}</div><br>@endif
        <div class="badge">{{ $movements->count() }} mouvement(s)</div><br>
        <div class="badge">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
    </div>
</div>

{{-- KPIs --}}
<div class="kpi-bar">
    <div class="kpi">
        <div class="kpi-value kpi-blue">{{ $movements->count() }}</div>
        <div class="kpi-label">Total mouvements</div>
    </div>
    <div class="kpi kpi-green">
        <div class="kpi-value">+{{ number_format($totalIn, 0, ',', ' ') }}</div>
        <div class="kpi-label">Total entrées (unités)</div>
    </div>
    <div class="kpi kpi-red">
        <div class="kpi-value">{{ number_format($totalOut, 0, ',', ' ') }}</div>
        <div class="kpi-label">Total sorties (unités)</div>
    </div>
    <div class="kpi">
        <div class="kpi-value {{ ($totalIn + $totalOut) >= 0 ? '' : 'kpi-red' }}">{{ number_format($totalIn + $totalOut, 0, ',', ' ') }}</div>
        <div class="kpi-label">Net (entrées − sorties)</div>
    </div>
    <div class="kpi">
        <div class="kpi-value">{{ $movements->where('quantity', '>', 0)->count() }}</div>
        <div class="kpi-label">Mouvements entrants</div>
    </div>
    <div class="kpi">
        <div class="kpi-value">{{ $movements->where('quantity', '<', 0)->count() }}</div>
        <div class="kpi-label">Mouvements sortants</div>
    </div>
</div>

{{-- Table --}}
<table>
    <thead>
        <tr>
            <th style="width:7%">Date</th>
            <th style="width:6%">Heure</th>
            @if(!$shop)<th style="width:9%">Boutique</th>@endif
            <th style="width:18%">Produit</th>
            <th style="width:8%">SKU</th>
            <th style="width:10%">Type</th>
            <th class="num" style="width:6%">Qté</th>
            <th class="num" style="width:6%">Avant</th>
            <th class="num" style="width:6%">Après</th>
            <th style="width:10%">Référence</th>
            <th style="width:9%">Opérateur</th>
            <th style="width:{{ $shop ? '15%' : '5%' }}">Notes</th>
        </tr>
    </thead>
    <tbody>
    @forelse($movements as $mv)
    <tr>
        <td>{{ $mv->created_at->format('d/m/Y') }}</td>
        <td style="color:#888">{{ $mv->created_at->format('H:i') }}</td>
        @if(!$shop)<td>{{ $mv->shop?->name ?? '—' }}</td>@endif
        <td>
            <strong>{{ $mv->product?->name ?? '—' }}</strong>
        </td>
        <td style="color:#888;font-size:7px">{{ $mv->product?->sku ?? '—' }}</td>
        <td>
            @if($mv->quantity > 0)
                <span class="badge-in">{{ $movementTypes[$mv->type] ?? $mv->type }}</span>
            @else
                <span class="badge-out">{{ $movementTypes[$mv->type] ?? $mv->type }}</span>
            @endif
        </td>
        <td class="num">
            @if($mv->quantity > 0)
                <span class="qty-in">+{{ $mv->quantity }}</span>
            @else
                <span class="qty-out">{{ $mv->quantity }}</span>
            @endif
        </td>
        <td class="num" style="color:#888">{{ $mv->quantity_before ?? '—' }}</td>
        <td class="num"><strong>{{ $mv->quantity_after ?? '—' }}</strong></td>
        <td style="font-size:7px;color:#555">{{ $mv->reference ?? '—' }}</td>
        <td style="font-size:7px">{{ $mv->user?->name ?? '—' }}</td>
        <td style="font-size:7px;color:#666">{{ \Illuminate\Support\Str::limit($mv->reason ?? '', 40) }}</td>
    </tr>
    @empty
    <tr>
        <td colspan="{{ $shop ? 11 : 12 }}" style="text-align:center;color:#888;padding:15px">
            Aucun mouvement trouvé.
        </td>
    </tr>
    @endforelse
    </tbody>
    <tfoot>
        <tr>
            <td colspan="{{ $shop ? 4 : 5 }}">TOTAL ({{ $movements->count() }})</td>
            <td></td>
            <td class="num">+{{ number_format($totalIn, 0, ',', ' ') }} / {{ number_format($totalOut, 0, ',', ' ') }}</td>
            <td colspan="{{ $shop ? 5 : 5 }}"></td>
        </tr>
    </tfoot>
</table>

<div class="footer">
    <div class="footer-left">EGREGORE BUSINESS — Journal des mouvements de stock · Document confidentiel</div>
    <div class="footer-right">Généré le {{ now()->format('d/m/Y à H:i') }}</div>
</div>

</div>
</body>
</html>
