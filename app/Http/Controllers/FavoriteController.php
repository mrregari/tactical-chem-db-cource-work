<?php

namespace App\Http\Controllers;

use App\Models\EquipmentLoadout;
use App\Models\Reagent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FavoriteController extends Controller
{
    public function index(Request $request): View
    {
        $ids = DB::table('favorites')->where('user_id', $request->user()->id);
        $reagents = Reagent::where('status', 'published')->whereIn('id', (clone $ids)->whereNotNull('reagent_id')->select('reagent_id'))->get();
        $loadouts = EquipmentLoadout::where('status', 'published')->whereIn('id', (clone $ids)->whereNotNull('equipment_loadout_id')->select('equipment_loadout_id'))->get();

        return view('favorites.index', compact('reagents', 'loadouts'));
    }

    public function toggle(Request $request, string $type, int $id): RedirectResponse
    {
        abort_unless(in_array($type, ['reagent', 'loadout'], true), 404);
        $model = $type === 'reagent' ? Reagent::class : EquipmentLoadout::class;
        $target = $model::where('status', 'published')->findOrFail($id);
        $column = $type === 'reagent' ? 'reagent_id' : 'equipment_loadout_id';
        $favorite = DB::table('favorites')->where('user_id', $request->user()->id)->where($column, $target->id);
        if ($favorite->exists()) {
            $favorite->delete();
        } else {
            DB::table('favorites')->insertOrIgnore([
                'user_id' => $request->user()->id,
                $column => $target->id,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return back()->with('success', 'Избранное обновлено.');
    }
}
