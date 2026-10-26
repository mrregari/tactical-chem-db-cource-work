<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

final class ForkVisibility
{
    /**
     * A selected derivative fork sees its own prototypes, parent-fork prototypes,
     * and unchanged vanilla prototypes. Vanilla prototypes explicitly blocked by
     * the selected fork are excluded. A fork selector is not a hard ownership filter.
     */
    public static function apply(Builder $query, ?string $source, string $table): Builder
    {
        if (! $source || $source === 'all') {
            return $query;
        }

        $registry = config('game_sources', []);
        if (! isset($registry[$source])) {
            return $query->where($table.'.source_fork', $source);
        }

        $lineage = [];
        $cursor = $source;
        $seen = [];
        while ($cursor && isset($registry[$cursor]) && ! isset($seen[$cursor])) {
            $seen[$cursor] = true;
            $lineage[] = $cursor;
            $cursor = $registry[$cursor]['parent_fork'] ?? null;
        }
        $owners = array_values(array_filter($lineage, fn ($fork) => $fork !== 'vanilla'));

        return $query->where(function (Builder $visible) use ($owners, $source, $table) {
            if ($owners !== []) {
                $visible->whereIn($table.'.source_fork', $owners);
            }

            $visible->orWhereJsonContains($table.'.also_in', $source);
            $visible->orWhere(function (Builder $vanilla) use ($source, $table) {
                $statusPath = $table.'.fork_status->'.$source;
                $vanilla->where($table.'.source_fork', 'vanilla')
                    ->where(function (Builder $status) use ($statusPath, $table) {
                        $status->whereNull($table.'.fork_status')
                            ->orWhereNull($statusPath)
                            ->orWhere($statusPath, '!=', 'blocked');
                    });
            });
        });
    }
}
