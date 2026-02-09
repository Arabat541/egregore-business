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
        .summary-card h4 {
            font-size: 8px;
            color: #666;
            margin-bottom: 3px;
        }
        .summary-card .amount {
            font-size: 12px;
            font-weight: bold;
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
        .products-list {
            font-size: 7px;
            color: #666;
            margin-top: 2px;
            padding-left: 8px;
        }
        .products-list li {
            margin-bottom: 1px;
        }
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
<div class="content">
    <!-- En-tête -->
    <div class="header">
        <h1>{{ $shopName ?? 'EGREGORE BUSINESS' }}</h1>
        <h2>RELEVÉ DE COMPTE REVENDEUR</h2>
    </div>

    <!-- Informations -->
    <div class="info-section">
        <div class="info-left">
            <div class="info-box">
                <h3>INFORMATIONS REVENDEUR</h3>
                <p><strong>Société:</strong> {{ $reseller->company_name }}</p>
                <p><strong>Contact:</strong> {{ $reseller->contact_name }}</p>
                <p><strong>Téléphone:</strong> {{ $reseller->phone }}</p>
                <p><strong>Email:</strong> {{ $reseller->email ?? 'Non renseigné' }}</p>
                <p><strong>Adresse:</strong> {{ $reseller->address ?? 'Non renseignée' }}</p>
            </div>
        </div>
        <div class="info-right">
            <div class="info-box">
                <h3>INFORMATIONS DOCUMENT</h3>
                <p><strong>Période:</strong> {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} - {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</p>
                <p><strong>Date édition:</strong> {{ now()->format('d/m/Y à H:i') }}</p>
                <p><strong>Remise:</strong> {{ $reseller->discount_percentage }}%</p>
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
        </div>
        <div class="summary-card payments">
            <h4>TOTAL PAIEMENTS</h4>
            <div class="amount">{{ number_format($summary['total_payments'], 0, ',', ' ') }} F</div>
        </div>
        <div class="summary-card discount">
            <h4>REMISE OBTENUE</h4>
            <div class="amount">{{ number_format($summary['total_discount'], 0, ',', ' ') }} F</div>
        </div>
        <div class="summary-card balance">
            <h4>CRÉANCE PÉRIODE</h4>
            <div class="amount">{{ number_format($summary['balance'], 0, ',', ' ') }} F</div>
        </div>
    </div>

    <!-- Tableau des mouvements -->
    <table>
        <thead>
            <tr>
                <th style="width: 12%;">Date</th>
                <th style="width: 10%;">Type</th>
                <th style="width: 12%;">Référence</th>
                <th style="width: 30%;">Description</th>
                <th style="width: 12%;" class="text-end">Débit</th>
                <th style="width: 12%;" class="text-end">Crédit</th>
                <th style="width: 12%;" class="text-end">Créance</th>
            </tr>
        </thead>
        <tbody>
            @php $runningBalance = $openingBalance; @endphp
            
            <!-- Créance d'ouverture -->
            <tr class="opening">
                <td>{{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }}</td>
                <td><span class="badge badge-secondary">Ouverture</span></td>
                <td>-</td>
                <td>Créance d'ouverture</td>
                <td class="text-end">-</td>
                <td class="text-end">-</td>
                <td class="text-end">{{ number_format($openingBalance, 0, ',', ' ') }} F</td>
            </tr>

            @forelse($movements as $movement)
                @php
                    if ($movement['type'] === 'sale') {
                        $runningBalance += $movement['debit'];
                    } else {
                        $runningBalance -= $movement['credit'];
                    }
                @endphp
                <tr class="{{ $movement['type'] }}">
                    <td>{{ \Carbon\Carbon::parse($movement['date'])->format('d/m/Y') }}</td>
                    <td>
                        @if($movement['type'] === 'sale')
                            <span class="badge badge-warning">Achat</span>
                        @else
                            <span class="badge badge-success">Paiement</span>
                        @endif
                    </td>
                    <td>{{ $movement['reference'] }}</td>
                    <td>
                        {{ $movement['description'] }}
                        @if(isset($movement['products']) && count($movement['products']) > 0)
                            <ul class="products-list">
                                @foreach($movement['products'] as $product)
                                    <li>{{ $product['name'] }} x{{ $product['quantity'] }} = {{ number_format($product['total'], 0, ',', ' ') }} F</li>
                                @endforeach
                            </ul>
                        @endif
                    </td>
                    <td class="text-end">
                        @if($movement['debit'] > 0)
                            {{ number_format($movement['debit'], 0, ',', ' ') }} F
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-end">
                        @if($movement['credit'] > 0)
                            {{ number_format($movement['credit'], 0, ',', ' ') }} F
                        @else
                            -
                        @endif
                    </td>
                    <td class="text-end">{{ number_format($runningBalance, 0, ',', ' ') }} F</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 20px;">
                        Aucun mouvement sur cette période
                    </td>
                </tr>
            @endforelse

            <!-- Créance de clôture -->
            <tr class="closing">
                <td>{{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}</td>
                <td>Clôture</td>
                <td>-</td>
                <td>Créance de clôture</td>
                <td class="text-end">{{ number_format($summary['total_purchases'], 0, ',', ' ') }} F</td>
                <td class="text-end">{{ number_format($summary['total_payments'], 0, ',', ' ') }} F</td>
                <td class="text-end">{{ number_format($runningBalance, 0, ',', ' ') }} F</td>
            </tr>
        </tbody>
    </table>

    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature-box">
            <p>Signature du Revendeur</p>
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