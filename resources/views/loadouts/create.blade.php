@extends('layouts.app')
@section('title', 'Конструктор билда')
@section('content')
<section class="hero">
    <div class="eyebrow">ЗАЯВКА // СНАРЯЖЕНИЕ</div>
    <h1>Конструктор билдов_</h1>
    <p class="lead">Соберите комплект из реальных прототипов игры. Поиск учитывает выбранный справа форк; в карточке предмета видны ID прототипа и источник.</p>
    <div class="stats-row"><span class="tag">Каталог: {{ number_format($equipmentCount) }} предметов</span><span class="tag">До 20 позиций</span><span class="tag">SS14 YAML</span></div>
</section>
<form class="panel form-panel" action="{{ route('loadouts.store') }}" method="POST" id="loadout-form">
    @csrf
    <label>Название билда <input name="name" value="{{ old('name') }}" required maxlength="255" placeholder="Например: Линейный стрелок"></label>
    <label>Роль / специализация <input name="role" value="{{ old('role') }}" placeholder="Стрелок, медик, инженер..." required maxlength="100"></label>
    <label>Категория <select name="category_id" required><option value="">Выберите категорию</option>@foreach($categories as $category)<option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>@endforeach</select></label>
    <label>Описание и тактика <textarea name="description" maxlength="5000" placeholder="Назначение комплекта, сильные стороны, ограничения...">{{ old('description') }}</textarea></label>

    <div class="section-heading"><h2>Комплектация</h2><span class="muted">Выбирай из каталога</span></div>
    <p class="muted">Сначала найди предмет по названию или Prototype ID, выбери его, затем укажи слот и количество. Если каталог ещё не импортирован, запусти <code>php artisan equipment:sync</code>.</p>
    <div id="loadout-slots" class="loadout-slot-list">
        @for($index = 0; $index < 6; $index++)
            <div class="panel loadout-slot" data-slot-index="{{ $index }}">
                <div class="eyebrow">ПОЗИЦИЯ {{ str_pad((string) ($index + 1), 2, '0', STR_PAD_LEFT) }}</div>
                <label>Поиск предмета
                    <input type="search" class="equipment-search" autocomplete="off" placeholder="Начни вводить название или ID…" maxlength="100">
                </label>
                <label>Категория каталога
                    <select class="equipment-category"><option value="">Все категории</option><option value="armor">Броня / одежда</option><option value="weapon">Оружие</option><option value="melee">Ближний бой</option><option value="ammo">Боеприпасы</option><option value="grenade">Гранаты</option><option value="explosive">Взрывчатка</option><option value="medical">Медицина</option><option value="storage">Хранение</option><option value="tool">Инструменты</option><option value="shield">Щиты</option><option value="implant">Импланты</option><option value="equipment">Прочее</option></select>
                </label>
                <div class="equipment-results" role="listbox" aria-label="Результаты поиска"></div>
                <div class="equipment-selected muted">Предмет не выбран</div>
                <input type="hidden" name="items[{{ $index }}][equipment_item_id]" class="equipment-item-id">
                <input type="hidden" name="items[{{ $index }}][item_name]" class="equipment-item-name">
                <label>Слот комплекта
                    <select name="items[{{ $index }}][slot]" class="equipment-slot"><option value="head">Голова</option><option value="eyes">Глаза</option><option value="mask">Маска</option><option value="outer clothing">Верхняя одежда / броня</option><option value="jumpsuit">Комбинезон</option><option value="back">Спина / рюкзак</option><option value="belt">Пояс</option><option value="gloves">Перчатки</option><option value="shoes">Обувь</option><option value="pocket">Карман</option><option value="weapon">Основное оружие</option><option value="sidearm">Дополнительное оружие</option><option value="ammo">Боеприпасы</option><option value="grenade">Граната</option><option value="utility">Инструмент / другое</option></select>
                </label>
                <div class="form-row"><label>Количество <input name="items[{{ $index }}][quantity]" type="number" min="1" max="100" value="1"></label><label>Заметка <input name="items[{{ $index }}][notes]" maxlength="1000" placeholder="Опционально"></label></div>
                <button class="button button-outline clear-equipment" type="button">Очистить позицию</button>
            </div>
        @endfor
    </div>
    <div class="form-actions"><button class="button button-outline" type="button" id="add-loadout-slot">+ Добавить позицию</button><button class="button" type="submit">Отправить билд на модерацию →</button></div>
</form>

