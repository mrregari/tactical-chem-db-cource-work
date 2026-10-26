<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LoadoutItem extends Model
{
    protected $fillable = ['equipment_loadout_id', 'equipment_item_id', 'item_name', 'slot', 'quantity', 'notes'];

    public function equipmentItem()
    {
        return $this->belongsTo(EquipmentItem::class);
    }

    public function equipmentLoadout()
    {
        return $this->belongsTo(EquipmentLoadout::class);
    }
}
