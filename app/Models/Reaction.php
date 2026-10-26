<?php

namespace App\Models;

use App\Support\ForkVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    protected $fillable = ['name', 'description', 'ingredients', 'result_reagent_id', 'user_id', 'status', 'upstream_id', 'source_fork', 'product_amount', 'fork_status', 'fork_notes', 'also_in', 'extra_data'];

    protected $casts = [
        'ingredients' => 'array',
        'fork_status' => 'array',
        'fork_notes' => 'array',
        'also_in' => 'array',
        'extra_data' => 'array',
    ];

    public function scopeVisibleInFork(Builder $query, ?string $source): Builder
    {
        return ForkVisibility::apply($query, $source, 'reactions');
    }

    public function resultReagent()
    {
        return $this->belongsTo(Reagent::class, 'result_reagent_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
