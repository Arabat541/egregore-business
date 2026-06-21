<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $reseller->company_name }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root { --navy: #1a1a2e; --navy-light: #0f3460; }
        body { background: #f0f2f5; font-family: 'Segoe UI', sans-serif; }
        .top-nav {
            background: linear-gradient(135deg, var(--navy), var(--navy-light));
            color: white;
            padding: 0.6rem 0;
        }
        .debt-hero {
            background: linear-gradient(135deg, #dc2626, #b91c1c);
            color: white;
            border-radius: 16px;
            padding: 1.5rem;
            text-align: center;
        }
        .debt-hero.zero { background: linear-gradient(135deg, #16a34a, #15803d); }
        .debt-amount { font-size: 2.4rem; font-weight: 800; line-height: 1; }
        .sale-card {
            background: white;
            border-radius: 12px;
            padding: 0.9rem 1rem;
            margin-bottom: 0.6rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .sale-card .invoice { font-weight: 700; font-size: 0.85rem; color: var(--navy); }
        .sale-card .date { font-size: 0.75rem; color: #6c757d; }
        .sale-card .amounts { text-align: right; }
        .sale-card .due { font-weight: 700; font-size: 1.05rem; color: #dc2626; }
        .sale-card .total { font-size: 0.75rem; color: #6c757d; }
        .section-title {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--navy);
            margin-bottom: 0.6rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }
        .tab-btn {
            flex: 1;
            padding: 0.7rem 0.5rem;
            border: none;
            background: white;
            font-weight: 600;
            font-size: 0.82rem;
            color: #6c757d;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }
        .tab-btn.active { color: var(--navy); border-bottom-color: var(--navy); }
        .tab-panel { display: none; }
        .tab-panel.active { display: block; }
        .pay-row {
            background: white;
            border-radius: 10px;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            box-shadow: 0 1px 4px rgba(0,0,0,0.05);
        }
        .pay-row.cancelled { opacity: 0.5; text-decoration: line-through; }
        .movement-table { font-size: 0.8rem; }
        .movement-table th {
            background: var(--navy);
            color: white;
            font-size: 0.75rem;
            padding: 0.5rem;
        }
        .movement-table td { padding: 0.45rem 0.5rem; vertical-align: middle; }
        .row-sale { background: #fff8e1; }
        .row-payment { background: #e8f5e9; }
        .row-opening { background: #e9ecef; font-weight: 600; }
        .row-closing { background: #dbeafe; font-weight: 700; }
        @media (max-width: 576px) {
            .debt-amount { font-size: 2rem; }
            .container-fluid { padding-left: 0.75rem; padding-right: 0.75rem; }
        }
    </style>
</head>
<body>

<nav class="top-nav">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div>
            <div style="font-weight:700;font-size:0.95rem;"><i class="bi bi-shop me-1"></i>{{ $reseller->company_name }}</div>
            <div style="font-size:0.78rem;opacity:0.8;"><i class="bi bi-person me-1"></i>{{ $reseller->contact_name }}</div>
        </div>
        <form method="POST" action="{{ route('reseller-portal.logout') }}" class="d-inline">
            @csrf
            <button type="submit" class="btn btn-sm btn-outline-light py-1 px-2" style="font-size:.8rem;">
                <i class="bi bi-box-arrow-right"></i>
            </button>
        </form>
    </div>
</nav>

<div class="container-fluid py-3">

    {{-- CRÉANCE --}}
    <div class="debt-hero mb-3 {{ $reseller->current_debt <= 0 ? 'zero' : '' }}">
        <div style="font-size:0.85rem;opacity:0.9;margin-bottom:0.3rem;">Créance actuelle</div>
        <div class="debt-amount">{{ number_format($reseller->current_debt, 0, ',', ' ') }} F</div>
    </div>

    {{-- VENTES IMPAYÉES --}}
    <div class="section-title">
        <i class="bi bi-exclamation-triangle text-danger"></i>
        Factures en cours ({{ $unpaidSales->count() }})
    </div>

    @forelse($unpaidSales as $sale)
    <div class="sale-card">
        <div>
            <div class="invoice">{{ $sale->invoice_number }}</div>
            <div class="date">{{ $sale->created_at->format('d/m/Y') }}</div>
        </div>
        <div class="amounts">
            <div class="due">{{ number_format($sale->amount_due, 0, ',', ' ') }} F</div>
            @if($sale->amount_paid > 0)
            <div class="total">payé {{ number_format($sale->amount_paid, 0, ',', ' ') }} / {{ number_format($sale->total_amount, 0, ',', ' ') }}</div>
            @else
            <div class="total">total {{ number_format($sale->total_amount, 0, ',', ' ') }} F</div>
            @endif
        </div>
    </div>
    @empty
    <div class="text-center py-4 text-muted" style="font-size:0.9rem;">
        <i class="bi bi-check-circle fs-3 d-block mb-1 text-success"></i>
        Aucune facture impayée
    </div>
    @endforelse

    {{-- ONGLETS --}}
    <div class="d-flex mt-4 mb-0 rounded-top overflow-hidden" style="box-shadow:0 -2px 8px rgba(0,0,0,0.05);">
        <button class="tab-btn active" onclick="switchTab('paiements', this)">
            <i class="bi bi-cash-coin d-block fs-5"></i>Paiements
        </button>
        <button class="tab-btn" onclick="switchTab('releve', this)">
            <i class="bi bi-list-ul d-block fs-5"></i>Relevé
        </button>
    </div>

    {{-- PAIEMENTS --}}
    <div class="tab-panel active" id="panel-paiements" style="background:white;border-radius:0 0 12px 12px;padding:0.75rem;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        @forelse($payments as $pay)
        @php $cancelled = (bool) $pay->cancelled_at; @endphp
        <div class="pay-row {{ $cancelled ? 'cancelled' : '' }}">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div style="font-weight:600;font-size:0.85rem;">
                        {{ $pay->created_at->format('d/m/Y') }}
                        @if($cancelled)
                            <span class="badge bg-danger ms-1" style="font-size:.65rem;">ANNULÉ</span>
                        @endif
                    </div>
                    <div style="font-size:0.75rem;color:#6c757d;">
                        @if($pay->sale)
                            {{ $pay->sale->invoice_number }}
                        @else
                            Toutes créances
                        @endif
                        @if($pay->has_product_return)
                            <span class="badge bg-info ms-1" style="font-size:.6rem;">+ retour</span>
                        @endif
                    </div>
                </div>
                <div style="font-weight:700;font-size:1rem;" class="{{ $cancelled ? 'text-muted' : 'text-success' }}">
                    {{ number_format($pay->amount, 0, ',', ' ') }} F
                </div>
            </div>
        </div>
        @empty
        <div class="text-center py-4 text-muted" style="font-size:0.85rem;">
            <i class="bi bi-inbox fs-3 d-block mb-1"></i>
            Aucun paiement
        </div>
        @endforelse
    </div>

    {{-- RELEVÉ DE COMPTE --}}
    <div class="tab-panel" id="panel-releve" style="background:white;border-radius:0 0 12px 12px;padding:0.5rem;box-shadow:0 2px 8px rgba(0,0,0,0.06);">
        <div class="table-responsive">
            <table class="table table-bordered mb-0 movement-table">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Réf</th>
                        <th class="text-end">Achat</th>
                        <th class="text-end">Paiem.</th>
                        <th class="text-end">Solde</th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="row-opening">
                        <td>{{ \Carbon\Carbon::parse($startDate)->format('d/m') }}</td>
                        <td>Ouverture</td>
                        <td class="text-end">—</td>
                        <td class="text-end">—</td>
                        <td class="text-end">{{ number_format($openingBalance, 0, ',', ' ') }}</td>
                    </tr>
                    @foreach($movements as $m)
                    <tr class="{{ $m['type'] === 'sale' ? 'row-sale' : 'row-payment' }}">
                        <td>{{ \Carbon\Carbon::parse($m['date'])->format('d/m') }}</td>
                        <td style="font-size:.72rem;">{{ $m['reference'] }}</td>
                        <td class="text-end text-danger">{{ $m['debit'] > 0 ? number_format($m['debit'], 0, ',', ' ') : '—' }}</td>
                        <td class="text-end text-success">{{ $m['credit'] > 0 ? number_format($m['credit'], 0, ',', ' ') : '—' }}</td>
                        <td class="text-end fw-bold {{ $m['running_balance'] > 0 ? 'text-danger' : 'text-success' }}">{{ number_format($m['running_balance'], 0, ',', ' ') }}</td>
                    </tr>
                    @endforeach
                    <tr class="row-closing">
                        <td>{{ \Carbon\Carbon::parse($endDate)->format('d/m') }}</td>
                        <td>Clôture</td>
                        <td class="text-end">{{ number_format($summary['total_purchases'], 0, ',', ' ') }}</td>
                        <td class="text-end">{{ number_format($summary['total_payments'], 0, ',', ' ') }}</td>
                        <td class="text-end" style="font-size:.95rem;">{{ number_format($summary['balance'], 0, ',', ' ') }} F</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function switchTab(name, btn) {
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
    btn.classList.add('active');
    document.getElementById('panel-' + name).classList.add('active');
}
</script>
</body>
</html>
