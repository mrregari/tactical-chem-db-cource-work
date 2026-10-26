<?php

namespace App\Providers;

use App\Support\ForkContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        View::composer('layouts.app', function ($view): void {
            $context = ForkContext::context(request());
            $selectedSource = ForkContext::selected(request(), $context);
            $table = $context === 'equipment' ? 'equipment_items' : 'reagents';
            $sources = collect();
            if (Schema::hasTable($table)) {
                $countQuery = DB::table($table)->whereNotNull('source_fork');
                if ($context === 'botany') {
                    $countQuery->where(function ($query) {
                        $query->where('effects', 'like', '%plant%')->orWhere('effects', 'like', '%weed%')
                            ->orWhere('effects', 'like', '%mutation%')->orWhere('effects', 'like', '%potency%')
                            ->orWhere('description', 'like', '%растен%')->orWhere('description', 'like', '%plant%')
                            ->orWhere('extra_data', 'like', '%plantEffects%');
                    });
                }
                $counts = $countQuery->select('source_fork', DB::raw('COUNT(*) as total'))->groupBy('source_fork')->orderBy('source_fork')->get()->keyBy('source_fork');
                $reactionCounts = ($context === 'chemistry' && Schema::hasTable('reactions'))
                    ? DB::table('reactions')->whereNotNull('source_fork')->select('source_fork', DB::raw('COUNT(*) as total'))->groupBy('source_fork')->pluck('total', 'source_fork')
                    : collect();
                $registry = config('game_sources', []);
                $sources = $counts->map(function ($row, $key) use ($registry, $reactionCounts, $context) {
                    $label = $key; $color = '#7bd6df';
                    foreach ($registry as $registryKey => $definition) {
                        $name = is_array($definition) ? ($definition['name'] ?? $registryKey) : $registryKey;
                        if ($key === $registryKey || $key === $name) { $label = $name; $color = is_array($definition) ? ($definition['color'] ?? $color) : $color; break; }
                    }
                    return ['key' => $key, 'label' => $label, 'color' => $color,
                        'count' => (int) $row->total,
                        'reagents' => $context === 'equipment' ? 0 : (int) $row->total,
                        'equipment' => $context === 'equipment' ? (int) $row->total : 0,
                        'reactions' => (int) ($reactionCounts[$key] ?? 0)];
                })->values();
            }
            $selectedSourceLabel = $sources->firstWhere('key', $selectedSource)['label'] ?? null;
            $sourceContextLabel = match ($context) { 'equipment' => 'броня и оружие', 'botany' => 'ботаника', default => 'химикаты и рецепты' };
            $view->with(compact('sources', 'selectedSource', 'selectedSourceLabel', 'context', 'sourceContextLabel'));
        });
    }
}
