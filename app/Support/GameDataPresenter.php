<?php

namespace App\Support;

/** Turns raw RobustToolbox/YAML values into readable Russian summaries without discarding source data. */
final class GameDataPresenter
{
    private const LABELS = [
        'Bloodstream' => 'В кровеносной системе', 'bloodstream' => 'В кровеносной системе',
        'speed' => 'скорость', 'walk' => 'ходьбы', 'sprint' => 'бега', 'movementSpeed' => 'скорость передвижения',
        'Armor' => 'Броня', 'Gun' => 'Огнестрельное оружие', 'MeleeWeapon' => 'Оружие ближнего боя', 'Item' => 'Предмет', 'Sprite' => 'Изображение', 'Clothing' => 'Одежда', 'Storage' => 'Хранилище', 'BallisticAmmoProvider' => 'Подача боеприпасов', 'Damage' => 'Урон', 'damage' => 'урон', 'modifiers' => 'модификаторы защиты',
        'Blunt' => 'тупой урон', 'Slash' => 'рубящий урон', 'Piercing' => 'колющий урон',
        'Heat' => 'термический урон', 'Cold' => 'холодовой урон', 'Shock' => 'электрический урон',
        'Caustic' => 'едкий урон', 'Structural' => 'структурный урон', 'Radiation' => 'радиационный урон',
        'Capacity' => 'вместимость', 'capacity' => 'вместимость', 'availableModes' => 'режимы стрельбы',
        'shotsPerBurst' => 'выстрелов в очереди', 'firerate' => 'скорострельность', 'reloadtime' => 'время перезарядки',
        'penetration' => 'пробитие', 'resistance' => 'сопротивление', 'temperature' => 'температура',
        'metabolism' => 'метаболизм', 'metabolismRate' => 'скорость метаболизма', 'duration' => 'длительность',
        'stamina' => 'выносливость', 'heal' => 'лечение', 'healing' => 'лечение', 'status' => 'эффект состояния',
        'state' => 'состояние спрайта', 'sprite' => 'спрайт', 'slots' => 'слоты экипировки', 'size' => 'размер предмета',
        'weight' => 'вес', 'volume' => 'объём', 'range' => 'дальность', 'delay' => 'задержка',
        'Enabled' => 'включено', 'enabled' => 'включено', 'true' => 'да', 'false' => 'нет',
        'group' => 'Группа', 'flavor' => 'Вкус / запах', 'physicalDesc' => 'Физическое описание',
        'isBase' => 'Базовый реагент', 'isDispenser' => 'Доступен в раздатчике', 'effectTags' => 'Категории эффектов',
        'metabolismPaths' => 'Пути метаболизма', 'overdose' => 'Порог передозировки',
        'criticalOverdose' => 'Критическая передозировка', 'boilingPoint' => 'Температура кипения',
        'meltingPoint' => 'Температура плавления', 'metabolismRate' => 'Скорость метаболизма',
        'obtainSources' => 'Источники получения', 'accessibility' => 'Доступность',
        'verifiedMechanics' => 'Подтверждённые игровые эффекты', 'plantEffects' => 'Влияние на растения',
        'antagScore' => 'Тактическая оценка', 'antagTags' => 'Тактические категории',
        'antagTips' => 'Тактические советы', 'antagTipsSources' => 'Источники тактических советов',
        'summary' => 'Описание', 'description' => 'Описание', 'effect' => 'Эффект', 'text' => 'Текст',
        'label' => 'Метка', 'kind' => 'Тип', 'tone' => 'Характер эффекта', 'amount' => 'Количество',
        'probability' => 'Вероятность', 'source' => 'Источник', 'id' => 'Идентификатор',
        // Common gameplay properties across SS14 forks.
        'walkSpeed' => 'скорость ходьбы', 'sprintSpeed' => 'скорость бега', 'walkSpeedModifier' => 'множитель скорости ходьбы', 'sprintSpeedModifier' => 'множитель скорости бега',
        'damageModifierSet' => 'набор защиты от урона', 'damageContainer' => 'тип защиты от урона', 'damageReduction' => 'снижение урона',
        'armor' => 'броня', 'armorClass' => 'класс брони', 'equipSound' => 'звук надевания', 'unequipSound' => 'звук снятия',
        'equipDelay' => 'время надевания', 'stripDelay' => 'время снятия', 'quickEquip' => 'быстрое надевание',
        'maxHealth' => 'максимальное здоровье', 'health' => 'здоровье', 'stunTime' => 'длительность оглушения', 'staminaDamage' => 'урон выносливости',
        'range' => 'дальность', 'angle' => 'угол атаки', 'attackRate' => 'скорость атаки', 'swingRate' => 'скорость замаха',
        'penetrationThreshold' => 'порог пробития', 'projectileSpeed' => 'скорость снаряда', 'spread' => 'разброс', 'recoil' => 'отдача',
        'magazineSize' => 'ёмкость магазина', 'ammoCount' => 'количество боеприпасов', 'shotsPerSecond' => 'выстрелов в секунду',
        'maxSlots' => 'число ячеек', 'maxWeight' => 'максимальный вес', 'maxItemSize' => 'максимальный размер предмета',
        'charges' => 'заряды', 'maxCharges' => 'максимум зарядов', 'uses' => 'число использований', 'cooldown' => 'перезарядка',
        'throwForce' => 'сила броска', 'throwSpeed' => 'скорость броска', 'stun' => 'оглушение', 'knockdown' => 'сбивание с ног',
        'Head' => 'голова', 'Eyes' => 'глаза', 'Mask' => 'лицо', 'Neck' => 'шея', 'OuterClothing' => 'верхняя одежда',
        'InnerClothing' => 'нижний слой одежды', 'Belt' => 'пояс / разгрузка', 'Back' => 'спина', 'Gloves' => 'перчатки', 'Shoes' => 'обувь',
        'Jumpsuit' => 'комбинезон', 'Ears' => 'уши', 'IDCard' => 'карта доступа', 'Pocket' => 'карман',
        'Tiny' => 'крошечный', 'Small' => 'маленький', 'Normal' => 'обычный', 'Large' => 'большой', 'Huge' => 'огромный',
        'Blunt' => 'тупой', 'Slash' => 'рубящий', 'Piercing' => 'колющий', 'Heat' => 'огонь / высокая температура',
        'Cold' => 'холод', 'Shock' => 'электричество', 'Caustic' => 'едкое вещество', 'Radiation' => 'радиация', 'Poison' => 'яд',
    ];

