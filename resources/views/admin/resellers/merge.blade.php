@extends('layouts.app')

@section('title', 'Fusion de revendeurs doublons')

@section('sidebar')
    @include('admin.partials.sidebar')
@endsection

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h2><i class="bi bi-people-fill me-2 text-primary"></i>Fusion de revendeurs doublons</h2>
        <p class="text-muted mb-0">Fusionnez plusieurs fiches d'un même réparateur/revendeur en une seule.</p>
    </div>
    <a href="{{ route('admin.resellers.index') }}" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Retour
    </a>
</div>

{{-- Suggestions automatiques --}}
@if($suggestedDuplicates->isNotEmpty())
<div class="card border-warning mb-4">
    <div class="card-header bg-warning bg-opacity-10">
        <h5 class="mb-0"><i class="bi bi-exclamation-triangle-fill text-warning me-2"></i>Doublons probables détectés ({{ $suggestedDuplicates->count() }} groupe(s))</h5>
    </div>
    <div class="card-body p-0">
        <table class="table table-sm table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nom commun</th>
                    <th>Fiches trouvées</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
            @foreach($suggestedDuplicates as $group)
            <tr>
                <td class="fw-semibold">{{ $group->name }}</td>
                <td>
                    @foreach($group->resellers as $r)
                        <span class="badge bg-light text-dark border me-1">
                            {{ $r->company_name }}
                            <span class="text-muted">· {{ $r->phone }}</span>
                        </span>
                    @endforeach
                </td>
                <td class="text-end">
                    <a href="{{ route('admin.resellers.merge', ['ids' => $group->resellers->pluck('id')->join(',')]) }}"
                       class="btn btn-sm btn-warning">
                        <i class="bi bi-scissors"></i> Fusionner ce groupe
                    </a>
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

