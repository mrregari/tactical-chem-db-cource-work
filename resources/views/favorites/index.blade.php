@extends('layouts.app')
@section('title', 'Избранное')
@section('content')
<section class="hero"><div class="eyebrow">ЛИЧНЫЙ АРХИВ</div><h1>Избранное_</h1><p class="lead">Сохранённые химикаты и билды.</p></section>
<div class="section-heading"><h2>Химикаты</h2></div><div class="grid">@forelse($reagents as $reagent)<a class="card" href="{{ route('reagents.show', $reagent) }}"><span class="tag">РЕАГЕНТ</span><h3>{{ $reagent->name }} ↗</h3></a>@empty <p class="muted">Нет сохранённых химикатов.</p>@endforelse</div>
<div class="section-heading"><h2>Снаряжение</h2></div><div class="grid">@forelse($loadouts as $loadout)<a class="card" href="{{ route('loadouts.show', $loadout) }}"><span class="tag">БИЛД</span><h3>{{ $loadout->name }} ↗</h3></a>@empty <p class="muted">Нет сохранённых билдов.</p>@endforelse</div>
@endsection
