@extends('layouts.app')
@section('title', 'Химикаты по форкам')
@section('content')
<section class="hero">
    <div class="eyebrow">[01] // ХИМИЧЕСКИЙ ОТДЕЛ · МУЛЬТИФОРК</div>
    <h1>Каталог химикатов<span style="color:var(--green)">_</span></h1>
    <p class="lead">Справочник реагентов и рецептов Space Station 14. Выберите игровой форк, чтобы посмотреть химикаты и реакции именно этой сборки, или используйте общий каталог.</p>
</section>

<div class="active-source-strip">
    <span class="eyebrow">ACTIVE SOURCE</span>
    <strong>{{ $selectedSourceLabel ?? 'Все форки' }}</strong>
    <span class="muted">Источник химии настраивается отдельно от форков экипировки и ботаники.</span>
</div>



<div class="section-heading">
    <div>
        <div class="eyebrow">LOCAL CHEMISTRY DATABASE</div>
        <h2>{{ $selectedSourceLabel ? 'Химикаты // '.$selectedSourceLabel : 'Общий каталог химикатов' }} <span class="muted">// {{ $reagents->total() }}</span></h2>
    </div>
    @if(auth()->check() && auth()->user()->role !== 'guest') <a class="button button-outline" href="{{ route('reagents.create') }}">+ Добавить химикат</a> @endif
</div>

<div class="panel reagent-selection-panel">
    <div class="section-heading"><div><h3>Быстрый выбор реагентов</h3><p class="muted">Отметь реагенты в результатах поиска — список выбранного можно скопировать для рецепта или заметок.</p></div><button type="button" class="button button-outline" id="copy-selected-reagents">Копировать выбранное</button></div>
    <div id="selected-reagents-list" class="selected-reagents-list"><span class="muted">Пока ничего не выбрано.</span></div>
