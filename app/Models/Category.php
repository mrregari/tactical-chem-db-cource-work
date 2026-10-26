<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'type', 'description'];

    public function reagents()
    {
        return $this->hasMany(Reagent::class);
    }

    public function equipmentLoadouts()
    {
        return $this->hasMany(EquipmentLoadout::class);
    }
}
