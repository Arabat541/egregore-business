{{--
    Composant : état vide guidant
    Usage :
      <x-empty-state
          icon="bi-tools"
          title="Aucune réparation"
          message="Il n'y a pas encore de réparation enregistrée."
          :action-url="route('cashier.repairs.create')"
          action-label="Nouvelle réparation"
      />
--}}
@props([
    'icon'        => 'bi-inbox',
    'title'       => 'Aucun résultat',
    'message'     => 'Aucun élément ne correspond à vos critères.',
    'actionUrl'   => null,
    'actionLabel' => null,
    'secondaryUrl'   => null,
    'secondaryLabel' => null,
])

<div class="text-center py-5 px-3">
    <div class="mb-3" style="opacity:.35;">
        <i class="bi {{ $icon }}" style="font-size:3.5rem;"></i>
    </div>
    <h5 class="fw-semibold text-secondary mb-1">{{ $title }}</h5>
    <p class="text-muted mb-4" style="max-width:380px;margin:0 auto;">{{ $message }}</p>
    @if($actionUrl)
    <div class="d-flex justify-content-center gap-2 flex-wrap">
        <a href="{{ $actionUrl }}" class="btn btn-primary">
            <i class="bi bi-plus-lg me-1"></i>{{ $actionLabel }}
        </a>
        @if($secondaryUrl)
        <a href="{{ $secondaryUrl }}" class="btn btn-outline-secondary">{{ $secondaryLabel }}</a>
        @endif
    </div>
    @endif
</div>
