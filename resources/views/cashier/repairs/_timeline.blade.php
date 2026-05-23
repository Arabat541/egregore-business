{{--
    Partial : timeline visuelle du statut d'une réparation
    Variable attendue : $repair (App\Models\Repair)
--}}
@php
    $timelineSteps = [
        ['key' => 'pending_payment',        'label' => 'Acompte',    'icon' => 'bi-cash-coin'],
        ['key' => 'paid_pending_diagnosis', 'label' => 'Payé',       'icon' => 'bi-check-circle'],
        ['key' => 'in_diagnosis',           'label' => 'Diagnostic', 'icon' => 'bi-search'],
        ['key' => 'waiting_parts',          'label' => 'Pièces',     'icon' => 'bi-box-seam'],
        ['key' => 'in_repair',              'label' => 'Réparation', 'icon' => 'bi-tools'],
        ['key' => 'repaired',               'label' => 'Réparé',     'icon' => 'bi-wrench-adjustable-circle'],
        ['key' => 'ready_for_pickup',       'label' => 'Prêt',       'icon' => 'bi-bell'],
        ['key' => 'delivered',              'label' => 'Livré',      'icon' => 'bi-bag-check'],
    ];
    $statusOrder    = array_column($timelineSteps, 'key');
    $currentIdx     = array_search($repair->status, $statusOrder);
    $isCancelled    = $repair->status === 'cancelled';
    $isUnrepairable = $repair->status === 'unrepairable';
@endphp
<div class="card mb-4 border-0 shadow-sm">
    <div class="card-body py-3">
        @if($isCancelled)
            <div class="alert alert-danger mb-0 py-2 text-center fw-semibold">
                <i class="bi bi-x-circle me-1"></i> Réparation annulée
            </div>
        @elseif($isUnrepairable)
            <div class="alert alert-secondary mb-0 py-2 text-center fw-semibold">
                <i class="bi bi-slash-circle me-1"></i> Appareil non réparable
            </div>
        @else
        <div class="d-flex align-items-center justify-content-between" style="overflow-x:auto;">
            @foreach($timelineSteps as $i => $step)
                @php
                    $done    = $currentIdx !== false && $i < $currentIdx;
                    $current = $currentIdx !== false && $i === $currentIdx;
                    $color   = $done ? '#198754' : ($current ? '#0d6efd' : '#dee2e6');
                    $textCol = $done ? 'text-success' : ($current ? 'text-primary' : 'text-muted');
                @endphp
                <div class="d-flex flex-column align-items-center flex-shrink-0" style="min-width:64px;">
                    <div class="rounded-circle d-flex align-items-center justify-content-center"
                         style="width:36px;height:36px;background:{{ $color }};color:{{ ($done||$current) ? 'white' : '#adb5bd' }};font-size:.85rem;
                                {{ $current ? 'box-shadow:0 0 0 4px rgba(13,110,253,.2);' : '' }}">
                        @if($done)
                            <i class="bi bi-check-lg"></i>
                        @else
                            <i class="bi {{ $step['icon'] }}"></i>
                        @endif
                    </div>
                    <small class="mt-1 text-center {{ $textCol }}" style="font-size:.7rem;line-height:1.2;{{ $current ? 'font-weight:700;' : '' }}">
                        {{ $step['label'] }}
                    </small>
                </div>
                @if(!$loop->last)
                <div class="flex-grow-1 mx-1" style="height:2px;background:{{ $done ? '#198754' : '#dee2e6' }};min-width:12px;margin-bottom:18px;"></div>
                @endif
            @endforeach
        </div>
        @endif
    </div>
</div>
