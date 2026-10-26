<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EquipmentLoadout extends Model
{
    protected $fillable = ['name', 'description', 'role', 'category_id', 'user_id', 'status'];

    public function category()
    {
        return $this->belongsTo(Category::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function items()
    {
        return $this->hasMany(LoadoutItem::class);
    }

    public function comments()
    {
        return $this->morphMany(Comment::class, 'commentable');
    }
}