    public static function componentLines(mixed $components, int $limit = 24): array
    {
        if (!is_array($components)) return [];

        // Show only gameplay-facing fields. The raw YAML remains stored in components,
        // but implementation details (sprite layers, animation, UI internals, etc.) are hidden.
        $lines = [];
        $flatByComponent = [];
        foreach ($components as $component => $data) {
            if (is_array($data)) $flatByComponent[strtolower((string) $component)] = self::flatten($data);
        }
        $add = static function (string $label, mixed $value, string $unit = '') use (&$lines, $limit): void {
            if (count($lines) >= $limit || $value === null || is_array($value) || is_object($value)) return;
            if (is_bool($value)) $value = $value ? 'да' : 'нет';
            elseif (is_numeric($value)) $value = self::number((float) $value).$unit;
            else $value = trim((string) $value);
            if ($value !== '') $lines[] = $label.': '.$value;
        };
        $find = static function (array $components, array $componentNames, array $keys): mixed {
            foreach ($componentNames as $component) {
                foreach ($components[$component] ?? [] as $path => $value) {
                    $last = strtolower((string) basename(str_replace('.', '/', (string) $path)));
                    if (in_array($last, $keys, true)) return $value;
                }
            }
            return null;
        };

        $size = $find($flatByComponent, ['item'], ['size', 'itemsiz']);
        if ($size !== null) {
            $sizes = ['tiny' => 'Крошечный', 'small' => 'Маленький', 'normal' => 'Обычный', 'large' => 'Большой', 'huge' => 'Огромный'];
            $raw = is_scalar($size) ? (string) $size : '';
            $add('Размер предмета', $sizes[strtolower($raw)] ?? $raw);
        }
        $add('Задержка использования', $find($flatByComponent, ['usedelay', 'use_delay'], ['delay', 'usedelay', 'delaytime']), ' с');
        $add('Время перезарядки', $find($flatByComponent, ['gun', 'ballisticammoprovider'], ['reloadtime', 'reloadtime']), ' с');
        $add('Скорострельность', $find($flatByComponent, ['gun'], ['firerate', 'firerate', 'shotspersecond']), ' выстр./с');
        $add('Боеприпасов в магазине', $find($flatByComponent, ['ballisticammoprovider', 'ammoCounter'], ['capacity', 'ammocount', 'count']));
        $add('Вместимость хранилища', $find($flatByComponent, ['storage'], ['capacity', 'maxslots', 'maxweight']), '');
        $maxItemSize = $find($flatByComponent, ['storage'], ['maxitemsize']);
        if ($maxItemSize !== null) {
            $sizeNames = ['tiny' => 'Крошечный', 'small' => 'Маленький', 'normal' => 'Обычный', 'large' => 'Большой', 'huge' => 'Огромный'];
            $rawSize = is_scalar($maxItemSize) ? (string) $maxItemSize : '';
            $add('Максимальный размер хранимого предмета', $sizeNames[strtolower($rawSize)] ?? $rawSize);
        }
        $add('Скорость атаки', $find($flatByComponent, ['meleeweapon'], ['attackrate', 'attackrate', 'swingrate']), ' атак/с');
        $add('Дальность атаки', $find($flatByComponent, ['meleeweapon', 'gun'], ['range', 'attackrange']), ' м');

        foreach (['meleeweapon' => 'Урон ближнего боя', 'damage' => 'Урон'] as $component => $label) {
            foreach ($flatByComponent[$component] ?? [] as $path => $value) {
                $last = strtolower((string) basename(str_replace('.', '/', (string) $path)));
                if (in_array($last, ['blunt', 'slash', 'piercing', 'heat', 'cold', 'shock', 'caustic', 'structural', 'radiation', 'poison', 'holy'], true) && is_numeric($value) && (float) $value != 0.0) {
                    $lines[] = $label.' — '.self::label($last).': '.self::number((float) $value);
                    if (count($lines) >= $limit) break 2;
                }
            }
        }
        foreach ($flatByComponent['armor'] ?? [] as $path => $value) {
            if (!str_contains(strtolower((string) $path), 'modifiers.') || !is_numeric($value)) continue;
            $type = strtolower((string) basename(str_replace('.', '/', (string) $path)));
            $multiplier = (float) $value;
            $delta = (int) round(abs(1 - $multiplier) * 100);
            if ($delta === 0 || !in_array($type, ['blunt','slash','piercing','heat','cold','shock','caustic','structural','radiation'], true)) continue;
            $lines[] = 'Защита — '.self::label($type).': '.($multiplier < 1 ? 'снижение урона на '.$delta.'%' : 'уязвимость +'.$delta.'%');
            if (count($lines) >= $limit) break;
        }
        $clothingSlots = $find($flatByComponent, ['clothing'], ['slots']);
        if (is_string($clothingSlots) && $clothingSlots !== '') $add('Слоты экипировки', $clothingSlots);

        return array_values(array_unique(array_slice($lines, 0, $limit)));
    }

