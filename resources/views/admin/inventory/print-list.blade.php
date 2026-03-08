<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Liste Inventaire {{ $inventory->reference }}</title>
    <style>
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
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #333;
        }
        .header h1 {
            font-size: 18px;
            margin-bottom: 5px;
        }
        .header h2 {
            font-size: 14px;
            color: #666;
            font-weight: normal;
        }
        .info-block {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 9px;
        }
        .info-left, .info-right {
            width: 48%;
        }
        .info-box {
            background: #f5f5f5;
            padding: 10px;
            border-radius: 5px;
            margin-bottom: 10px;
        }
        .info-box strong {
            display: block;
            margin-bottom: 5px;
            font-size: 10px;
        }
        .category-section {
            margin-bottom: 15px;
            page-break-inside: avoid;
        }
        .category-header {
            background: #333;
            color: white;
            padding: 6px 10px;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 0;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background: #e9e9e9;
            font-weight: bold;
            font-size: 9px;
        }
        td {
            font-size: 9px;
        }
        .text-center {
            text-align: center;
        }
        .text-right {
            text-align: right;
        }
        .col-product {
            width: 40%;
        }
        .col-theoretical {
            width: 12%;
        }
        .col-physical {
            width: 15%;
        }
        .col-diff {
            width: 12%;
        }
        .col-notes {
            width: 21%;
        }
        .physical-input {
            width: 100%;
            border: 1px solid #999;
            background: #fff;
            min-height: 18px;
        }
        .notes-input {
            width: 100%;
            border-bottom: 1px dotted #999;
            min-height: 15px;
        }
        .footer {
            margin-top: 20px;
            padding-top: 15px;
            border-top: 1px solid #ccc;
            font-size: 9px;
        }
        .signature-block {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 45%;
            text-align: center;
        }
        .signature-line {
            border-bottom: 1px solid #333;
            margin-top: 40px;
            margin-bottom: 5px;
        }
        .page-break {
            page-break-after: always;
        }
        .summary {
            margin-top: 10px;
            font-size: 10px;
        }
        .summary strong {
            color: #333;
        }
        @page {
            margin: 15mm;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ $shopName }}</h1>
        <h2>FICHE D'INVENTAIRE - {{ $inventory->reference }}</h2>
    </div>

    <table style="width: 100%; border: none; margin-bottom: 15px;">
        <tr>
            <td style="border: none; width: 50%; vertical-align: top;">
                <div class="info-box">
                    <strong>Informations inventaire</strong>
                    <div>Référence: {{ $inventory->reference }}</div>
                    <div>Boutique: {{ $inventory->shop->name }}</div>
                    <div>Créé par: {{ $inventory->user->name }}</div>
                    <div>Date: {{ $inventory->created_at->format('d/m/Y à H:i') }}</div>
                </div>
            </td>
            <td style="border: none; width: 50%; vertical-align: top;">
                <div class="info-box">
                    <strong>Récapitulatif</strong>
                    <div>Nombre total de produits: <strong>{{ $items->count() }}</strong></div>
                    <div>Nombre de catégories: <strong>{{ $itemsByCategory->count() }}</strong></div>
                    <div>Statut: <strong>{{ strtoupper($inventory->status_label) }}</strong></div>
                </div>
            </td>
        </tr>
    </table>

    <p style="margin-bottom: 10px; font-style: italic; font-size: 9px;">
        Instructions: Comptez chaque produit physiquement et notez la quantité dans la colonne "Qté Physique". 
        Utilisez la colonne "Notes" pour tout commentaire (produit endommagé, mal placé, etc.).
    </p>

    @foreach($itemsByCategory as $categoryName => $categoryItems)
    <div class="category-section">
        <div class="category-header">
            {{ $categoryName }} ({{ $categoryItems->count() }} produit{{ $categoryItems->count() > 1 ? 's' : '' }})
        </div>
        <table>
            <thead>
                <tr>
                    <th class="col-product">Produit</th>
                    <th class="col-theoretical text-center">Qté Théorique</th>
                    <th class="col-physical text-center">Qté Physique</th>
                    <th class="col-diff text-center">Écart</th>
                    <th class="col-notes">Notes</th>
                </tr>
            </thead>
            <tbody>
                @foreach($categoryItems as $item)
                <tr>
                    <td>{{ $item->product->name }}</td>
                    <td class="text-center"><strong>{{ $item->theoretical_quantity }}</strong></td>
                    <td class="text-center">
                        <div class="physical-input"></div>
                    </td>
                    <td class="text-center">
                        <div class="physical-input"></div>
                    </td>
                    <td>
                        <div class="notes-input"></div>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    @endforeach

    <div class="footer">
        <div class="summary">
            <strong>Total: {{ $items->count() }} produit(s) à inventorier</strong>
        </div>
        
        <table style="width: 100%; border: none; margin-top: 30px;">
            <tr>
                <td style="border: none; width: 50%; text-align: center;">
                    <div>Effectué par:</div>
                    <div class="signature-line" style="width: 80%; margin: 40px auto 5px;"></div>
                    <div>Nom et signature</div>
                </td>
                <td style="border: none; width: 50%; text-align: center;">
                    <div>Date et heure de fin:</div>
                    <div class="signature-line" style="width: 80%; margin: 40px auto 5px;"></div>
                    <div>____/____/________ à ____:____</div>
                </td>
            </tr>
        </table>
    </div>

    <div style="margin-top: 20px; font-size: 8px; color: #666; text-align: center;">
        Document généré le {{ now()->format('d/m/Y à H:i') }} - {{ $shopName }}
    </div>
</body>
</html>
