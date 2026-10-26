<?php

namespace App\Http\Controllers;

use App\Models\EquipmentLoadout;
use App\Models\Reagent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:reagent,loadout'],
            'id' => ['required', 'integer', 'min:1'],
            'content' => ['required', 'string', 'max:2000'],
        ]);

        $model = $data['type'] === 'reagent' ? Reagent::class : EquipmentLoadout::class;
        $target = $model::where('status', 'published')->findOrFail($data['id']);
        $target->comments()->create(['content' => $data['content'], 'user_id' => $request->user()->id]);

        return redirect()->route($data['type'] === 'reagent' ? 'reagents.show' : 'loadouts.show', $target)
            ->with('success', 'Комментарий добавлен.');
    }
}
