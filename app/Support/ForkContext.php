<?php

namespace App\Support;

use Illuminate\Http\Request;

/** Keeps source selection independent for each catalog domain. */
final class ForkContext
{
    public static function context(Request $request): string
    {
        $route = (string) ($request->route()?->getName() ?? '');
        if (str_starts_with($route, 'equipment-items.') || str_starts_with($route, 'loadouts.')) return 'equipment';
        if ($route === 'tools.botany') return 'botany';
        return 'chemistry';
    }

    public static function sessionKey(string $context): string
    {
        return match ($context) {
            'equipment' => 'selected_equipment_source',
            'botany' => 'selected_botany_source',
            default => 'selected_source',
        };
    }

    public static function selected(Request $request, ?string $context = null): ?string
    {
        $context ??= self::context($request);
        return $request->session()->get(self::sessionKey($context), $context === 'chemistry' ? null : null);
    }
}
