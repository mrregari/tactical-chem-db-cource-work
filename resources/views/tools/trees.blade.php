@extends('layouts.app')
@section('title', 'Деревья синтеза')
@section('content')
<section class="hero compact-hero"><div class="eyebrow">[07] // REACTION GRAPH</div><h1>Деревья синтеза<span class="accent">_</span></h1><p class="lead">Посмотри, из каких реагентов можно получить выбранный продукт. Глубина дерева ограничена пятью уровнями, чтобы избежать циклов и слишком больших графов.</p></section>
<form method="GET" class="panel form-panel"><label>ЦЕЛЕВОЙ РЕАГЕНТ<select name="reagent" required><option value="">Выбери реагент…</option>@foreach($reagents as $reagent)<option value="{{ $reagent->id }}" @selected($selected?->id === $reagent->id)>{{ $reagent->name }}</option>@endforeach</select></label><button class="button" type="submit">Построить дерево</button></form>
@if($selected)<div class="tree-root"><span class="eyebrow">TARGET</span><h2>{{ $selected->name }}</h2><span class="muted">Рецепты из опубликованных данных выбранного форка</span></div>@if($tree)<div class="tree-list">@foreach($tree as $node)<div class="tree-node" style="--depth:{{ min($node['depth'], 5) }}"><span class="tree-depth">L{{ $node['depth'] }}</span><strong>{{ $node['name'] }}</strong>@if($node['ingredients'])<span class="muted">из: {{ collect($node['ingredients'])->map(fn($i) => $i['name'].' × '.$i['amount'])->join(' + ') }}</span>@else<span class="muted">Базовый реагент или рецепт не найден</span>@endif</div>@endforeach</div>@else<div class="panel">Для этого реагента нет опубликованного рецепта в выбранном форке.</div>@endif
@endif
@endsection
