<?php

namespace App\Models;

use App\Support\ForkVisibility;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Reagent extends Model
{
    protected $fillable = ['name', 'description', 'color', 'effects', 'category_id', 'user_id', 'status', 'upstream_id', 'source_fork', 'fork_status', 'also_in', 'recipe_data', 'extra_data'];

    protected $casts = [
        'fork_status' => 'array',
        'also_in' => 'array',
        'recipe_data' => 'array',
        'extra_data' => 'array',
    ];

    public function scopeVisibleInFork(Builder $query, ?string $source): Builder
    {
        return ForkVisibility::apply($query, $source, 'reagents');
    }

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }

    public function reactions()
    {
        return $this->hasMany(Reaction::class, 'result_reagent_id');
    }
}
