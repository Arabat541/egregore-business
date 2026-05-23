<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suivi Réparation - {{ $publicRepair['repair_number'] }}</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-color: #0d6efd;
            --success-color: #198754;
            --warning-color: #ffc107;
            --danger-color: #dc3545;
            --info-color: #0dcaf0;
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
        
        .status-header {
            padding: 30px;
            text-align: center;
            color: white;
        }
        
        .status-header.status-pending_payment       { background: linear-gradient(135deg, #ffc107, #e0a800); color: #000; }
        .status-header.status-paid_pending_diagnosis  { background: linear-gradient(135deg, #0dcaf0, #0aa2c0); }
        .status-header.status-in_diagnosis            { background: linear-gradient(135deg, #0dcaf0, #0aa2c0); }
        .status-header.status-waiting_parts           { background: linear-gradient(135deg, #fd7e14, #e8650a); }
        .status-header.status-in_repair               { background: linear-gradient(135deg, #0d6efd, #0a58ca); }
        .status-header.status-repaired                { background: linear-gradient(135deg, #198754, #157347); }
        .status-header.status-ready_for_pickup        { background: linear-gradient(135deg, #198754, #157347); }
        .status-header.status-delivered               { background: linear-gradient(135deg, #20c997, #1aa179); }
        .status-header.status-unrepairable            { background: linear-gradient(135deg, #dc3545, #b02a37); }
        .status-header.status-cancelled               { background: linear-gradient(135deg, #6c757d, #495057); }
        
        .status-icon {
            font-size: 4rem;
            margin-bottom: 15px;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .status-text {
            font-size: 1.5rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        
        .ticket-number {
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
        
        .progress-timeline {
            position: relative;
            padding-left: 30px;
        }
        
        .progress-timeline::before {
            content: '';
            position: absolute;
            left: 10px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #e9ecef;
            border-radius: 3px;
        }
        
        .timeline-step {
            position: relative;
            padding-bottom: 20px;
        }
        
        .timeline-step:last-child {
            padding-bottom: 0;
        }
        
        .timeline-step::before {
            content: '';
            position: absolute;
            left: -24px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e9ecef;
            border: 3px solid white;
            box-shadow: 0 0 0 3px #e9ecef;
        }
        
        .timeline-step.completed::before {
            background: #198754;
            box-shadow: 0 0 0 3px #d1e7dd;
        }
        
        .timeline-step.current::before {
            background: #0d6efd;
            box-shadow: 0 0 0 3px #cfe2ff;
            animation: pulse-dot 1.5s infinite;
        }
        
        @keyframes pulse-dot {
            0%, 100% { box-shadow: 0 0 0 3px #cfe2ff; }
            50% { box-shadow: 0 0 0 6px rgba(13, 110, 253, 0.3); }
        }
        
        .timeline-content {
            font-size: 0.95rem;
        }
        
        .timeline-content.completed {
            color: #198754;
        }
        
        .timeline-content.current {
            color: #0d6efd;
            font-weight: 600;
        }
        
        .timeline-content.pending {
            color: #adb5bd;
        }
        
        .cost-display {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
        }
        
        .cost-label {
            font-size: 0.85rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .cost-amount {
            font-size: 2rem;
            font-weight: 700;
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
        
        .alert-ready {
            background: linear-gradient(135deg, #d1e7dd, #badbcc);
            border: none;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            animation: celebrate 0.5s ease-out;
        }
        
        @keyframes celebrate {
            0% { transform: scale(0.8); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }
        
        .alert-ready i {
            font-size: 2rem;
            color: #198754;
            display: block;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="tracking-container">
        <div class="company-header">
            <h1><i class="fas fa-mobile-alt me-2"></i>{{ $settings['company_name'] ?? 'CRM Phone Shop' }}</h1>
            <p class="mb-0">Suivi de votre réparation</p>
        </div>
        
        <div class="tracking-card">
            @php
                $statusIcons = [
                    'pending_payment'       => 'fa-credit-card',
                    'paid_pending_diagnosis'=> 'fa-clock',
                    'in_diagnosis'          => 'fa-search',
                    'waiting_parts'         => 'fa-truck',
                    'in_repair'             => 'fa-tools',
                    'repaired'              => 'fa-check-circle',
                    'ready_for_pickup'      => 'fa-bell',
                    'delivered'             => 'fa-handshake',
                    'unrepairable'          => 'fa-times-circle',
                    'cancelled'             => 'fa-ban',
                ];

                $statusLabels = [
                    'pending_payment'       => 'En attente de paiement',
                    'paid_pending_diagnosis'=> 'En attente de diagnostic',
                    'in_diagnosis'          => 'Diagnostic en cours',
                    'waiting_parts'         => 'En attente de pièces',
                    'in_repair'             => 'En cours de réparation',
                    'repaired'              => 'Réparation terminée',
                    'ready_for_pickup'      => 'Prêt pour retrait',
                    'delivered'             => 'Livré',
                    'unrepairable'          => 'Irréparable',
                    'cancelled'             => 'Annulé',
                ];

                $statusOrder = [
                    'pending_payment',
                    'paid_pending_diagnosis',
                    'in_diagnosis',
                    'waiting_parts',
                    'in_repair',
                    'repaired',
                    'ready_for_pickup',
                    'delivered',
                ];
                $currentStatus = $publicRepair['status'];
                $currentIndex = array_search($currentStatus, $statusOrder);
            @endphp

            <div class="status-header status-{{ $currentStatus }}">
                <div class="status-icon">
                    <i class="fas {{ $statusIcons[$currentStatus] ?? 'fa-question' }}"></i>
                </div>
                <div class="status-text">{{ $statusLabels[$currentStatus] ?? $currentStatus }}</div>
                <div class="ticket-number">
                    <i class="fas fa-ticket-alt me-1"></i>{{ $publicRepair['repair_number'] }}
                </div>
            </div>

            <div class="tracking-body">
                @if(in_array($currentStatus, ['repaired', 'ready_for_pickup']))
                    <div class="alert-ready mb-4">
                        <i class="fas fa-bell"></i>
                        <strong>Votre appareil est prêt !</strong>
                        <p class="mb-0 mt-2">Vous pouvez venir le récupérer à la boutique.</p>
                    </div>
                @elseif($currentStatus === 'unrepairable')
                    <div class="alert alert-danger rounded-3 mb-4 text-center">
                        <i class="fas fa-times-circle fa-2x d-block mb-2"></i>
                        <strong>Appareil irréparable</strong>
                        <p class="mb-0 mt-1">Veuillez contacter la boutique pour plus d'informations.</p>
                    </div>
                @elseif($currentStatus === 'cancelled')
                    <div class="alert alert-secondary rounded-3 mb-4 text-center">
                        <i class="fas fa-ban fa-2x d-block mb-2"></i>
                        <strong>Réparation annulée</strong>
                    </div>
                @endif

                {{-- Appareil --}}
                <div class="info-section">
                    <h5><i class="fas fa-mobile-alt"></i> Appareil</h5>
                    <div class="info-content">
                        <div class="info-row">
                            <span class="info-label">Marque / Modèle</span>
                            <span class="info-value">{{ $publicRepair['device_brand'] }} {{ $publicRepair['device_model'] }}</span>
                        </div>
                        @if($publicRepair['device_imei_masked'])
                        <div class="info-row">
                            <span class="info-label">IMEI</span>
                            <span class="info-value">{{ $publicRepair['device_imei_masked'] }}</span>
                        </div>
                        @endif
                        <div class="info-row">
                            <span class="info-label">Client</span>
                            <span class="info-value">{{ $publicRepair['customer_name'] }}</span>
                        </div>
                    </div>
                </div>

                {{-- Date estimée --}}
                @if($publicRepair['estimated_completion_date'])
                <div class="info-section">
                    <h5><i class="fas fa-calendar"></i> Date de retrait estimée</h5>
                    <div class="info-content">
                        <div class="info-row">
                            <span class="info-label">Date estimée</span>
                            <span class="info-value">{{ \Carbon\Carbon::parse($publicRepair['estimated_completion_date'])->format('d/m/Y') }}</span>
                        </div>
                    </div>
                </div>
                @endif

                {{-- Progression --}}
                @if(!in_array($currentStatus, ['cancelled', 'unrepairable']))
                <div class="info-section">
                    <h5><i class="fas fa-tasks"></i> Progression</h5>
                    <div class="info-content">
                        <div class="progress-timeline">
                            @foreach($statusOrder as $index => $status)
                                @php
                                    $stepClass = 'pending';
                                    if ($currentIndex !== false) {
                                        if ($index < $currentIndex) $stepClass = 'completed';
                                        elseif ($index === $currentIndex) $stepClass = 'current';
                                    }
                                @endphp
                                <div class="timeline-step {{ $stepClass }}">
                                    <div class="timeline-content {{ $stepClass }}">
                                        <i class="fas {{ $statusIcons[$status] }} me-2"></i>
                                        {{ $statusLabels[$status] }}
                                        @if($stepClass === 'completed')
                                            <i class="fas fa-check ms-2"></i>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <div class="footer-info">
                <p><i class="fas fa-map-marker-alt me-2"></i>{{ $settings['company_address'] ?? '' }}</p>
                <p class="phone"><i class="fas fa-phone me-2"></i>{{ $settings['company_phone'] ?? '' }}</p>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <p class="text-white opacity-75 small">
                &copy; {{ date('Y') }} {{ $settings['company_name'] ?? 'CRM Phone Shop' }} - Tous droits réservés
            </p>
        </div>
    </div>
</body>
</html>
