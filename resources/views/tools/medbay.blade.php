@extends('layouts.app')
@section('title', 'What Heals? — Медбей')
@section('content')
<section class="hero compact-hero"><div class="eyebrow">[06] // MEDICAL ANALYSIS</div><h1>What Heals?<span class="accent">_</span></h1><p class="lead">Найди лекарства и реагенты по названию, описанию или эффектам. Все результаты ограничены текущим форком.</p></section>
<form class="toolbar" method="GET"><label>ПОИСК ЛЕЧЕНИЯ / ТИП УРОНА<input type="search" name="q" value="{{ $q }}" placeholder="Brute, burn, poison, лечение…"></label><button class="button" type="submit">Найти лечение</button>@if($q)<a class="button button-outline" href="{{ route('tools.medbay') }}">Сбросить</a>@endif</form>
<div class="grid">@forelse($reagents as $reagent)<a class="card" href="{{ route('reagents.show', $reagent) }}"><div class="card-top"><span class="tag">{{ $reagent->category?->name ?? 'Реагент' }}</span><span class="muted">#{{ $reagent->id }}</span></div><h3>{{ $reagent->name }} ↗</h3><p>{{ Str::limit($reagent->description, 160) }}</p>@if($reagent->effects)<div class="effect-chip">{{ Str::limit($reagent->effects, 240) }}</div>@endif</a>@empty<div class="panel empty-state">В выбранном источнике не найдены подходящие лекарства. Попробуй общий каталог.</div>@endforelse</div><div class="pagination">{{ $reagents->links() }}</div>
@endsection
