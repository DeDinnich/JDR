@props(['map', 'revealed', 'points' => [], 'editable' => false, 'canPlacePoints' => true])

{{--
    Carte quadrillée, partagée par la vue MJ et la vue joueur.

    Chaque case révélée est une <img> qui pointe vers la route de tuile ; une
    case fermée n'émet AUCUNE requête et reste un simple bloc sombre. C'est ce
    qui rend le brouillard réel : l'image de la case fermée n'existe pas dans
    la page, même pour qui inspecte le HTML.

    $editable : le MJ peut cliquer une case pour l'ouvrir/la refermer. Dans ce
    mode, TOUTES les tuiles sont rendues — une case fermée est simplement
    assombrie — pour qu'il voie ce qu'il ouvre. C'est sans risque : la route de
    tuile sert tout au MJ de toute façon, et la vue joueur, elle, n'émet aucune
    requête pour une case fermée.
--}}
@php($revealAll = $editable)

<div class="map-grid-wrapper">
    <div class="map-grid"
         data-map-grid
         data-map-slug="{{ $map->slug }}"
         data-columns="{{ $map->grid_columns }}"
         data-rows="{{ $map->grid_rows }}"
         @if($editable) data-cell-url="{{ route('gm.maps.cells.toggle', $map) }}" data-editable="1" @endif
         @if($canPlacePoints) data-point-url="{{ route('maps.points.store', $map) }}" @endif
         style="--grid-columns:{{ $map->grid_columns }};--grid-rows:{{ $map->grid_rows }};aspect-ratio:{{ $map->image_width }} / {{ $map->image_height }}">

        @for($row = 0; $row < $map->grid_rows; $row++)
            @for($column = 0; $column < $map->grid_columns; $column++)
                @php($isRevealed = isset($revealed[$column.':'.$row]))
                <div class="map-cell {{ $isRevealed ? 'is-revealed' : 'is-dark' }}"
                     data-column="{{ $column }}" data-row="{{ $row }}"
                     @if($editable) role="button" tabindex="0"
                        aria-label="Case {{ $column + 1 }}-{{ $row + 1 }}, {{ $isRevealed ? 'révélée' : 'dans le noir' }}" @endif>
                    @if($isRevealed || $revealAll)
                        <img src="{{ route('maps.tile', [$map, $row, $column]) }}" alt="" loading="lazy">
                    @endif
                </div>
            @endfor
        @endfor

        {{-- Les repères flottent au-dessus de la grille, en pourcentage. --}}
        <div class="map-points" data-map-points>
            @foreach($points as $point)
                <span class="map-point" data-point-id="{{ $point->id }}"
                      @if($point->user_id === auth()->id()) data-delete-url="{{ route('maps.points.destroy', [$map, $point]) }}" title="Cliquer pour supprimer ce repère" @endif
                      style="left:{{ $point->x_position }}%;top:{{ $point->y_position }}%;--point-color:{{ $point->color }}"
                      data-owner="{{ $point->user_id }}">
                    <span class="map-point-dot"></span>
                    <span class="map-point-label">
                        {{ $point->label }}
                        <span class="muted small">· {{ $point->author?->name }}</span>
                    </span>
                </span>
            @endforeach
        </div>
    </div>
</div>
