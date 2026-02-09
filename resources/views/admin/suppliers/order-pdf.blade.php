<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Bon de Commande - {{ $reference }}</title>
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
            font-size: 10px;
            line-height: 1.4;
            color: #333;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 20px;
            border-bottom: 2px solid #0d6efd;
            padding-bottom: 15px;
        }
        .header-left, .header-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .header-right {
            text-align: right;
        }
        .company-name {
            font-size: 18px;
            font-weight: bold;
            color: #0d6efd;
            margin-bottom: 5px;
        }
        .company-info {
            font-size: 9px;
            color: #666;
        }
        .document-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .document-ref {
            font-size: 12px;
            color: #0d6efd;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .info-box {
            display: table-cell;
            width: 50%;
            vertical-align: top;
            padding: 10px;
        }
        .info-box.supplier {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
        }
        .info-box.order {
            text-align: right;
        }
        .info-box h3 {
            font-size: 11px;
            color: #0d6efd;
            margin-bottom: 8px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        .info-box p {
            margin-bottom: 3px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table th {
            background-color: #0d6efd;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-weight: bold;
            font-size: 10px;
        }
        table th.text-center {
            text-align: center;
        }
        table th.text-end {
            text-align: right;
        }
        table td {
            padding: 8px;
            border-bottom: 1px solid #dee2e6;
            font-size: 10px;
        }
        table td.text-center {
            text-align: center;
        }
        table td.text-end {
            text-align: right;
        }
        table tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .stock-badge {
            display: inline-block;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }
        .stock-danger {
            background-color: #dc3545;
            color: white;
        }
        .stock-warning {
            background-color: #ffc107;
            color: #000;
        }
        .notes-section {
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            padding: 10px;
            margin-bottom: 20px;
        }
        .notes-section h4 {
            font-size: 10px;
            margin-bottom: 5px;
            color: #856404;
        }
        .summary {
            display: table;
            width: 100%;
            margin-bottom: 20px;
        }
        .summary-left, .summary-right {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .summary-box {
            background-color: #e7f1ff;
            border: 1px solid #0d6efd;
            padding: 15px;
            text-align: center;
        }
        .summary-box h4 {
            font-size: 11px;
            color: #0d6efd;
            margin-bottom: 5px;
        }
        .summary-box .count {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .signature-section {
            display: table;
            width: 100%;
            margin-top: 30px;
        }
        .signature-box {
            display: table-cell;
            width: 50%;
            text-align: center;
            padding: 10px;
        }
        .signature-box p {
            margin-bottom: 40px;
            font-size: 10px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 150px;
            margin: 0 auto;
            padding-top: 5px;
            font-size: 9px;
        }
        .footer {
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #666;
            padding: 10px;
            border-top: 1px solid #dee2e6;
        }
        .urgent {
            color: #dc3545;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <!-- En-tête -->
    <div class="header">
        <div class="header-left">
            <div class="company-name">{{ $shopName }}</div>
            <div class="company-info">
                @if($shopAddress){{ $shopAddress }}<br>@endif
                @if($shopPhone)Tél: {{ $shopPhone }}<br>@endif
                @if($shopEmail)Email: {{ $shopEmail }}@endif
            </div>
        </div>
        <div class="header-right">
            <div class="document-title">BON DE COMMANDE</div>
            <div class="document-ref">{{ $reference }}</div>
            <div style="margin-top: 10px; font-size: 10px;">
                Date: {{ $orderDate->format('d/m/Y') }}
            </div>
        </div>
    </div>

    <!-- Informations fournisseur -->
    <div class="info-section">
        <div class="info-box supplier">
            <h3>FOURNISSEUR</h3>
            <p><strong>{{ $supplier->company_name }}</strong></p>
            @if($supplier->contact_name)<p>À l'attention de: {{ $supplier->contact_name }}</p>@endif
            @if($supplier->address)<p>{{ $supplier->address }}</p>@endif
            @if($supplier->city)<p>{{ $supplier->city }}, {{ $supplier->country }}</p>@endif
            @if($supplier->phone)<p>Tél: {{ $supplier->phone }}</p>@endif
            @if($supplier->email)<p>Email: {{ $supplier->email }}</p>@endif
        </div>
        <div class="info-box order">
            <p><strong>Date de commande:</strong> {{ $orderDate->format('d/m/Y') }}</p>
            <p><strong>Émis par:</strong> {{ auth()->user()->name }}</p>
            <p style="margin-top: 10px;" class="urgent">
                <i>⚠️ COMMANDE URGENTE - RUPTURE DE STOCK</i>
            </p>
        </div>
    </div>

    <!-- Notes -->
    @if($notes)
    <div class="notes-section">
        <h4>📝 NOTES / INSTRUCTIONS SPÉCIALES:</h4>
        <p>{{ $notes }}</p>
    </div>
    @endif

    <!-- Tableau des produits -->
    <table>
        <thead>
            <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 45%;">Désignation</th>
                <th class="text-center" style="width: 15%;">Stock actuel</th>
                <th class="text-center" style="width: 15%;">Qté commandée</th>
                <th style="width: 20%;">Observations</th>
            </tr>
        </thead>
        <tbody>
            @foreach($orderItems as $index => $item)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $item['product']->name }}</strong>
                        @if($item['product']->sku)
                            <br><small style="color: #666;">Réf: {{ $item['product']->sku }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        @if($item['current_stock'] == 0)
                            <span class="stock-badge stock-danger">RUPTURE</span>
                        @else
                            <span class="stock-badge stock-warning">{{ $item['current_stock'] }}</span>
                        @endif
                    </td>
                    <td class="text-center">
                        <strong style="font-size: 12px;">{{ $item['quantity'] }}</strong>
                    </td>
                    <td>
                        @if($item['current_stock'] == 0)
                            <span class="urgent">URGENT</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Résumé -->
    <div class="summary">
        <div class="summary-left">
            <div class="summary-box">
                <h4>NOMBRE DE RÉFÉRENCES</h4>
                <div class="count">{{ count($orderItems) }}</div>
            </div>
        </div>
        <div class="summary-right">
            <div class="summary-box">
                <h4>QUANTITÉ TOTALE</h4>
                <div class="count">{{ collect($orderItems)->sum('quantity') }}</div>
            </div>
        </div>
    </div>

    <!-- Signatures -->
    <div class="signature-section">
        <div class="signature-box">
            <p>Signature et cachet du fournisseur</p>
            <div class="signature-line">{{ $supplier->company_name }}</div>
        </div>
        <div class="signature-box">
            <p>Signature de l'acheteur</p>
            <div class="signature-line">{{ $shopName }}</div>
        </div>
    </div>

    <!-- Pied de page -->
    <div class="footer">
        <p>Bon de commande généré le {{ now()->format('d/m/Y à H:i') }} - {{ $shopName }}</p>
        <p>Merci de confirmer la réception de cette commande et de nous indiquer les délais de livraison.</p>
    </div>
</body>
</html>
