<?php

namespace App\Http\Controllers;

use App\Models\EquipmentLoadout;
use App\Models\Reaction;
use App\Models\Reagent;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AdminController extends Controller
{
    public function index(): View
    {
        $pendingReagents = Reagent::where('status', 'draft')->with('user')->get();
        $pendingLoadouts = EquipmentLoadout::where('status', 'draft')->with('user')->get();
        $pendingReactions = Reaction::where('status', 'draft')->with('user', 'resultReagent')->get();
        $users = User::orderBy('name')->get();

        return view('admin.index', compact('pendingReagents', 'pendingLoadouts', 'pendingReactions', 'users'));
    }

    public function approveReagent(Reagent $reagent): RedirectResponse
    {
        $reagent->update(['status' => 'published']);

        return back()->with('success', 'Химикат опубликован.');
    }

    public function rejectReagent(Reagent $reagent): RedirectResponse
    {
        $reagent->update(['status' => 'rejected']);

        return back()->with('success', 'Химикат отклонён.');
    }

    public function approveLoadout(EquipmentLoadout $loadout): RedirectResponse
    {
        $loadout->update(['status' => 'published']);

        return back()->with('success', 'Билд опубликован.');
    }

    public function rejectLoadout(EquipmentLoadout $loadout): RedirectResponse
    {
        $loadout->update(['status' => 'rejected']);

        return back()->with('success', 'Билд отклонён.');
    }

    public function approveReaction(Reaction $reaction): RedirectResponse
    {
        abort_unless($reaction->resultReagent->status === 'published'
            && Reagent::where('status', 'published')->whereIn('id', array_keys($reaction->ingredients))->count() === count($reaction->ingredients), 422);
        $reaction->update(['status' => 'published']);

        return back()->with('success', 'Рецепт опубликован.');
    }

    public function rejectReaction(Reaction $reaction): RedirectResponse
    {
        $reaction->update(['status' => 'rejected']);

        return back()->with('success', 'Рецепт отклонён.');
    }

    public function changeRole(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate(['role' => ['required', 'in:user,admin']]);
        abort_if($user->id === $request->user()->id && $data['role'] !== 'admin', 403);
        $user->update($data);

        return back()->with('success', 'Роль пользователя изменена.');
    }
}
