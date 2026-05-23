{{--
    Composant : sélecteur de nombre de résultats par page
    Usage : <x-per-page-selector :current="$perPage" />
    Envoie le paramètre "per_page" via GET en conservant les autres filtres actifs.
--}}
@props(['current' => 20, 'options' => [10, 20, 50, 100]])

<form method="GET" action="" class="d-inline-flex align-items-center gap-1" id="per-page-form">
    {{-- Conserver tous les paramètres GET existants sauf per_page --}}
    @foreach(request()->except('per_page', 'page') as $key => $value)
        @if(is_array($value))
            @foreach($value as $v)
                <input type="hidden" name="{{ $key }}[]" value="{{ $v }}">
            @endforeach
        @else
            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
        @endif
    @endforeach

    <label class="text-muted small mb-0 text-nowrap">Lignes :</label>
    <select name="per_page" class="form-select form-select-sm" style="width:75px;"
            onchange="this.form.submit()">
        @foreach($options as $opt)
            <option value="{{ $opt }}" {{ (int)$current === (int)$opt ? 'selected' : '' }}>{{ $opt }}</option>
        @endforeach
    </select>
</form>