    private static function flatten(array $data, string $prefix = '', int $depth = 0): array
    {
        $out = [];
        if ($depth > 5) return $out;
        foreach ($data as $key => $value) {
            if (in_array((string) $key, ['_type', 'type', 'sprite', 'layers', 'map'], true)) continue;
            $path = $prefix === '' ? (string) $key : $prefix.'.'.$key;
            if (is_array($value)) {
                if (array_is_list($value)) {
                    if ($value && count($value) <= 12 && !is_array(reset($value))) {
                        $out[$path] = implode(', ', array_map('strval', $value));
                    } else {
                        foreach (array_slice($value, 0, 12, true) as $index => $child) {
                            if (is_array($child)) $out += self::flatten($child, $path.'.элемент'.((int) $index + 1), $depth + 1);
                        }
                    }
                    continue;
                }
                $out += self::flatten($value, $path, $depth + 1);
            } elseif (is_scalar($value) || $value === null) {
                $out[$path] = $value;
            }
        }
        return $out;
    }

    private static function isMultiplierKey(string $key): bool
    {
        return (bool) preg_match('/(^|_)(walk|sprint|speed|movement.?speed|speed.?modifier|multiplier|modifier)$/i', $key)
            || in_array($key, ['walk', 'sprint', 'walkspeed', 'sprintspeed', 'walkspeedmodifier', 'sprintspeedmodifier'], true);
    }

