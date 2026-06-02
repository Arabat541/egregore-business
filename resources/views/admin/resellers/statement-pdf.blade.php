<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Relevé de Compte - {{ $reseller->company_name }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 15mm 15mm 20mm 15mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 9px;
            line-height: 1.3;
            color: #333;
            width: 100%;
        }
        .header {
            text-align: center;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h1 {
            font-size: 16px;
            margin-bottom: 3px;
            color: #0d6efd;
        }
        .header h2 {
            font-size: 12px;
            font-weight: normal;
            color: #666;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .info-left, .info-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .info-right {
            text-align: right;
        }
        .info-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            padding: 8px;
            margin-bottom: 8px;
        }
        .info-box h3 {
            font-size: 10px;
            color: #0d6efd;
            margin-bottom: 5px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 3px;
        }
        .info-box p {
            margin-bottom: 2px;
            font-size: 9px;
        }
        .info-box strong {
            display: inline-block;
            width: 80px;
        }
        .summary-cards {
            display: table;
            width: 100%;
            margin-bottom: 15px;
        }
        .summary-card {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 8px;
            border: 1px solid #dee2e6;
        }
        .summary-card.purchases {
            background-color: #cfe2ff;
        }
        .summary-card.payments {
            background-color: #d1e7dd;
        }
        .summary-card.discount {
            background-color: #cff4fc;
        }
        .summary-card.balance {
            background-color: #f8d7da;
        }
        .summary-card.balance.ok {
            background-color: #d1e7dd;
        }
        .summary-card h4 {
            font-size: 8px;
            color: #666;
            margin-bottom: 3px;
        }
        .summary-card .amount {
            font-size: 12px;
            font-weight: bold;
        }
        .summary-card .sub-amount {
            font-size: 7px;
            color: #555;
            margin-top: 2px;
        }
        .current-debt {
            text-align: center;
            padding: 10px;
            margin-bottom: 15px;
            border: 2px solid #dc3545;
            background-color: #fff5f5;
        }
        .current-debt.paid {
            border-color: #198754;
            background-color: #f0fff4;
        }
        .current-debt h3 {
            font-size: 10px;
            margin-bottom: 3px;
        }
        .current-debt .amount {
            font-size: 16px;
            font-weight: bold;
            color: #dc3545;
        }
        .current-debt.paid .amount {
            color: #198754;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
            font-size: 8px;
        }
        table th {
            background-color: #343a40;
            color: white;
            padding: 5px 3px;
            text-align: left;
            font-weight: bold;
            font-size: 8px;
        }
        table th.text-end {
            text-align: right;
        }
        table td {
            padding: 4px 3px;
            border-bottom: 1px solid #dee2e6;
            font-size: 8px;
        }
        table td.text-end {
            text-align: right;
        }
        table tr.opening {
            background-color: #e9ecef;
            font-weight: bold;
        }
        table tr.closing {
            background-color: #343a40;
            color: white;
            font-weight: bold;
        }
        table tr.sale {
            background-color: #fff3cd;
        }
        table tr.payment {
            background-color: #d1e7dd;
        }
        .badge {
            display: inline-block;
            padding: 1px 4px;
            border-radius: 2px;
            font-size: 7px;
            font-weight: bold;
        }
        .badge-warning {
            background-color: #ffc107;
            color: #000;
        }
        .badge-success {
            background-color: #198754;
            color: white;
        }
        .badge-secondary {
            background-color: #6c757d;
            color: white;
        }
        table tr.product-row {
            background-color: #fffde7;
        }
        table tr.product-row td {
            font-size: 7px;
            color: #555;
            padding: 2px 3px;
            border-bottom: none;
        }
        .section-title {
            font-size: 10px;
            font-weight: bold;
            color: #0d6efd;
            border-bottom: 1px solid #0d6efd;
            padding-bottom: 3px;
            margin-bottom: 8px;
            margin-top: 15px;
        }
        .bilan-table {
            width: 60%;
            margin: 0 auto 15px auto;
        }
        .bilan-table td {
            padding: 4px 6px;
            font-size: 9px;
            border-bottom: 1px solid #dee2e6;
        }
        .bilan-table td.text-end {
            text-align: right;
            font-weight: bold;
        }
        .bilan-table tr.total-row td {
            font-size: 11px;
            font-weight: bold;
            border-top: 2px solid #333;
            background-color: #f8f9fa;
        }
        .bilan-table .danger { color: #dc3545; }
        .bilan-table .success { color: #198754; }
        .bilan-table .info { color: #0dcaf0; }
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #dee2e6;
            text-align: center;
            font-size: 7px;
            color: #666;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
        }
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 25px;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            font-size: 8px;
        }
        .signature-box p {
            margin-bottom: 30px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 120px;
            margin: 0 auto;
            padding-top: 3px;
        }
        .page-break {
            page-break-after: always;
        }
        .content {
            margin-bottom: 60px;
        }
    </style>
</head>
<body>
@php
    $itemDiscounts   = $sales->flatMap->items->sum('discount');
    $globalDiscounts = $summary['total_discount'];
    $totalDiscounts  = $itemDiscounts + $globalDiscounts;
    $grossTotal      = $sales->flatMap->items->sum(fn($i) => $i->unit_price * $i->quantity);
@endphp
<div class="content">
    <!-- En-tête -->
    <div class="header">
        <h1>{{ $shopName ?? 'EGREGORE BUSINESS' }}</h1>
        <h2>RELEVÉ DE COMPTE RÉPARATEUR</h2>
    </div>

    <!-- Informations -->
    <div class="info-section">
        <div class="info-left">
            <div class="info-box">
                <h3>INFORMATIONS RÉPARATEUR</h3>
                <p><strong>Société:</strong> {{ $reseller->company_name }}</p>
                <p><strong>Contact:</strong> {{ $reseller->contact_name }}</p>
                <p><strong>Téléphone:</strong> {{ $reseller->phone }}</p>
                <p><strong>Email:</strong> {{ $reseller->email ?? 'Non renseigné' }}</p>
                <p><strong>Adresse:</strong> {{ $reseller->address ?? 'Non renseignée' }}</p>
                @if($reseller->loyalty_tier && $reseller->loyalty_tier !== 'Nouveau')
                <p><strong>Fidélité:</strong> {{ $reseller->loyalty_tier }}
                    @if($reseller->loyalty_bonus_rate > 0)(+{{ $reseller->loyalty_bonus_rate }}%)@endif
                </p>
                @endif
            </div>
        </div>
        <div class="info-right">
            <div class="info-box">
                <h3>INFORMATIONS DOCUMENT</h3>
                <p><strong>Période:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                <p><strong>Boutique:</strong> {{ $shopName ?? 'Toutes boutiques' }}</p>
                <p><strong>Date édition:</strong> {{ now()->format('d/m/Y à H:i') }}</p>
                <p><strong>Plafond crédit:</strong> {{ number_format($reseller->credit_limit, 0, ',', ' ') }} F</p>
            </div>
        </div>
    </div>

    <!-- Créance actuelle -->
    <div class="current-debt {{ $reseller->current_debt <= 0 ? 'paid' : '' }}">
        <h3>CRÉANCE ACTUELLE</h3>
        <div class="amount">{{ number_format($reseller->current_debt, 0, ',', ' ') }} F</div>
    </div>

    <!-- Résumé de la période -->
    <div class="summary-cards">
        <div class="summary-card purchases">
            <h4>TOTAL ACHATS</h4>
            <div class="amount">{{ number_format($summary['total_purchases'], 0, ',', ' ') }} F</div>
            @if($grossTotal > $summary['total_purchases'])
            <div class="sub-amount">Brut : {{ number_format($grossTotal, 0, ',', ' ') }} F</div>
            @endif
        </div>
        <div class="summary-card discount">
            <h4>TOTAL REMISES</h4>
            <div class="amount">{{ number_format($totalDiscounts, 0, ',', ' ') }} F</div>
            @if($totalDiscounts > 0)
            <div class="sub-amount">
                @if($itemDiscounts > 0)Produits : {{ number_format($itemDiscounts, 0, ',', ' ') }} F@endif
                @if($itemDiscounts > 0 && $globalDiscounts > 0) · @endif
                @if($globalDiscounts > 0)Globales : {{ number_format($globalDiscounts, 0, ',', ' ') }} F@endif
            </div>
            @endif
        </div>
        <div class="summary-card payments">
            <h4>TOTAL PAYÉ</h4>
            <div class="amount">{{ number_format($summary['total_payments'], 0, ',', ' ') }} F</div>
            @if($payments->isNotEmpty())
            <div class="sub-amount">{{ $payments->count() }} versement(s)</div>
            @endif
        </div>
        <div class="summary-card balance {{ $summary['balance'] <= 0 ? 'ok' : '' }}">
            <h4>RESTE À PAYER</h4>
            <div class="amount">{{ number_format($summary['balance'], 0, ',', ' ') }} F</div>
            @if($openingBalance > 0)
            <div class="sub-amount">Ouverture : {{ number_format($openingBalance, 0, ',', ' ') }} F</div>
            @endif
        </div>
    </div>

    <!-- Tableau des mouvements -->
    <div class="section-title">MOUVEMENTS DE LA PÉRIODE</div>
    <table>
        <thead>
            <tr>
                <th style="width:10%;">Date</th>
                <th style="width:9%;">Type</th>
                <th style="width:28%;">Référence / Produit</th>
                @if(!isset($shopId) || !$shopId)<th style="width:10%;">Boutique</th>@endif
                <th style="width:6%;" class="text-end">Qté</th>
                <th style="width:12%;" class="text-end">Prix unit.</th>
                <th style="width:11%;" class="text-end">Débit</th>
                <th style="width:11%;" class="text-end">Crédit</th>
                <th style="width:11%;" class="text-end">Créance</th>
            </tr>
        </thead>
        <tbody>
            <!-- Créance d'ouverture -->
            <tr class="opening">
                <td>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</td>
                <td><span class="badge badge-secondary">Ouverture</span></td>
                <td>Créance d'ouverture</td>
                @if(!isset($shopId) || !$shopId)<td></td>@endif
                <td></td><td></td>
                <td class="text-end">—</td>
                <td class="text-end">—</td>
                <td class="text-end">{{ number_format($openingBalance, 0, ',', ' ') }} F</td>
            </tr>

            @forelse($movements as $movement)
                @php
                    $isSale      = $movement['type'] === 'sale';
                    $hasProducts = $isSale && !empty($movement['products']);
                @endphp

                {{-- Ligne principale --}}
                <tr class="{{ $movement['type'] }}" style="font-weight:bold;">
                    <td>{{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}</td>
                    <td>
                        @if($isSale)
                            <span class="badge badge-warning">Achat</span>
                        @else
                            <span class="badge badge-success">Paiement</span>
                        @endif
                    </td>
                    <td>
                        {{ $movement['reference'] }}
                        <span style="font-weight:normal; color:#666;"> — {{ $movement['description'] }}</span>
                    </td>
                    @if(!isset($shopId) || !$shopId)
                    <td style="font-weight:normal;">{{ $movement['shop'] ?? '—' }}</td>
                    @endif
                    <td></td>
                    <td></td>
                    <td class="text-end">
                        @if($movement['debit'] > 0){{ number_format($movement['debit'], 0, ',', ' ') }} F
                        @else —
                        @endif
                    </td>
                    <td class="text-end">
                        @if($movement['credit'] > 0){{ number_format($movement['credit'], 0, ',', ' ') }} F
                        @else —
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($movement['running_balance'], 0, ',', ' ') }} F</td>
                </tr>

                {{-- Sous-lignes produits --}}
                @if($hasProducts)
                    @foreach($movement['products'] as $product)
                    <tr class="product-row">
                        <td></td>
                        <td></td>
                        <td style="padding-left:12px;">
                            └ {{ $product['name'] }}
                            @if(isset($product['discount']) && $product['discount'] > 0)
                                <span style="color:#c47a00;"> (-{{ number_format($product['discount'], 0, ',', ' ') }} F)</span>
                            @endif
                        </td>
                        @if(!isset($shopId) || !$shopId)<td></td>@endif
                        <td class="text-end">{{ $product['quantity'] }}</td>
                        <td class="text-end">{{ number_format($product['unit_price'], 0, ',', ' ') }} F</td>
                        <td class="text-end">{{ number_format($product['total'], 0, ',', ' ') }} F</td>
                        <td></td>
                        <td></td>
                    </tr>
                    @endforeach
                @endif
            @empty
                <tr>
                    <td colspan="{{ (!isset($shopId) || !$shopId) ? 9 : 8 }}" style="text-align:center; padding:20px;">
                        Aucun mouvement sur cette période
                    </td>
                </tr>
            @endforelse

            <!-- Créance de clôture -->
            <tr class="closing">
                <td>{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</td>
                <td>Clôture</td>
                <td>Créance de clôture</td>
                @if(!isset($shopId) || !$shopId)<td></td>@endif
                <td></td><td></td>
                <td class="text-end">{{ number_format($summary['total_purchases'], 0, ',', ' ') }} F</td>
                <td class="text-end">{{ number_format($summary['total_payments'], 0, ',', ' ') }} F</td>
                <td class="text-end">{{ number_format($summary['balance'], 0, ',', ' ') }} F</td>
            </tr>
        </tbody>
    </table>

    @if(isset($payments) && $payments->isNotEmpty())
    <!-- Versements reçus -->
    <div class="section-title">HISTORIQUE DES PAIEMENTS</div>
    <table>
        <thead>
            <tr>
                <th style="width:15%;">Date</th>
                <th style="width:20%;">Référence</th>
                <th style="width:15%;">Mode</th>
                <th style="width:22%;" class="text-end">Montant</th>
                <th>Note</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $p)
            <tr style="{{ (float)($p->debt_before ?? 0) <= 0 ? 'color:#888;background:#f8f9fa;' : 'background:#d1e7dd;' }}">
                <td>{{ $p->created_at->format('d/m/Y') }}</td>
                <td>{{ $p->reference ?? 'PAY-' . str_pad($p->id, 5, '0', STR_PAD_LEFT) }}</td>
                <td>{{ $p->payment_method ?? 'Espèces' }}</td>
                <td class="text-end">{{ number_format($p->amount, 0, ',', ' ') }} F</td>
                <td style="font-size:7px;">{{ (float)($p->debt_before ?? 0) <= 0 ? 'Avance (aucune dette)' : ($p->notes ?? '') }}</td>
            </tr>
            @endforeach
            <tr style="background:#e9ecef;font-weight:bold;">
                <td colspan="3">Total versements reçus</td>
                <td class="text-end">{{ number_format($payments->sum('amount'), 0, ',', ' ') }} F</td>
                <td></td>
            </tr>
        </tbody>
    </table>
    @endif

    <!-- Bilan de la période -->
    <div class="section-title">BILAN DE LA PÉRIODE</div>
    <table class="bilan-table">
        <tbody>
            <tr>
                <td>Dette d'ouverture</td>
                <td class="text-end">{{ number_format($openingBalance, 0, ',', ' ') }} F</td>
            </tr>
            <tr>
                <td class="danger">+ Achats de la période</td>
                <td class="text-end danger">+ {{ number_format($summary['total_purchases'], 0, ',', ' ') }} F</td>
            </tr>
            @if($totalDiscounts > 0)
            <tr>
                <td class="info">Remises obtenues</td>
                <td class="text-end info" style="font-size:7px;">
                    @if($itemDiscounts > 0)Produits : -{{ number_format($itemDiscounts, 0, ',', ' ') }} F · @endif
                    @if($globalDiscounts > 0)Globales : -{{ number_format($globalDiscounts, 0, ',', ' ') }} F · @endif
                    déjà incluses dans les achats
                </td>
            </tr>
            @endif
            <tr>
                <td class="success">- Paiements reçus</td>
                <td class="text-end success">- {{ number_format($summary['total_payments'], 0, ',', ' ') }} F</td>
            </tr>
            <tr class="total-row">
                <td>Solde restant dû</td>
                <td class="text-end {{ $summary['balance'] > 0 ? 'danger' : 'success' }}">
                    {{ number_format($summary['balance'], 0, ',', ' ') }} F
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature-box">
            <p>Signature du Réparateur</p>
            <div class="signature-line">{{ $reseller->contact_name }}</div>
        </div>
        <div class="signature-box">
            <p>Signature de la Boutique</p>
            <div class="signature-line">{{ $shopName ?? 'EGREGORE BUSINESS' }}</div>
        </div>
    </div>
</div>

    <!-- Pied de page -->
    <div class="footer">
        <p>Document généré le {{ now()->format('d/m/Y à H:i') }} - Ce relevé fait foi en cas de litige.</p>
        <p>{{ $shopName ?? 'EGREGORE BUSINESS' }} - {{ $shopAddress ?? '' }} - {{ $shopPhone ?? '' }}</p>
    </div>
</body>
</html>
