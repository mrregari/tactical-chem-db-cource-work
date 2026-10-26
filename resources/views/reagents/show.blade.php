@extends('layouts.app')
@section('title', $reagent->name)
@section('content')
<section class="hero"><a href="{{ route('reagents.index') }}">← Все химикаты</a><div class="eyebrow" style="margin-top:25px">{{ $reagent->category->name }} // #{{ $reagent->id }}</div><h1>{{ $reagent->name }}</h1><p class="muted">@if($reagent->upstream_id) Источник: {{ $reagent->source_fork }} / {{ $reagent->upstream_id }} @else Автор: {{ $reagent->user->name }} · Статус: {{ $reagent->status }} @endif</p></section>
<div class="actions">@if(auth()->check() && auth()->user()->role !== 'guest') @if($reagent->status === 'published')<form method="POST" action="{{ route('favorites.toggle', ['reagent', $reagent->id]) }}">@csrf <button class="button button-outline" type="submit">☆ В избранное / убрать</button></form>@endif @endif <a class="button button-outline" href="{{ route('reactions.index', ['reagent' => $reagent->id]) }}">Калькулятор →</a></div>
<section class="panel"><h2>Описание и действие</h2><p class="detail-body">{{ $reagent->description ?: 'Описание отсутствует в источнике.' }}</p>@php($effectLines = collect(\App\Support\GameDataPresenter::effectLines($reagent->effects))->merge(\App\Support\GameDataPresenter::effectLines($reagent->extra_data['verifiedMechanics'] ?? []))->unique()->values())
@if($effectLines || !empty($reagent->extra_data['plantEffects']) || !empty($reagent->extra_data['effectTags']))<div class="eyebrow">ЭФФЕКТЫ / ДЕЙСТВИЕ</div>
@if($reagent->effects)
    <ul class="reagent-effects-detail">@foreach(collect(explode(';', $reagent->effects))->map(fn ($v) => trim($v))->filter() as $effectLine)<li>{{ $effectLine }}</li>@endforeach</ul>
@elseif($structuredEffects->isNotEmpty())<ul class="reagent-effects-detail">@foreach($structuredEffects as $effect)<li>{{ $effect }}</li>@endforeach</ul>@endif
@if(!empty($reagent->extra_data['plantEffects']))<h3>Влияние на растения</h3><ul>@foreach($reagent->extra_data['plantEffects'] as $effect)@php($effectText = is_string($effect) ? $effect : ($effect['summary'] ?? $effect['description'] ?? $effect['effect'] ?? $effect['name'] ?? null))@if($effectText)<li>{{ $effectText }}</li>@endif @endforeach</ul>@endif
@if(!empty($reagent->extra_data['effectTags']))<p><strong>Категории эффектов:</strong> {{ implode(', ', array_map(fn ($v) => is_string($v) ? str_replace(['_', '-'], ' ', $v) : '', $reagent->extra_data['effectTags'])) }}</p>@endif @endif @if($reagent->extra_data['physicalDesc'] ?? null)<div class="eyebrow">ФИЗИЧЕСКОЕ ОПИСАНИЕ</div><p class="detail-body">{{ $reagent->extra_data['physicalDesc'] }}</p>@endif @if($reagent->color)<div class="reagent-color-detail"><span class="reagent-color-swatch reagent-color-swatch-large" style="--reagent-color: {{ preg_match('/^#[0-9a-fA-F]{6}$/', $reagent->color) ? $reagent->color : '#777777' }}"></span><div><strong>Цвет реагента: {{ \App\Support\GameDataPresenter::colorName($reagent->color) ?? 'не определён' }}</strong><div class="muted">{{ $reagent->color }} — визуальный цвет из прототипа игры.</div></div></div>@endif
@if($reagent->extra_data)
<div class="eyebrow">ИГРОВЫЕ СВОЙСТВА</div>
<dl class="chem-properties">
@foreach($reagent->extra_data as $key => $value)
    @if(!in_array($key, ['verifiedMechanics', 'plantEffects', 'effectTags', 'physicalDesc'], true) && $value !== null && $value !== '' && $value !== [])
        <div><dt>{{ \App\Support\GameDataPresenter::label((string) $key) }}</dt><dd>{{ \App\Support\GameDataPresenter::readableValue($value) }}</dd></div>
    @endif
