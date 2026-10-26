@extends('layouts.app')
@section('title', 'Карта предметов')
@section('content')
<section class="hero compact-hero"><div class="eyebrow">[08] // STATION CARTOGRAPHY</div><h1>Карта предметов<span class="accent">_</span></h1><p class="lead">Инструмент поиска мест появления предметов на схемах станций из оригинальной NanoTrasen ChemDB.</p></section>
<div class="feature-banner"><div class="feature-icon">▦</div><div><div class="eyebrow">MAP DATASET REQUIRED</div><h2>Схемы карт не входят в импорт химии</h2><p>Для локальной карты нужны сгенерированные файлы `maps/index.json`, схемы PNG и таблицы мест появления предметов. Они не содержатся в текущем Laravel-архиве, поэтому я не подставляю выдуманные координаты.</p><a class="button" href="https://mikameo.github.io/space-station-recipes/" target="_blank" rel="noopener noreferrer">Открыть оригинальную карту ↗</a></div></div>
<div class="panel"><h3>Что будет доступно после добавления картографических данных</h3><div class="feature-list"><span>Поиск предметов по имени</span><span>Отображение места на схеме станции</span><span>Группировка по ближайшему маяку</span><span>Фильтр по форку и карте</span><span>Список лута и цен, если данные есть для карты</span></div></div>
@endsection
