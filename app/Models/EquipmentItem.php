<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentItem extends Model
{
    protected $fillable = [
        'source_fork', 'source_name', 'prototype_id', 'name', 'description', 'category',
        'prototype_type', 'parent_ids', 'sprite_path', 'slots', 'tags', 'components',
        'source_file', 'source_url',
    ];

    protected function casts(): array
    {
        return [
            'parent_ids' => 'array', 'slots' => 'array', 'tags' => 'array', 'components' => 'array',
        ];
    }

    public function loadoutItems()
    {
        return $this->hasMany(LoadoutItem::class);
    }
}