@endforeach
</dl>
@endif</section>
@if($reagent->recipe_data)
<section class="panel"><div class="section-heading"><h2>Рецепт синтеза</h2><span class="tag">ДАННЫЕ ИЗ DATA.JSON</span></div>
<p class="muted">Рецепт из исходного каталога. Если для выбранного форка он помечен как изменённый, ниже будет указано это отличие.</p>
<ul class="list">@foreach(($reagent->recipe_data['reactants'] ?? []) as $protoId => $reactant)<li>{{ $recipeIngredientNames[$protoId] ?? $protoId }} — {{ is_array($reactant) ? ($reactant['amount'] ?? 1) : $reactant }} ед.@if(is_array($reactant) && !empty($reactant['catalyst'])) <span class="tag">КАТАЛИЗАТОР</span>@endif</li>@endforeach</ul>
@if(isset($reagent->recipe_data['minTemp']) && $reagent->recipe_data['minTemp'] !== null)<p class="muted">Минимальная температура: {{ $reagent->recipe_data['minTemp'] }} K</p>@endif
@if(isset($reagent->recipe_data['maxTemp']) && $reagent->recipe_data['maxTemp'] !== null)<p class="muted">Максимальная температура: {{ $reagent->recipe_data['maxTemp'] }} K</p>@endif
@if(!empty($reagent->recipe_data['mixer']))<p class="muted">Требуемый миксер: {{ implode(', ', $reagent->recipe_data['mixer']) }}</p>@endif
</section>
@endif
<section class="panel"><div class="section-heading"><h2>Рецепты и различия форков</h2>@if(auth()->check() && auth()->user()->role !== 'guest') <a href="{{ route('reactions.create') }}">+ Предложить рецепт</a> @endif</div>
@forelse($reagent->reactions as $reaction)
    <h3>{{ $reaction->name }}</h3><p class="muted">{{ $reaction->description }}</p>
    @php($forkState = $selectedSource ? ($reaction->fork_status[$selectedSource] ?? null) : null)
    @if($forkState === 'modified')<p class="tag">РЕЦЕПТ ИЗМЕНЁН В {{ strtoupper($selectedSource) }}</p>@if($reaction->fork_notes[$selectedSource] ?? null)<p>{{ $reaction->fork_notes[$selectedSource] }}</p>@endif @elseif($forkState === 'blocked')<p class="tag">РЕЦЕПТ НЕДОСТУПЕН В ЭТОМ ФОРКЕ</p>@endif
    <ul class="list">@foreach($reaction->ingredients as $id => $amount)<li>{{ $ingredientNames[$id] ?? 'Неизвестный реагент' }} — {{ $amount }} ед. на {{ $reaction->product_amount ?: 1 }} ед. продукта</li>@endforeach</ul>
@empty @if(!$reagent->recipe_data)<p class="muted">В импортированном источнике нет данных о рецепте этого реагента.</p>@endif @endforelse</section>
<section class="panel"><h2>Комментарии // {{ $reagent->comments->count() }}</h2>@foreach($reagent->comments as $comment)<div class="comment"><small>{{ $comment->user->name }} · {{ $comment->created_at->format('d.m.Y H:i') }}</small><p>{{ $comment->content }}</p></div>@endforeach
@if($reagent->status === 'published') @if(auth()->check() && auth()->user()->role !== 'guest') <form method="POST" action="{{ route('comments.store') }}">@csrf <input type="hidden" name="type" value="reagent"><input type="hidden" name="id" value="{{ $reagent->id }}"><label>Ваш комментарий<textarea name="content" required maxlength="2000"></textarea></label><button class="button" type="submit">Отправить →</button></form>@else <p class="muted">Комментарии доступны зарегистрированным пользователям.</p>@endif @endif</section>
@endsection

<style>.chem-properties{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.7rem;margin:1rem 0}.chem-properties>div{padding:.75rem;border:1px solid var(--line,#344);border-radius:8px;background:rgba(255,255,255,.025)}.chem-properties dt{font-size:.78rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted,#9bb)}.chem-properties dd{margin:.35rem 0 0;overflow-wrap:anywhere;line-height:1.5}</style>

<style>.reagent-effects-detail{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:.45rem;padding-left:1.2rem}.reagent-effects-detail li{padding:.45rem .6rem;border:1px solid var(--line,#344);border-radius:6px;overflow-wrap:anywhere;line-height:1.45}</style>

<style>.reagent-effects-detail{list-style:none;padding:0;display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:.55rem;margin:.8rem 0}.reagent-effects-detail li{border:1px solid var(--line,#344);border-left:3px solid var(--green,#8bc);border-radius:7px;padding:.65rem .8rem;background:rgba(255,255,255,.025);line-height:1.45;overflow-wrap:anywhere}.reagent-effect-list .effect-chip{border-radius:6px}</style>
