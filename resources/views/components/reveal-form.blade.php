<form method="POST" action="{{ $action }}" class="reveal-row">
    @csrf
    <div class="form-group"><label>Portée</label><select class="select" name="scope"><option value="all">Toute la table</option><option value="individual">Un joueur</option></select></div>
    <div class="form-group"><label>Joueur si individuel</label><select class="select" name="user_id"><option value="">—</option>@foreach($players as $player)<option value="{{ $player->id }}">{{ $player->character?->name ?? $player->name }}</option>@endforeach</select></div>
    <button class="btn btn-primary btn-sm" type="submit">Révéler</button>
</form>
