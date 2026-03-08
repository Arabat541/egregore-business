<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        @page {
            margin: 15mm 10mm 15mm 10mm;
        }
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 10px;
            line-height: 1.4;
            color: #333;
            padding: 15mm 10mm;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header .date {
            font-size: 11px;
            color: #666;
        }
        .shop-section {
            margin-bottom: 25px;
        }
        .shop-title {
            background: #333;
            color: white;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        th {
            background: #f5f5f5;
            padding: 8px 6px;
            text-align: left;
            font-weight: bold;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        td {
            padding: 6px;
            border: 1px solid #ddd;
            font-size: 9px;
        }
        tr:nth-child(even) {
            background: #fafafa;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .price {
            font-weight: bold;
            white-space: nowrap;
        }
        .normal-price {
            color: #198754;
        }
        .semi-wholesale-price {
            color: #0d6efd;
        }
        .wholesale-price {
            color: #6f42c1;
        }
        .sku {
            font-family: monospace;
            font-size: 8px;
            color: #666;
        }
        .footer {
            position: fixed;
            bottom: 20px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 8px;
            color: #999;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
        .page-break {
            page-break-before: always;
        }
        .legend {
            margin-bottom: 15px;
            font-size: 9px;
            padding: 8px;
            background: #f9f9f9;
            border: 1px solid #ddd;
        }
        .legend span {
            margin-right: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $title }}</h1>
        <div class="date">Généré le {{ date('d/m/Y à H:i') }}</div>
    </div>

    @if($shop)
        {{-- Une seule boutique --}}
        <table>
            <thead>
                <tr>
                    <th style="width: 80px;">SKU</th>
                    <th>Produit</th>
                    <th style="width: 100px;">Catégorie</th>
                    <th class="text-right" style="width: 100px;">Prix Réparateur</th>
                </tr>
            </thead>
            <tbody>
                @foreach($products as $product)
                    <tr>
                        <td class="sku">{{ $product->sku ?? '-' }}</td>
                        <td>{{ $product->name }}</td>
                        <td>{{ $product->category->name ?? '-' }}</td>
                        <td class="text-right price normal-price">{{ number_format($product->reseller_price ?? $product->normal_price ?? 0, 0, ',', ' ') }} F</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <p style="font-size: 10px; text-align: right;">
            <strong>Total : {{ $products->count() }} produits</strong>
        </p>
    @else
        {{-- Toutes les boutiques --}}
        @foreach($productsByShop as $shopId => $shopProducts)
            @if(!$loop->first)
                <div class="page-break"></div>
            @endif
            
            <div class="shop-section">
                <div class="shop-title">
                    {{ $shops[$shopId]->name ?? 'Boutique inconnue' }} 
                    ({{ $shops[$shopId]->code ?? '?' }})
                    - {{ $shopProducts->count() }} produits
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th style="width: 80px;">SKU</th>
                            <th>Produit</th>
                            <th style="width: 100px;">Catégorie</th>
                            <th class="text-right" style="width: 100px;">Prix Réparateur</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($shopProducts as $product)
                            <tr>
                                <td class="sku">{{ $product->sku ?? '-' }}</td>
                                <td>{{ $product->name }}</td>
                                <td>{{ $product->category->name ?? '-' }}</td>
                                <td class="text-right price normal-price">{{ number_format($product->reseller_price ?? $product->normal_price ?? 0, 0, ',', ' ') }} F</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endforeach
    @endif

    <div class="footer">
        EGREGORE BUSINESS - Liste des Prix Réparateur - Confidentiel
    </div>
</body>
</html>
