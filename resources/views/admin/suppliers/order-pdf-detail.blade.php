<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Facture {{ $order->reference }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #333;
        }
        .container {
            padding: 20px;
        }
        .header {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        .header-left, .header-right {
            display: table-cell;
            vertical-align: top;
            width: 50%;
        }
        .header-right {
            text-align: right;
        }
        .company-name {
            font-size: 24px;
            font-weight: bold;
            color: #2563eb;
            margin-bottom: 5px;
        }
        .doc-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 5px;
        }
        .doc-reference {
            font-size: 14px;
            color: #666;
        }
        .info-section {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        .info-box {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .info-box:first-child {
            margin-right: 4%;
        }
        .info-box-title {
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
            color: #666;
            border-bottom: 2px solid #2563eb;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }
        .info-box h4 {
            font-size: 14px;
            margin-bottom: 5px;
        }
        .info-box p {
            margin: 2px 0;
            color: #555;
        }
        .meta-info {
            margin-bottom: 20px;
        }
        .meta-info table {
            width: auto;
        }
        .meta-info td {
            padding: 3px 15px 3px 0;
        }
        .meta-info td:first-child {
            font-weight: bold;
            color: #666;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        .items-table th {
            background-color: #2563eb;
            color: white;
            padding: 10px 8px;
            text-align: left;
            font-size: 11px;
        }
        .items-table th:last-child,
        .items-table th:nth-last-child(2) {
            text-align: right;
        }
        .items-table td {
            padding: 10px 8px;
            border-bottom: 1px solid #e5e7eb;
        }
        .items-table td:last-child,
        .items-table td:nth-last-child(2) {
            text-align: right;
        }
        .items-table tr:nth-child(even) {
            background-color: #f9fafb;
        }
        .items-table .product-name {
            font-weight: bold;
        }
        .items-table .product-sku {
            font-size: 10px;
            color: #666;
        }
        .totals-section {
            width: 100%;
            margin-top: 20px;
        }
        .totals-table {
            width: 300px;
            margin-left: auto;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px 10px;
        }
        .totals-table .total-row {
            background-color: #2563eb;
            color: white;
            font-size: 14px;
            font-weight: bold;
        }
        .totals-table .total-row td {
            padding: 12px 10px;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .status-draft { background-color: #fef3c7; color: #92400e; }
        .status-sent { background-color: #dbeafe; color: #1e40af; }
        .status-received { background-color: #d1fae5; color: #065f46; }
        .status-cancelled { background-color: #fee2e2; color: #991b1b; }
        .notes-section {
            margin-top: 30px;
            padding: 15px;
            background-color: #f8f9fa;
            border-radius: 5px;
        }
        .notes-title {
            font-weight: bold;
            margin-bottom: 5px;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            text-align: center;
            font-size: 10px;
            color: #666;
            border-top: 1px solid #e5e7eb;
            padding-top: 10px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
    </style>
</head>
<body>
    <div class="container">
        <!-- En-tête -->
        <div class="header">
            <div class="header-left">
                <div class="company-name">{{ $order->shop->name ?? 'EGREGORE BUSINESS' }}</div>
                @if($order->shop)
                    <p>{{ $order->shop->address ?? '' }}</p>
                    <p>{{ $order->shop->phone ?? '' }}</p>
                    <p>{{ $order->shop->email ?? '' }}</p>
                @endif
            </div>
            <div class="header-right">
                <div class="doc-title">FACTURE FOURNISSEUR</div>
                <div class="doc-reference">{{ $order->reference }}</div>
                <br>
                <span class="status-badge status-{{ $order->status }}">
                    @switch($order->status)
                        @case('draft') Brouillon @break
                        @case('sent') Envoyée @break
                        @case('confirmed') Confirmée @break
                        @case('received') Réceptionnée @break
                        @case('cancelled') Annulée @break
                    @endswitch
                </span>
            </div>
        </div>

        <!-- Informations fournisseur et dates -->
        <table class="info-section">
            <tr>
                <td class="info-box" style="width: 48%; padding-right: 10px;">
                    <div class="info-box-title">Fournisseur</div>
                    <h4>{{ $order->supplier->company_name }}</h4>
                    @if($order->supplier->contact_name)
                        <p>Contact: {{ $order->supplier->contact_name }}</p>
                    @endif
                    @if($order->supplier->phone)
                        <p>Tél: {{ $order->supplier->phone }}</p>
                    @endif
                    @if($order->supplier->email)
                        <p>Email: {{ $order->supplier->email }}</p>
                    @endif
                    @if($order->supplier->address)
                        <p>{{ $order->supplier->address }}</p>
                    @endif
                </td>
                <td class="info-box" style="width: 48%;">
                    <div class="info-box-title">Détails</div>
                    <table class="meta-info">
                        <tr>
                            <td>Date facture:</td>
                            <td>{{ $order->order_date->format('d/m/Y') }}</td>
                        </tr>
                        @if($order->received_date)
                        <tr>
                            <td>Réceptionné le:</td>
                            <td>{{ $order->received_date->format('d/m/Y') }}</td>
                        </tr>
                        @endif
                        <tr>
                            <td>Créé par:</td>
                            <td>{{ $order->user->name ?? '-' }}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Liste des articles -->
        <table class="items-table">
            <thead>
                <tr>
                    <th style="width: 40%;">Produit</th>
                    <th class="text-center" style="width: 15%;">Qté commandée</th>
                    @if($order->status === 'received')
                    <th class="text-center" style="width: 15%;">Qté reçue</th>
                    @endif
                    <th class="text-right" style="width: 15%;">Prix unitaire</th>
                    <th class="text-right" style="width: 15%;">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->items as $item)
                    <tr>
                        <td>
                            <span class="product-name">{{ $item->product->name ?? $item->product_name }}</span>
                            @if($item->product && $item->product->sku)
                                <br><span class="product-sku">SKU: {{ $item->product->sku }}</span>
                            @endif
                        </td>
                        <td class="text-center">{{ $item->quantity_ordered }}</td>
                        @if($order->status === 'received')
                        <td class="text-center">{{ $item->quantity_received }}</td>
                        @endif
                        <td class="text-right">{{ number_format($item->unit_price, 0, ',', ' ') }} FCFA</td>
                        <td class="text-right">{{ number_format($item->total_price, 0, ',', ' ') }} FCFA</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Totaux -->
        <div class="totals-section">
            <table class="totals-table">
                <tr>
                    <td>Nombre d'articles:</td>
                    <td class="text-right">{{ $order->items->count() }}</td>
                </tr>
                <tr>
                    <td>Quantité totale:</td>
                    <td class="text-right">{{ $order->items->sum($order->status === 'received' ? 'quantity_received' : 'quantity_ordered') }}</td>
                </tr>
                <tr class="total-row">
                    <td>TOTAL</td>
                    <td class="text-right">{{ number_format($order->total_amount, 0, ',', ' ') }} FCFA</td>
                </tr>
            </table>
        </div>

        <!-- Notes -->
        @if($order->notes)
        <div class="notes-section">
            <div class="notes-title">Notes:</div>
            <p>{{ $order->notes }}</p>
        </div>
        @endif

        <!-- Pied de page -->
        <div class="footer">
            Document généré le {{ now()->format('d/m/Y à H:i') }} | {{ $order->shop->name ?? 'EGREGORE BUSINESS' }}
        </div>
    </div>
</body>
</html>