<style>
.loadout-slot-list{display:grid;gap:1rem}.loadout-slot{border:1px solid var(--line,#344);padding:1rem}.equipment-results{display:grid;gap:.4rem;margin:.4rem 0}.equipment-result{display:flex;justify-content:space-between;gap:1rem;text-align:left;padding:.7rem;border:1px solid var(--line,#344);background:rgba(255,255,255,.025);color:inherit;border-radius:8px;cursor:pointer}.equipment-result:hover,.equipment-result:focus{border-color:var(--accent,#a6e073)}.equipment-result small{display:block;opacity:.7;overflow-wrap:anywhere}.equipment-selected{padding:.55rem;border-left:2px solid var(--accent,#a6e073);margin:.5rem 0}.form-row{display:grid;grid-template-columns:minmax(100px,.5fr) minmax(160px,1.5fr);gap:.8rem}.form-actions{display:flex;gap:.7rem;flex-wrap:wrap;margin-top:1rem}.equipment-result-category{white-space:nowrap;font-size:.8rem;opacity:.8}.loadout-slot[hidden]{display:none}@media(max-width:600px){.form-row{grid-template-columns:1fr}.equipment-result{flex-direction:column}}
</style>
<script>
(() => {
    const endpoint = @json(route('equipment-items.search'));
    const slots = document.getElementById('loadout-slots');
    const addButton = document.getElementById('add-loadout-slot');
    const form = document.getElementById('loadout-form');
    let slotCount = slots.querySelectorAll('.loadout-slot').length;
    const maxSlots = 20;
    const labels = {armor:'Броня',weapon:'Оружие',melee:'Ближний бой',ammo:'Боеприпасы',grenade:'Гранаты',explosive:'Взрывчатка',medical:'Медицина',storage:'Хранение',tool:'Инструменты',shield:'Щиты',implant:'Импланты',equipment:'Прочее'};

    function makeResult(item, slot) {
        const button = document.createElement('button'); button.type = 'button'; button.className = 'equipment-result'; button.setAttribute('role','option');
        const left = document.createElement('span'); const title = document.createElement('strong'); title.textContent = item.name; const meta = document.createElement('small'); meta.textContent = `${item.prototype_id} · ${item.source_name || item.source_fork}`; left.append(title, meta);
        const right = document.createElement('span'); right.className = 'equipment-result-category'; right.textContent = labels[item.category] || item.category; button.append(left,right);
        button.addEventListener('click', () => {
            slot.querySelector('.equipment-item-id').value = item.id;
            slot.querySelector('.equipment-item-name').value = item.name;
            slot.querySelector('.equipment-search').value = item.name;
            slot.querySelector('.equipment-selected').textContent = `${item.name} · ${item.prototype_id} · источник: ${item.source_name || item.source_fork}`;
            slot.querySelector('.equipment-results').replaceChildren();
            const sourceLink = item.source_url;
            if (sourceLink) {
                const a = document.createElement('a'); a.href = sourceLink; a.target = '_blank'; a.rel = 'noopener'; a.textContent = 'Открыть исходный YAML ↗'; a.className = 'muted'; slot.querySelector('.equipment-selected').append(' · ', a);
            }
        });
        return button;
    }

    async function search(slot) {
        const q = slot.querySelector('.equipment-search').value.trim(); const category = slot.querySelector('.equipment-category').value; const results = slot.querySelector('.equipment-results');
        if (q.length < 2 && !category) { results.replaceChildren(); return; }
        results.textContent = 'Поиск по локальному каталогу…';
        try {
            const url = new URL(endpoint, window.location.origin); if (q) url.searchParams.set('q',q); if (category) url.searchParams.set('category',category);
            const response = await fetch(url, {headers:{'Accept':'application/json'}}); if (!response.ok) throw new Error('HTTP '+response.status);
            const data = await response.json(); results.replaceChildren();
            if (!data.items.length) { results.textContent = 'Ничего не найдено. Проверь выбранный форк или синхронизируй каталог.'; return; }
            data.items.forEach(item => results.append(makeResult(item,slot)));
        } catch (error) { results.textContent = 'Не удалось получить каталог. Проверь миграции и выполни equipment:sync.'; }
    }
    function wire(slot) {
        let timer;
        slot.querySelector('.equipment-search').addEventListener('input', () => { slot.querySelector('.equipment-item-id').value=''; slot.querySelector('.equipment-item-name').value=''; slot.querySelector('.equipment-selected').textContent='Предмет не выбран'; clearTimeout(timer); timer=setTimeout(()=>search(slot),250); });
        slot.querySelector('.equipment-category').addEventListener('change',()=>search(slot));
        slot.querySelector('.clear-equipment').addEventListener('click',()=>{slot.querySelector('.equipment-search').value='';slot.querySelector('.equipment-item-id').value='';slot.querySelector('.equipment-item-name').value='';slot.querySelector('.equipment-results').replaceChildren();slot.querySelector('.equipment-selected').textContent='Предмет не выбран';});
    }
    slots.querySelectorAll('.loadout-slot').forEach(wire);
    addButton.addEventListener('click',()=>{
        if(slotCount>=maxSlots)return;
        const source=slots.querySelector('.loadout-slot'); const clone=source.cloneNode(true); const index=slotCount++;
        clone.dataset.slotIndex=index; clone.querySelector('.eyebrow').textContent='ПОЗИЦИЯ '+String(index+1).padStart(2,'0');
        clone.querySelectorAll('[name]').forEach(input=>input.name=input.name.replace(/items\[\d+\]/,`items[${index}]`));
        clone.querySelectorAll('input').forEach(input=>{if(input.type==='hidden')input.value='';else if(input.type==='number')input.value='1';else input.value='';});
        clone.querySelector('.equipment-results').replaceChildren();clone.querySelector('.equipment-selected').textContent='Предмет не выбран';clone.querySelector('.equipment-category').value='';clone.querySelector('.equipment-slot').value='head';
        slots.append(clone);wire(clone);if(slotCount>=maxSlots)addButton.disabled=true;
    });
    form.addEventListener('submit',(event)=>{
        const selected=[...slots.querySelectorAll('.loadout-slot')].filter(slot=>slot.querySelector('.equipment-item-id').value);
        if(!selected.length){event.preventDefault();alert('Выбери хотя бы один предмет из каталога.');return;}
        // Empty positions are disabled so they are excluded from validation and submission.
        slots.querySelectorAll('.loadout-slot').forEach(slot=>{if(!slot.querySelector('.equipment-item-id').value)slot.querySelectorAll('[name]').forEach(input=>input.disabled=true);});
    });
})();
</script>
@endsection
