<!doctype html>
<html lang="ru">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php echo $__env->yieldContent('title', 'Каталог'); ?> // NT-TACTICAL</title>
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>
</head>
<body>
    <header class="site-header">
        <nav class="shell navigation" aria-label="Основная навигация">
            <a class="brand" href="<?php echo e(route('home')); ?>"><span class="brand-mark">◈</span> NT<span class="brand-light">/TACTICAL</span><small>DATABASE v1.0</small></a>
            <div class="nav-links">
                <a href="<?php echo e(route('reagents.index')); ?>" <?php if(request()->routeIs('reagents.*', 'home')): ?> aria-current="page" <?php endif; ?>>Химикаты</a>
                <a href="<?php echo e(route('reactions.index')); ?>" <?php if(request()->routeIs('reactions.*')): ?> aria-current="page" <?php endif; ?>>Калькулятор</a>
                <a href="<?php echo e(route('loadouts.index')); ?>" <?php if(request()->routeIs('loadouts.*')): ?> aria-current="page" <?php endif; ?>>Билды</a>
                <a href="<?php echo e(route('equipment-items.index')); ?>" <?php if(request()->routeIs('equipment-items.*')): ?> aria-current="page" <?php endif; ?>>Каталог предметов</a>
                <a href="<?php echo e(route('tools.botany')); ?>" <?php if(request()->routeIs('tools.botany')): ?> aria-current="page" <?php endif; ?>>Ботаника</a>
                <a href="<?php echo e(route('tools.medbay')); ?>" <?php if(request()->routeIs('tools.medbay')): ?> aria-current="page" <?php endif; ?>>Медбей</a>
                <a href="<?php echo e(route('tools.maps')); ?>" <?php if(request()->routeIs('tools.maps')): ?> aria-current="page" <?php endif; ?>>Карты</a>
                <a href="<?php echo e(route('tools.ordnance')); ?>" <?php if(request()->routeIs('tools.ordnance')): ?> aria-current="page" <?php endif; ?>>Взрывчатка</a>
                <?php if(auth()->guard()->check()): ?>
                    <?php if(in_array(auth()->user()->role, ['user', 'admin'], true)): ?> <a href="<?php echo e(route('favorites.index')); ?>">Избранное</a> <?php endif; ?>
                    <?php if(auth()->user()->isAdmin()): ?> <a href="<?php echo e(route('admin.index')); ?>">Модерация</a> <?php endif; ?>
                <?php endif; ?>
            </div>
            <div class="nav-account">
                <?php if(auth()->guard()->check()): ?>
                    <span class="account-name"><?php echo e(auth()->user()->name); ?></span>
                    <form method="POST" action="<?php echo e(route('logout')); ?>"><?php echo csrf_field(); ?> <button class="link-button" type="submit">Выход ↗</button></form>
                <?php else: ?>
                    <a href="<?php echo e(route('login')); ?>">Вход</a>
                    <a class="button button-small" href="<?php echo e(route('register')); ?>">Регистрация</a>
                <?php endif; ?>
            </div>
        </nav>
    </header>
    <main class="shell main-content">
        <div class="system-line"><span>● СИСТЕМА ОНЛАЙН</span><span>ДОСТУП // <?php echo e(auth()->user()?->role ?? 'guest'); ?></span></div>
        <?php if(session('success')): ?> <div class="notice" role="status">✓ <?php echo e(session('success')); ?></div> <?php endif; ?>
        <?php if($errors->any()): ?> <div class="notice notice-error" role="alert"><?php echo e($errors->first()); ?></div> <?php endif; ?>
        <div class="app-layout">
            <section class="page-content"><?php echo $__env->yieldContent('content'); ?></section>
            <aside class="source-sidebar" aria-label="Выбор игрового форка">
                <div class="sidebar-kicker">SOURCE CONTROL // 01</div>
                <h2>Игровой форк</h2>
                <p class="sidebar-copy">Фильтр отдельно для раздела: <?php echo e($sourceContextLabel ?? 'каталог'); ?>.</p>
                <form action="<?php echo e(route('source.update')); ?>" method="POST" class="source-picker"><?php echo csrf_field(); ?><input type="hidden" name="context" value="<?php echo e($context ?? 'chemistry'); ?>">
                    <label for="source-select">Текущий источник</label>
                    <select id="source-select" name="source" onchange="this.form.submit()">
                        <option value="all" <?php if(!$selectedSource): echo 'selected'; endif; ?>>Все форки / общий каталог</option>
                        <?php $__currentLoopData = $sources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $source): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <option value="<?php echo e($source['key']); ?>" <?php if($selectedSource === $source['key']): echo 'selected'; endif; ?>><?php echo e($source['label']); ?> (<?php echo e($source['count'] ?? $source['reagents']); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                    <button class="button button-small" type="submit">Применить форк</button>
                </form>
                <div class="selected-fork-card" style="--fork-color: <?php echo e($sources->firstWhere('key', $selectedSource)['color'] ?? '#a6e073'); ?>">
                    <span class="fork-indicator"></span>
                    <strong><?php echo e($selectedSourceLabel ?? 'Все игровые форки'); ?></strong>
                    <?php if($selectedSource): ?>
                        <span><?php echo e(number_format($sources->firstWhere('key', $selectedSource)['count'] ?? 0)); ?> записей</span>
                        <span><?php echo e(number_format($sources->firstWhere('key', $selectedSource)['reactions'] ?? 0)); ?> <?php echo e(($context ?? 'chemistry') === 'equipment' ? 'предметов' : 'реакций'); ?></span>
                    <?php else: ?>
                        <span>Объединённая база</span>
                        <span>Общий поиск по источникам</span>
                    <?php endif; ?>
                </div>
                <div class="sidebar-tools">
                    <div class="sidebar-kicker">TOOLS // 02</div>
                    <a href="<?php echo e(route('reagents.index')); ?>">◈ Каталог химикатов</a>
                    <a href="<?php echo e(route('reactions.index')); ?>">⚗ Калькулятор рецептов</a>
                    <a href="<?php echo e(route('equipment-items.index')); ?>">▣ Каталог брони и оружия</a>
                    <a href="<?php echo e(route('loadouts.index')); ?>">⌘ Сохранённые билды</a>
                    <a href="<?php echo e(route('tools.trees')); ?>">⌘ Деревья синтеза</a>
                    <a href="<?php echo e(route('tools.botany')); ?>">♧ Ботаника</a>
                    <a href="<?php echo e(route('tools.medbay')); ?>">＋ What Heals? / Медбей</a>
                    <a href="<?php echo e(route('tools.antag')); ?>">☠ Антагонист</a>
                    <a href="<?php echo e(route('tools.maps')); ?>">▦ Карта предметов</a>
                    <a href="<?php echo e(route('tools.ordnance')); ?>">◉ Ордна́нс / взрывчатка</a>
                </div>
                <div class="sidebar-foot">Локальная база · фильтр хранится отдельно для каждой вкладки</div>
            </aside>
        </div>
    </main>
    <footer class="site-footer"><div class="shell">NANOTRASEN // ТАКТИЧЕСКИЙ АРХИВ <span>ДАННЫЕ ДЛЯ ОЗНАКОМЛЕНИЯ</span></div></footer>
    <?php echo $__env->yieldPushContent('scripts'); ?>
</body>
</html>
<?php /**PATH /var/home/regari/ss14-tactical-db/resources/views/layouts/app.blade.php ENDPATH**/ ?>