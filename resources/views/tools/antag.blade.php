@extends('layouts.app')
@section('title', 'Антагонист')
@section('content')
<section class="hero compact-hero"><div class="eyebrow">[10] // CHEMICAL FIELD GUIDE</div><h1>Антагонист<span class="accent">_</span></h1><p class="lead">Каталог токсинов и реагентов, которые могут быть полезны в тактических сценариях. Список ограничен выбранным форком.</p></section>
<div class="grid">@forelse($reagents as $reagent)<a class="card" href="{{ route('reagents.show', $reagent) }}"><div class="card-top"><span class="tag">{{ $reagent->category?->name ?? 'Реагент' }}</span><span class="muted">#{{ $reagent->id }}</span></div><h3>{{ $reagent->name }} ↗</h3><p>{{ Str::limit($reagent->description, 150) }}</p>@if($reagent->effects)<div class="effect-chip">{{ Str::limit($reagent->effects, 200) }}</div>@endif</a>@empty<div class="panel">Не найдено подходящих реагентов для выбранного источника.</div>@endforelse</div><div class="pagination">{{ $reagents->links() }}</div>
@endsection