</div>
<form class="toolbar" action="{{ route('reagents.index') }}" method="GET">
    <label>ПОИСК <input name="q" type="search" maxlength="100" value="{{ request('q') }}" placeholder="Название или описание..."></label>
    <label>КАТЕГОРИЯ <select name="category"><option value="">Все категории</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(request('category') == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
    <label>СОРТИРОВКА <select name="sort"><option value="name" @selected(request('sort', 'name') === 'name')>Название</option><option value="effects" @selected(request('sort') === 'effects')>Эффекты</option><option value="category" @selected(request('sort') === 'category')>Категория</option></select></label>
    <label>ПОРЯДОК <select name="direction"><option value="asc" @selected(request('direction', 'asc') === 'asc')>По возрастанию</option><option value="desc" @selected(request('direction') === 'desc')>По убыванию</option></select></label>
    <label class="reagent-unique-toggle"><input type="checkbox" name="unique_only" value="1" @checked(request()->boolean('unique_only'))> Только уникальные реагенты выбранного форка</label>
    <button class="button" type="submit">Найти →</button>
    @if(request()->hasAny(['q', 'category']))<a class="button button-outline" href="{{ route('reagents.index') }}">Сбросить фильтры</a>@endif
</form>

<div class="grid">
    @forelse($reagents as $reagent)
        <article class="card reagent-card reagent-selectable" data-reagent-id="{{ $reagent->id }}" data-reagent-name="{{ $reagent->name }}" data-reagent-proto="{{ $reagent->upstream_id }}">
            <label class="reagent-pick"><input class="reagent-pick-input" type="checkbox" value="{{ $reagent->id }}" data-name="{{ $reagent->name }}" data-proto="{{ $reagent->upstream_id }}"><span>Выбрать реагент</span></label>
            <a class="reagent-card-link" href="{{ route('reagents.show', $reagent) }}">
            <div class="card-top"><span class="tag">{{ $reagent->category->name }}</span><span class="muted">#{{ str_pad($reagent->id, 4, '0', STR_PAD_LEFT) }}</span></div>
            <h3>{{ $reagent->name }} ↗</h3>
            @if($reagent->color)<div class="reagent-color-info" title="Цвет реагента {{ $reagent->color }}"><span class="reagent-color-swatch" style="--reagent-color: {{ preg_match('/^#[0-9a-fA-F]{6}$/', $reagent->color) ? $reagent->color : '#777777' }}"></span><span><strong>Цвет:</strong> {{ \App\Support\GameDataPresenter::colorName($reagent->color) ?? 'не определён' }} <code>{{ $reagent->color }}</code></span></div>@endif
            @if($reagent->source_fork)<small class="source-label"><span class="source-dot"></span>{{ $sources->firstWhere('key', $reagent->source_fork)['label'] ?? $reagent->source_fork }} @if($reagent->source_fork === 'vanilla' && $selectedSource && $selectedSource !== 'vanilla') · базовый реагент @elseif($selectedSource && $reagent->source_fork === $selectedSource) · версия форка @endif @if($reagent->upstream_id) · {{ $reagent->upstream_id }} @endif</small>@endif
            <p>{{ Str::limit($reagent->description, 115) }}</p>
            @php($effectLines = collect(\App\Support\GameDataPresenter::effectLines($reagent->effects))->merge(\App\Support\GameDataPresenter::effectLines($reagent->extra_data['verifiedMechanics'] ?? []))->unique()->values())
            @if($effectLines)
                <div class="reagent-effect-list" aria-label="Эффекты">
                    <span class="eyebrow">ЭФФЕКТЫ</span>
                    @foreach(collect($effectLines)->take(4) as $effectLine)
                        <span class="effect-chip">{{ $effectLine }}</span>
                    @endforeach
                    @if($effectLines->count() > 4)<span class="muted">+ ещё {{ $effectLines->count() - 4 }} — открыть карточку</span>@endif
                </div>
            @elseif($reagent->extra_data['plantEffects'] ?? null)
                <span class="eyebrow">БОТАНИКА // {{ Str::limit(collect($reagent->extra_data['plantEffects'])->map(fn ($v) => is_string($v) ? $v : ($v['summary'] ?? $v['description'] ?? $v['effect'] ?? null))->filter()->implode(' · '), 180) }}</span>
            @elseif($reagent->extra_data['effectTags'] ?? null)
                <span class="eyebrow">ЭФФЕКТ // {{ implode(', ', array_filter($reagent->extra_data['effectTags'], 'is_string')) }}</span>
            @endif
            @if($reagent->extra_data['flavor'] ?? null)<p><strong>Вкус / запах:</strong> {{ is_array($reagent->extra_data['flavor']) ? implode(', ', $reagent->extra_data['flavor']) : $reagent->extra_data['flavor'] }}</p>@endif @if($reagent->extra_data['obtainSources'] ?? null)<p><strong>Где получить:</strong> {{ collect($reagent->extra_data['obtainSources'])->map(fn($v) => is_string($v) ? $v : ($v['name'] ?? $v['description'] ?? null))->filter()->implode(' · ') }}</p>@endif @if($reagent->extra_data['overdose'] ?? null)<p><strong>Передозировка:</strong> {{ is_scalar($reagent->extra_data['overdose']) ? $reagent->extra_data['overdose'] : json_encode($reagent->extra_data['overdose'], JSON_UNESCAPED_UNICODE) }}</p>@endif @if($reagent->extra_data['criticalOverdose'] ?? null)<p><strong>Критическая передозировка:</strong> {{ is_scalar($reagent->extra_data['criticalOverdose']) ? $reagent->extra_data['criticalOverdose'] : json_encode($reagent->extra_data['criticalOverdose'], JSON_UNESCAPED_UNICODE) }}</p>@endif @if($reagent->recipe_data)<span class="tag">РЕЦЕПТ ДОСТУПЕН</span>@endif
            </a>
        </article>
    @empty <div class="panel">Ничего не найдено. Выберите другой форк или измените параметры поиска.</div> @endforelse
</div>
<div class="pagination" id="reagent-pagination">{{ $reagents->links() }}</div><p class="muted" id="reagent-load-status" aria-live="polite" style="text-align:center">Прокрути вниз — каталог подгрузит следующую страницу.</p>
@endsection

@push('scripts')
<script>
(() => {
 const list=document.getElementById('selected-reagents-list'); const copy=document.getElementById('copy-selected-reagents'); const chosen=new Map();
 const results=document.querySelector('.grid'); const pagination=document.getElementById('reagent-pagination'); const status=document.getElementById('reagent-load-status'); let loading=false; let finished=false;
 function render(){list.replaceChildren();if(!chosen.size){const empty=document.createElement('span');empty.className='muted';empty.textContent='Пока ничего не выбрано.';list.append(empty);return;}for(const [id,item] of chosen){const chip=document.createElement('button');chip.type='button';chip.className='tag selected-reagent-chip';chip.textContent=item.name+(item.proto?' · '+item.proto:'')+' ×';chip.title='Убрать из выбранного';chip.addEventListener('click',()=>{chosen.delete(id);const cb=[...document.querySelectorAll('.reagent-pick-input')].find(c=>c.value===id);if(cb)cb.checked=false;render();});list.append(chip);}}
 document.addEventListener('change',e=>{const cb=e.target.closest('.reagent-pick-input');if(!cb)return;if(cb.checked)chosen.set(cb.value,{name:cb.dataset.name||'',proto:cb.dataset.proto||''});else chosen.delete(cb.value);render();});
 copy?.addEventListener('click',async()=>{const text=[...chosen.values()].map(x=>x.name+(x.proto?' ['+x.proto+']':'')).join('\n');if(!text){copy.textContent='Сначала выбери реагенты';return;}try{await navigator.clipboard.writeText(text);copy.textContent='Скопировано ✓';}catch(e){copy.textContent=text;}});
 function nextLink(){return pagination?.querySelector('a[rel="next"]')||pagination?.querySelector('a[aria-label*="Next"],a[aria-label*="Следующ"]');}
 async function loadMore(){if(loading||finished)return;const link=nextLink();if(!link){finished=true;if(status)status.textContent='Все химикаты загружены.';return;}loading=true;if(status)status.textContent='Загружаю ещё химикаты…';try{const response=await fetch(link.href,{headers:{'X-Requested-With':'XMLHttpRequest'}});if(!response.ok)throw new Error('HTTP '+response.status);const html=await response.text();const doc=new DOMParser().parseFromString(html,'text/html');const cards=[...doc.querySelectorAll('.grid .reagent-card')];if(!cards.length){finished=true;if(status)status.textContent='Все химикаты загружены.';return;}cards.forEach(card=>results.append(card));const nextPagination=doc.getElementById('reagent-pagination');if(nextPagination&&pagination)pagination.innerHTML=nextPagination.innerHTML;else finished=true;if(status)status.textContent=finished?'Все химикаты загружены.':'Прокрути вниз для следующей порции.';}catch(error){if(status)status.textContent='Не удалось загрузить следующую порцию. Используй ссылки страниц ниже.';}finally{loading=false;}}
 const observer=new IntersectionObserver(entries=>{if(entries.some(e=>e.isIntersecting))loadMore();},{rootMargin:'700px'});if(status)observer.observe(status);
})();
</script>
@endpush
<style>
.reagent-unique-toggle{display:flex;align-items:center;gap:.45rem}.reagent-unique-toggle input{width:auto}.reagent-selectable{position:relative}.reagent-pick{display:flex;align-items:center;gap:.45rem;font-size:.78rem;color:var(--muted,#9bb);margin-bottom:.55rem;cursor:pointer}.reagent-pick input{width:auto}.reagent-card-link{display:block;color:inherit;text-decoration:none}.reagent-card-link:hover h3{text-decoration:underline}.reagent-selection-panel{margin:1rem 0}.selected-reagents-list{display:flex;flex-wrap:wrap;gap:.5rem}.selected-reagent-chip{cursor:pointer;border:1px solid var(--line,#344)}
</style>

<style>.reagent-effect-list{display:flex;flex-wrap:wrap;align-items:center;gap:.35rem;margin-top:.45rem}.reagent-effect-list .eyebrow{width:100%;margin-bottom:.1rem}.reagent-effect-list .effect-chip{display:inline-flex;max-width:100%;padding:.2rem .45rem;border:1px solid var(--line,#344);border-radius:5px;font-size:.78rem;line-height:1.35;overflow-wrap:anywhere;background:rgba(255,255,255,.025)}</style>
