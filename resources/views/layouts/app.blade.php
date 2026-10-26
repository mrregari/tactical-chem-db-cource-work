<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Каталог') // NT-TACTICAL</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
    <header class="site-header">
        <nav class="shell navigation" aria-label="Основная навигация">
            <a class="brand" href="{{ route('home') }}"><span class="brand-mark">◈</span> NT<span class="brand-light">/TACTICAL</span><small>DATABASE v1.0</small></a>
            <div class="nav-links">
                <a href="{{ route('reagents.index') }}" @if(request()->routeIs('reagents.*', 'home')) aria-current="page" @endif>Химикаты</a>
                <a href="{{ route('reactions.index') }}" @if(request()->routeIs('reactions.*')) aria-current="page" @endif>Калькулятор</a>
                <a href="{{ route('loadouts.index') }}" @if(request()->routeIs('loadouts.*')) aria-current="page" @endif>Билды</a>
                <a href="{{ route('equipment-items.index') }}" @if(request()->routeIs('equipment-items.*')) aria-current="page" @endif>Каталог предметов</a>
                <a href="{{ route('tools.botany') }}" @if(request()->routeIs('tools.botany')) aria-current="page" @endif>Ботаника</a>
                <a href="{{ route('tools.medbay') }}" @if(request()->routeIs('tools.medbay')) aria-current="page" @endif>Медбей</a>
                <a href="{{ route('tools.maps') }}" @if(request()->routeIs('tools.maps')) aria-current="page" @endif>Карты</a>
                <a href="{{ route('tools.ordnance') }}" @if(request()->routeIs('tools.ordnance')) aria-current="page" @endif>Взрывчатка</a>
                @auth
                    @if(in_array(auth()->user()->role, ['user', 'admin'], true)) <a href="{{ route('favorites.index') }}">Избранное</a> @endif
                    @if(auth()->user()->isAdmin()) <a href="{{ route('admin.index') }}">Модерация</a> @endif
                @endauth
            </div>
            <div class="nav-account">
                @auth
                    <span class="account-name">{{ auth()->user()->name }}</span>
                    <form method="POST" action="{{ route('logout') }}">@csrf <button class="link-button" type="submit">Выход ↗</button></form>
                @else
                    <a href="{{ route('login') }}">Вход</a>
                    <a class="button button-small" href="{{ route('register') }}">Регистрация</a>
                @endauth
            </div>
        </nav>
    </header>
    <main class="shell main-content">
        <div class="system-line"><span>● СИСТЕМА ОНЛАЙН</span><span>ДОСТУП // {{ auth()->user()?->role ?? 'guest' }}</span></div>
        @if(session('success')) <div class="notice" role="status">✓ {{ session('success') }}</div> @endif
        @if($errors->any()) <div class="notice notice-error" role="alert">{{ $errors->first() }}</div> @endif
        <div class="app-layout">
            <section class="page-content">@yield('content')</section>
            <aside class="source-sidebar" aria-label="Выбор игрового форка">
                <div class="sidebar-kicker">SOURCE CONTROL // 01</div>
                <h2>Игровой форк</h2>
                <p class="sidebar-copy">Фильтр отдельно для раздела: {{ $sourceContextLabel ?? 'каталог' }}.</p>
                <form action="{{ route('source.update') }}" method="POST" class="source-picker">@csrf<input type="hidden" name="context" value="{{ $context ?? 'chemistry' }}">
                    <label for="source-select">Текущий источник</label>
                    <select id="source-select" name="source" onchange="this.form.submit()">
                        <option value="all" @selected(!$selectedSource)>Все форки / общий каталог</option>
                        @foreach($sources as $source)
                            <option value="{{ $source['key'] }}" @selected($selectedSource === $source['key'])>{{ $source['label'] }} ({{ $source['count'] ?? $source['reagents'] }})</option>
                        @endforeach
                    </select>
                    <button class="button button-small" type="submit">Применить форк</button>
                </form>
                <div class="selected-fork-card" style="--fork-color: {{ $sources->firstWhere('key', $selectedSource)['color'] ?? '#a6e073' }}">
                    <span class="fork-indicator"></span>
                    <strong>{{ $selectedSourceLabel ?? 'Все игровые форки' }}</strong>
                    @if($selectedSource)
                        <span>{{ number_format($sources->firstWhere('key', $selectedSource)['count'] ?? 0) }} записей</span>
                        <span>{{ number_format($sources->firstWhere('key', $selectedSource)['reactions'] ?? 0) }} {{ ($context ?? 'chemistry') === 'equipment' ? 'предметов' : 'реакций' }}</span>
                    @else
                        <span>Объединённая база</span>
                        <span>Общий поиск по источникам</span>
                    @endif
                </div>
                <div class="sidebar-tools">
                    <div class="sidebar-kicker">TOOLS // 02</div>
                    <a href="{{ route('reagents.index') }}">◈ Каталог химикатов</a>
                    <a href="{{ route('reactions.index') }}">⚗ Калькулятор рецептов</a>
                    <a href="{{ route('equipment-items.index') }}">▣ Каталог брони и оружия</a>
                    <a href="{{ route('loadouts.index') }}">⌘ Сохранённые билды</a>
                    <a href="{{ route('tools.trees') }}">⌘ Деревья синтеза</a>
                    <a href="{{ route('tools.botany') }}">♧ Ботаника</a>
                    <a href="{{ route('tools.medbay') }}">＋ What Heals? / Медбей</a>
                    <a href="{{ route('tools.antag') }}">☠ Антагонист</a>
                    <a href="{{ route('tools.maps') }}">▦ Карта предметов</a>
                    <a href="{{ route('tools.ordnance') }}">◉ Ордна́нс / взрывчатка</a>
                </div>
                <div class="sidebar-foot">Локальная база · фильтр хранится отдельно для каждой вкладки</div>
            </aside>
        </div>
    </main>
    <footer class="site-footer"><div class="shell">NANOTRASEN // ТАКТИЧЕСКИЙ АРХИВ <span>ДАННЫЕ ДЛЯ ОЗНАКОМЛЕНИЯ</span></div></footer>
    @stack('scripts')
</body>
</html>
