@extends('layouts.app')
@section('title', 'Снаряжение')
@section('content')
<section class="hero"><div class="eyebrow">[02] // ТАКТИЧЕСКИЙ ОТДЕЛ</div><h1>Билды снаряжения_</h1><p class="lead">Подборки экипировки морпехов для разных специализаций.</p></section>
<div class="section-heading"><h2>Билды // {{ $loadouts->total() }}</h2>@if(auth()->check() && auth()->user()->role !== 'guest') <a class="button button-outline" href="{{ route('loadouts.create') }}">+ Создать билд</a> @endif</div>
<form class="toolbar" method="GET" action="{{ route('loadouts.index') }}"><label>ПОИСК ПО НАЗВАНИЮ <input type="search" name="q" maxlength="100" value="{{ request('q') }}" placeholder="Название билда..."></label><button class="button" type="submit">Найти →</button></form>
<div class="grid">@forelse($loadouts as $loadout)<a class="card" href="{{ route('loadouts.show', $loadout) }}"><span class="tag">{{ $loadout->category->name }}</span><h3>{{ $loadout->name }} ↗</h3><p>Роль: {{ $loadout->role }}</p><p>{{ Str::limit($loadout->description, 110) }}</p></a>@empty <div class="panel">Билдов пока нет.</div> @endforelse</div><div class="pagination">{{ $loadouts->links() }}</div>
@endsection