{{-- Formulaire de fusion --}}
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-arrow-down-circle me-2"></i>Sélectionner les revendeurs à fusionner</h5>
            </div>
            <div class="card-body">

                @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $e)
                        <div>{{ $e }}</div>
                    @endforeach
                </div>
                @endif

                <form action="{{ route('admin.resellers.merge.process') }}" method="POST" id="mergeForm">
                    @csrf

                    {{-- Recherche et sélection des revendeurs --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Rechercher et ajouter des revendeurs</label>
                        <div class="input-group mb-2">
                            <input type="text" id="resellerSearch" class="form-control"
                                   placeholder="Tapez le nom ou le téléphone…" autocomplete="off">
                        </div>
                        <div id="searchResults" class="list-group mb-3" style="max-height:220px;overflow-y:auto;display:none;"></div>

                        <div id="selectedResellers" class="d-flex flex-wrap gap-2 mb-3">
                            @foreach($selected as $r)
                            <span class="badge bg-primary fs-6 d-flex align-items-center gap-1 px-3 py-2 selected-badge"
                                  data-id="{{ $r->id }}">
                                {{ $r->company_name }}
                                <small class="opacity-75">{{ $r->phone }}</small>
                                <input type="hidden" name="selected_ids[]" value="{{ $r->id }}">
                                <button type="button" class="btn-close btn-close-white ms-1" style="font-size:.6rem"
                                        onclick="removeBadge(this)"></button>
                            </span>
                            @endforeach
                        </div>
                        <small class="text-muted">Sélectionnez au moins 2 revendeurs.</small>
                    </div>

                    {{-- Choix du revendeur primaire --}}
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Revendeur primaire <span class="text-danger">*</span></label>
                        <p class="text-muted small mb-2">La fiche qui sera conservée. Les ventes et données des autres seront transférées vers celle-ci.</p>
                        <div id="primaryChoices" class="d-flex flex-column gap-2">
                            <div class="text-muted fst-italic" id="primaryPlaceholder">
                                <i class="bi bi-info-circle"></i> Ajoutez au moins 2 revendeurs pour choisir le primaire.
                            </div>
                        </div>
                        <input type="hidden" name="primary_id" id="primaryId" required>
                    </div>

                    {{-- Aperçu --}}
                    <div id="mergePreview" class="alert alert-info d-none">
                        <i class="bi bi-info-circle me-2"></i>
                        <span id="previewText"></span>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger" id="submitBtn" disabled>
                            <i class="bi bi-scissors me-1"></i> Fusionner maintenant
                        </button>
                        <button type="reset" class="btn btn-outline-secondary" onclick="resetForm()">
                            Réinitialiser
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card bg-light border-0">
            <div class="card-body">
                <h6><i class="bi bi-info-circle text-primary me-1"></i>Ce que fait la fusion</h6>
                <hr class="my-2">
                <ul class="small mb-0">
                    <li class="mb-1">Toutes les <strong>ventes</strong> des doublons sont rattachées au primaire</li>
                    <li class="mb-1">Les <strong>paiements</strong> et <strong>remboursements</strong> sont transférés</li>
                    <li class="mb-1">Les <strong>dettes</strong> sont additionnées</li>
                    <li class="mb-1">Les <strong>points de fidélité</strong> sont cumulés</li>
                    <li class="mb-1">Les <strong>bonus de fidélité</strong> de même année sont fusionnés</li>
                    <li class="mb-1">Les doublons sont <strong>désactivés</strong> (conservés pour l'historique)</li>
                </ul>
                <div class="alert alert-warning mt-3 mb-0 small">
                    <i class="bi bi-exclamation-triangle-fill me-1"></i>
                    <strong>Irréversible.</strong> Vérifiez bien votre sélection avant de confirmer.
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
const allResellers = @json($resellersData);

let selectedIds = new Set(@json($selected->pluck('id')));

// ── Recherche ──────────────────────────────────────────────────────────
document.getElementById('resellerSearch').addEventListener('input', function() {
    const q = this.value.toLowerCase().trim();
    const results = document.getElementById('searchResults');
    if (!q) { results.style.display = 'none'; return; }

    const hits = allResellers.filter(r =>
        r.name.toLowerCase().includes(q) || r.phone.includes(q)
    ).slice(0, 10);

    results.innerHTML = hits.map(r => `
        <button type="button" class="list-group-item list-group-item-action d-flex justify-content-between"
                onclick="addReseller(${r.id}, '${r.name.replace(/'/g,"\\'")}', '${r.phone}', ${r.debt})">
            <span><strong>${r.name}</strong> <span class="text-muted small">${r.phone}</span></span>
            <span class="badge bg-${r.debt > 0 ? 'danger' : 'secondary'}">${r.debt > 0 ? 'Dette: ' + fmt(r.debt) + ' F' : 'OK'}</span>
        </button>
    `).join('') || '<div class="list-group-item text-muted">Aucun résultat</div>';
    results.style.display = 'block';
});

document.addEventListener('click', function(e) {
    if (!e.target.closest('#resellerSearch') && !e.target.closest('#searchResults')) {
        document.getElementById('searchResults').style.display = 'none';
    }
});

// ── Ajout / retrait ────────────────────────────────────────────────────
function addReseller(id, name, phone, debt) {
    if (selectedIds.has(id)) return;
    selectedIds.add(id);

    const container = document.getElementById('selectedResellers');
    const span = document.createElement('span');
    span.className = 'badge bg-primary fs-6 d-flex align-items-center gap-1 px-3 py-2 selected-badge';
    span.dataset.id = id;
    span.innerHTML = `${name} <small class="opacity-75">${phone}</small>
        <input type="hidden" name="selected_ids[]" value="${id}">
        <button type="button" class="btn-close btn-close-white ms-1" style="font-size:.6rem" onclick="removeBadge(this)"></button>`;
    container.appendChild(span);

    document.getElementById('resellerSearch').value = '';
    document.getElementById('searchResults').style.display = 'none';
    refreshPrimaryChoices();
}

function removeBadge(btn) {
    const badge = btn.closest('.selected-badge');
    selectedIds.delete(parseInt(badge.dataset.id));
    badge.remove();
    if (document.getElementById('primaryId').value === badge.dataset.id) {
        document.getElementById('primaryId').value = '';
    }
    refreshPrimaryChoices();
}

function resetForm() {
    selectedIds.clear();
    document.getElementById('selectedResellers').innerHTML = '';
    document.getElementById('primaryId').value = '';
    refreshPrimaryChoices();
}

// ── Choix du primaire ──────────────────────────────────────────────────
function refreshPrimaryChoices() {
    const container = document.getElementById('primaryChoices');
    const placeholder = document.getElementById('primaryPlaceholder');
    const badges = [...document.querySelectorAll('.selected-badge')];
    const currentPrimary = document.getElementById('primaryId').value;

    if (badges.length < 2) {
        container.innerHTML = '';
        if (placeholder) container.appendChild(placeholder);
        document.getElementById('primaryId').value = '';
        document.getElementById('submitBtn').disabled = true;
        document.getElementById('mergePreview').classList.add('d-none');
        return;
    }

    container.innerHTML = badges.map(b => {
        const id = b.dataset.id;
        const nameEl = b.querySelector('strong') || b;
        const name = allResellers.find(r => r.id == id)?.name ?? id;
        const checked = id === currentPrimary ? 'checked' : '';
        return `<div class="form-check border rounded p-2">
            <input class="form-check-input" type="radio" name="_primary_choice" id="prim_${id}"
                   value="${id}" ${checked} onchange="setPrimary('${id}', '${name.replace(/'/g,"\\'")}')">
            <label class="form-check-label fw-semibold" for="prim_${id}">${name}</label>
        </div>`;
    }).join('');

    updatePreview();
}

function setPrimary(id, name) {
    document.getElementById('primaryId').value = id;
    updatePreview();
}

function updatePreview() {
    const primaryId = document.getElementById('primaryId').value;
    const allBadges = [...document.querySelectorAll('.selected-badge')];
    const submit = document.getElementById('submitBtn');
    const preview = document.getElementById('mergePreview');

    if (!primaryId || allBadges.length < 2) {
        submit.disabled = true;
        preview.classList.add('d-none');
        return;
    }

    const duplicates = allBadges.filter(b => b.dataset.id !== primaryId);
    const primaryName = allResellers.find(r => r.id == primaryId)?.name ?? primaryId;
    const dupNames = duplicates.map(b => allResellers.find(r => r.id == b.dataset.id)?.name ?? b.dataset.id).join(', ');

    document.getElementById('previewText').innerHTML =
        `<strong>${duplicates.length} doublon(s)</strong> (${dupNames}) seront fusionné(s) dans <strong>${primaryName}</strong>.`;
    preview.classList.remove('d-none');
    submit.disabled = false;

    // Synchroniser primary_id dans les selected_ids comme duplicate
    allBadges.forEach(b => {
        // s'assurer que les hidden inputs correspondent aux doublons
    });
}

// Avant soumission : construire duplicate_ids depuis les sélectionnés non-primaires
document.getElementById('mergeForm').addEventListener('submit', function(e) {
    const primaryId = document.getElementById('primaryId').value;
    if (!primaryId) { e.preventDefault(); alert('Choisissez le revendeur primaire.'); return; }

    // Supprimer les anciens duplicate_ids
    document.querySelectorAll('input[name="duplicate_ids[]"]').forEach(el => el.remove());

    const badges = [...document.querySelectorAll('.selected-badge')];
    badges.forEach(b => {
        if (b.dataset.id !== primaryId) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'duplicate_ids[]';
            input.value = b.dataset.id;
            this.appendChild(input);
        }
    });

    if (!document.querySelectorAll('input[name="duplicate_ids[]"]').length) {
        e.preventDefault();
        alert('Sélectionnez au moins un doublon différent du primaire.');
    }
});

function fmt(n) {
    return new Intl.NumberFormat('fr-FR').format(n);
}

// Init avec les sélectionnés pré-chargés
refreshPrimaryChoices();
</script>
@endpush
@endsection
