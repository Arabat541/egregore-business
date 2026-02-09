<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture - {{ $sale->invoice_number }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .tracking-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .company-header {
            text-align: center;
            color: white;
            padding: 30px 0 20px;
        }
        
        .company-header h1 {
            font-weight: 700;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }
        
        .tracking-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: slideUp 0.5s ease-out;
        }
        
        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #198754, #157347);
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .invoice-icon {
            font-size: 4rem;
            margin-bottom: 15px;
        }
        
        .invoice-title {
            font-size: 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .invoice-number {
            font-size: 1rem;
            opacity: 0.9;
            margin-top: 10px;
        }
        
        .tracking-body {
            padding: 30px;
        }
        
        .info-section {
            margin-bottom: 25px;
        }
        
        .info-section h5 {
            color: #6c757d;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .info-section h5 i {
            color: #764ba2;
        }
        
        .info-content {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
        }
        
        .info-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px dashed #dee2e6;
        }
        
        .info-row:last-child {
            border-bottom: none;
        }
        
        .info-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .info-value {
            font-weight: 600;
            color: #212529;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .items-table th {
            background: #e9ecef;
            padding: 12px;
            font-size: 0.85rem;
            text-transform: uppercase;
            color: #495057;
            text-align: left;
        }
        
        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }
        
        .items-table td {
            padding: 12px;
            border-bottom: 1px solid #e9ecef;
        }
        
        .items-table tr:last-child td {
            border-bottom: none;
        }
        
        .product-name {
            font-weight: 600;
            color: #212529;
        }
        
        .product-details {
            font-size: 0.85rem;
            color: #6c757d;
        }
        
        .totals-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 15px;
            margin-top: 20px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
        }
        
        .total-row.grand-total {
            border-top: 2px solid #dee2e6;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 1.2rem;
        }
        
        .total-row.grand-total .total-value {
            color: #198754;
            font-weight: 700;
        }
        
        .payment-badge {
            display: inline-block;
            padding: 6px 15px;
            border-radius: 20px;
            font-weight: 600;
            font-size: 0.85rem;
        }
        
        .payment-badge.paid {
            background: #d1e7dd;
            color: #0f5132;
        }
        
        .payment-badge.partial {
            background: #fff3cd;
            color: #664d03;
        }
        
        .payment-badge.pending {
            background: #f8d7da;
            color: #842029;
        }
        
        .warranty-info {
            background: linear-gradient(135deg, #cff4fc, #b6effb);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin-top: 20px;
        }
        
        .warranty-info i {
            font-size: 2rem;
            color: #0dcaf0;
            margin-bottom: 10px;
        }
        
        .warranty-info h6 {
            color: #055160;
            margin-bottom: 10px;
        }
        
        .warranty-info p {
            color: #055160;
            margin: 0;
            font-size: 0.9rem;
        }
        
        .footer-info {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        
        .footer-info p {
            margin: 5px 0;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .footer-info .phone {
            font-size: 1.1rem;
            color: #764ba2;
            font-weight: 600;
        }
        
        .reseller-badge {
            background: linear-gradient(135deg, #fd7e14, #e55b00);
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <div class="tracking-container">
        <div class="company-header">
            <h1><i class="fas fa-mobile-alt me-2"></i>{{ $settings['company_name'] ?? 'CRM Phone Shop' }}</h1>
            <p class="mb-0">Votre facture</p>
        </div>
        
        <div class="tracking-card">
            <div class="invoice-header">
                <div class="invoice-icon">
                    <i class="fas fa-receipt"></i>
                </div>
                <div class="invoice-title">Facture</div>
                <div class="invoice-number">
                    <i class="fas fa-hashtag me-1"></i>{{ $sale->invoice_number }}
                </div>
            </div>
            
            <div class="tracking-body">
                {{-- Informations vente --}}
                <div class="info-section">
                    <h5><i class="fas fa-info-circle"></i> Informations</h5>
                    <div class="info-content">
                        <div class="info-row">
                            <span class="info-label">Date d'achat</span>
                            <span class="info-value">{{ $sale->created_at->format('d/m/Y à H:i') }}</span>
                        </div>
                        <div class="info-row">
                            <span class="info-label">Statut</span>
                            <span class="info-value">
                                @if($sale->payment_status === 'paid')
                                    <span class="payment-badge paid"><i class="fas fa-check me-1"></i>Payée</span>
                                @elseif($sale->payment_status === 'partial')
                                    <span class="payment-badge partial"><i class="fas fa-clock me-1"></i>Partiel</span>
                                @else
                                    <span class="payment-badge pending"><i class="fas fa-exclamation me-1"></i>En attente</span>
                                @endif
                            </span>
                        </div>
                        @if($sale->customer)
                        <div class="info-row">
                            <span class="info-label">Client</span>
                            <span class="info-value">{{ $sale->customer->full_name }}</span>
                        </div>
                        @endif
                        @if($sale->reseller)
                        <div class="info-row">
                            <span class="info-label">Revendeur</span>
                            <span class="info-value">
                                <span class="reseller-badge">{{ $sale->reseller->company_name }}</span>
                            </span>
                        </div>
                        @endif
                    </div>
                </div>
                
                {{-- Articles --}}
                <div class="info-section">
                    <h5><i class="fas fa-shopping-cart"></i> Articles ({{ $sale->items->count() }})</h5>
                    <div class="info-content p-0">
                        <table class="items-table">
                            <thead>
                                <tr>
                                    <th>Article</th>
                                    <th>Qté</th>
                                    <th>Prix</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($sale->items as $item)
                                <tr>
                                    <td>
                                        <div class="product-name">{{ $item->product->name ?? 'Produit supprimé' }}</div>
                                        @if($item->product && $item->product->brand)
                                            <div class="product-details">{{ $item->product->brand }}</div>
                                        @endif
                                    </td>
                                    <td>{{ $item->quantity }}</td>
                                    <td>{{ number_format($item->total_price, 2, ',', ' ') }} €</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                
                {{-- Totaux --}}
                <div class="totals-section">
                    <div class="total-row">
                        <span class="info-label">Sous-total</span>
                        <span class="info-value">{{ number_format($sale->subtotal ?? $sale->total_amount, 2, ',', ' ') }} €</span>
                    </div>
                    @if($sale->discount_amount > 0)
                    <div class="total-row">
                        <span class="info-label">Remise</span>
                        <span class="info-value text-success">- {{ number_format($sale->discount_amount, 2, ',', ' ') }} €</span>
                    </div>
                    @endif
                    <div class="total-row grand-total">
                        <span>Total TTC</span>
                        <span class="total-value">{{ number_format($sale->total_amount, 2, ',', ' ') }} €</span>
                    </div>
                    @if($sale->amount_paid > 0 && $sale->amount_paid < $sale->total_amount)
                    <div class="total-row">
                        <span class="info-label">Payé</span>
                        <span class="info-value">{{ number_format($sale->amount_paid, 2, ',', ' ') }} €</span>
                    </div>
                    <div class="total-row">
                        <span class="info-label">Reste à payer</span>
                        <span class="info-value text-danger">{{ number_format($sale->total_amount - $sale->amount_paid, 2, ',', ' ') }} €</span>
                    </div>
                    @endif
                </div>
                
                {{-- Garantie --}}
                <div class="warranty-info">
                    <i class="fas fa-shield-alt"></i>
                    <h6>Garantie</h6>
                    <p>
                        Vos produits sont garantis conformément aux conditions générales de vente.
                        Conservez cette facture comme preuve d'achat.
                    </p>
                </div>
            </div>
            
            <div class="footer-info">
                <p><i class="fas fa-map-marker-alt me-2"></i>{{ $settings['company_address'] ?? '' }}</p>
                <p class="phone"><i class="fas fa-phone me-2"></i>{{ $settings['company_phone'] ?? '' }}</p>
                @if(isset($settings['company_email']))
                    <p><i class="fas fa-envelope me-2"></i>{{ $settings['company_email'] }}</p>
                @endif
                @if(isset($settings['company_siret']))
                    <p class="small mt-2">SIRET : {{ $settings['company_siret'] }}</p>
                @endif
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p class="text-white opacity-75 small">
                Merci pour votre confiance !<br>
                &copy; {{ date('Y') }} {{ $settings['company_name'] ?? 'CRM Phone Shop' }} - Tous droits réservés
            </p>
        </div>
    </div>
</body>
</html>