    private static function multiplierSentence(string $path, float $value): string
    {
        $path = strtolower($path);
        $parts = preg_split('/[.\\\\_]/', $path);
        $last = (string) end($parts);
        $movement = str_contains($path, 'walk') || str_contains($path, 'sprint') || str_contains($path, 'speed');
        $what = str_contains($path, 'sprint') ? 'скорость бега' : (str_contains($path, 'walk') ? 'скорость ходьбы' : 'скорость');
        $noun = $movement ? $what : self::label($last);
        $delta = (int) round(abs($value - 1.0) * 100);
        if (abs($value - 1.0) < 0.00001) return $noun.' не изменяется (множитель ×1,00)';
        if ($value > 1.0) return 'увеличивает '.$noun.' на '.$delta.'% (множитель ×'.self::number($value).')';
        return 'уменьшает '.$noun.' на '.$delta.'% (множитель ×'.self::number($value).')';
    }

    private static function prettyValue(mixed $value, string $key): string
    {
        if ($value === null) return 'не задано';
        if (is_bool($value)) return $value ? 'да' : 'нет';
        if (is_numeric($value)) {
            $n = (float) $value;
            if (preg_match('/(time|delay|duration|cooldown)$/i', $key)) return self::number($n).' с';
            if (preg_match('/(capacity|amount|count|charges|shots|burst)$/i', $key)) return self::number($n);
            return self::number($n);
        }
        if (is_array($value)) return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '';
        $text = trim((string) $value);
        $valueTranslations = [
            'Head' => 'голова', 'Eyes' => 'глаза', 'Mask' => 'лицо', 'Neck' => 'шея', 'OuterClothing' => 'верхняя одежда',
            'InnerClothing' => 'нижний слой одежды', 'Belt' => 'пояс / разгрузка', 'Back' => 'спина', 'Gloves' => 'перчатки',
            'Shoes' => 'обувь', 'Jumpsuit' => 'комбинезон', 'Ears' => 'уши', 'Pocket' => 'карман',
            'Tiny' => 'крошечный', 'Small' => 'маленький', 'Normal' => 'обычный', 'Large' => 'большой', 'Huge' => 'огромный',
            'semi-auto' => 'полуавтоматический', 'full-auto' => 'автоматический', 'burst' => 'очередь',
        ];
        return $valueTranslations[$text] ?? $text;
    }

    /** Convert scalar or nested extractor values into a readable, escaped-by-Blade string. */
    public static function readableValue(mixed $value, int $depth = 0): string
    {
        if ($value === null) return 'не задано';
        if (is_bool($value)) return $value ? 'да' : 'нет';
        if (is_scalar($value)) return trim((string) $value);
        if (!is_array($value)) return '';
        if ($depth >= 5) return '…';

        $parts = [];
        foreach ($value as $key => $child) {
            if (is_string($key) && str_starts_with($key, '_')) continue;
            $text = self::readableValue($child, $depth + 1);
            if ($text === '') continue;
            if (is_string($key) && !is_int($key)) {
                $parts[] = self::label($key).': '.$text;
            } else {
                $parts[] = $text;
            }
        }
        return implode('; ', $parts);
    }

    /** Split extractor's compact effect blob into clean, translated display lines. */
    public static function effectLines(mixed $value): array
    {
        if (is_array($value)) {
            $rawLines = [];
            foreach ($value as $item) {
                if (is_string($item)) $rawLines[] = $item;
                elseif (is_array($item)) {
                    foreach (['summary', 'description', 'effect', 'text', 'label'] as $key) {
                        if (is_string($item[$key] ?? null) && trim($item[$key]) !== '') { $rawLines[] = $item[$key]; break; }
                    }
                }
            }
        } elseif (is_string($value) && trim($value) !== '') {
            $rawLines = preg_split('/\s*(?:•|;|\R)\s*/u', trim($value)) ?: [];
        } else {
            return [];
        }

        $lines = [];
        foreach ($rawLines as $raw) {
            $line = trim(strip_tags((string) $raw));
            $line = preg_replace('/\s+/u', ' ', $line) ?? $line;
            $line = trim($line, " \t\n\r\0\x0B•;·");
            // Drop incomplete fragments produced when a source has an empty effect node.
            if ($line === '' || preg_match('/^\[[^]]+\]\s*(?:Effect|Эффект)\s*:?\s*$/iu', $line)) continue;

            $line = preg_replace('/^\[Digestion\]\s*Effect:\s*/i', 'При переваривании: ', $line) ?? $line;
            $line = preg_replace('/^\[Digestion\]\s*/i', 'При переваривании: ', $line) ?? $line;
            $line = preg_replace('/^\[Touch\]\s*/i', 'При касании: ', $line) ?? $line;
            $line = preg_replace('/^on touch:\s*/i', 'При касании: ', $line) ?? $line;
            $line = preg_replace('/^Tile:\s*/i', 'На поверхности: ', $line) ?? $line;
            $line = preg_replace('/^Effect:\s*/i', '', $line) ?? $line;
            $line = preg_replace('/\s*\[(?:Digestion|Touch)\]\s*$/i', '', $line) ?? $line;
            $line = preg_replace('/\s*\[(?:Digestion|Touch)\]\s*/i', ' ', $line) ?? $line;
            $line = trim($line, " \t\n\r\0\x0B•;·");
            if ($line === '' || preg_match('/^(?:Effect|Эффект)\s*:?$/iu', $line)) continue;

            $replacements = [
                '/Satiate/i' => 'насыщение',
                '/Adds\s+([0-9.]+)u\s+([A-Za-z0-9_-]+)/i' => 'Добавляет $1 ед. $2',
                '/Ignites spilled reagent\s*\(([^)]*)\)/i' => 'Воспламеняет разлитый реагент ($1)',
                '/ignites target/i' => 'поджигает цель',
                '/starts fire on target/i' => 'поджигает цель',
                '/^Extinguishes fires on wet tiles$/i' => 'Тушит огонь на мокрых поверхностях',
                '/^Cleans decals and puddles$/i' => 'Очищает следы и лужи',
                '/^Slippery surface \(trips walkers\)$/i' => 'Делает поверхность скользкой',
                '/^Sticky surface \(slows movement\)$/i' => 'Делает поверхность липкой и замедляет движение',
            ];
            foreach ($replacements as $pattern => $replacement) {
                if (preg_match($pattern, $line)) { $line = preg_replace($pattern, $replacement, $line) ?? $line; break; }
            }
            $line = str_replace(['Ethanot', 'Ethanol'], 'этанола', $line);
            $line = str_replace(['temperature)', ' temperature'], ['к температуре)', ' к температуре'], $line);
            $line = preg_replace('/\s+/u', ' ', $line) ?? $line;
            $line = trim($line);
            if ($line !== '' && !in_array(strtolower($line), array_map('strtolower', $lines), true)) $lines[] = $line;
        }
        return $lines;
    }

    public static function label(string $key): string
    {
        if (isset(self::LABELS[$key])) return self::LABELS[$key];
        foreach (self::LABELS as $known => $label) {
            if (strtolower($known) === strtolower($key)) return $label;
        }
        $key = preg_replace('/([a-z])([A-Z])/', '$1 $2', $key) ?? $key;
        $key = str_replace(['_', '-'], ' ', $key);
        $key = strtolower($key);
        return ucfirst($key);
    }

    private static function number(float $number): string
    {
        return rtrim(rtrim(number_format($number, 2, ',', ''), '0'), ',');
    }

    public static function colorName(?string $hex): ?string
    {
        if (!$hex || !preg_match('/^#?([0-9a-f]{6})$/i', $hex, $m)) return null;
        $rgb = array_map('hexdec', str_split($m[1], 2));
        [$r, $g, $b] = $rgb;
        $max = max($rgb); $min = min($rgb);
        if ($max < 35) return 'чёрный';
        if ($max - $min < 18) return $max > 220 ? 'белый' : ($max > 150 ? 'светло-серый' : 'серый');
        if ($r > 180 && $g > 120 && $b < 100) return 'оранжевый';
        if ($r > 180 && $g > 100 && $b > 100 && abs($r - $b) < 80) return 'розовый';
        if ($r > $g * 1.35 && $r > $b * 1.35) return 'красный';
        if ($g > $r * 1.25 && $g > $b * 1.15) return 'зелёный';
        if ($b > $r * 1.2 && $b > $g * 1.1) return ($r > 100 && $b > 120) ? 'фиолетовый' : 'синий';
        if ($r > 140 && $g > 100 && $b < 100) return 'жёлтый';
        if ($g > 100 && $b > 100) return 'бирюзовый';
        return 'смешанный оттенок';
    }
}
