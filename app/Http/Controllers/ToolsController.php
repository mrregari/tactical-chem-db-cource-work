<?php

namespace App\Http\Controllers;

use App\Support\ForkContext;

use App\Models\Reaction;
use App\Models\Reagent;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ToolsController extends Controller
{
    private function baseReagents(Request $request)
    {
        return Reagent::query()->where('status', 'published')->visibleInFork(ForkContext::selected($request));
    }

    public function botany(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $reagents = $this->baseReagents($request)->with('category')
            ->where(function ($query) {
                $query->whereHas('category', fn ($c) => $c->where('name', 'like', '%botany%'))
                    ->orWhere('effects', 'like', '%plant%')->orWhere('effects', 'like', '%weed%')
                    ->orWhere('effects', 'like', '%mutation%')->orWhere('effects', 'like', '%potency%')
                    ->orWhere('description', 'like', '%растен%');
            })
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('effects', 'like', "%{$q}%")))
            ->orderBy('name')->paginate(24)->withQueryString();
        return view('tools.botany', compact('reagents', 'q'));
    }

    public function medbay(Request $request): View
    {
        $q = trim((string) $request->query('q', ''));
        $reagents = $this->baseReagents($request)->with('category')
            ->where(function ($query) {
                $query->whereHas('category', fn ($c) => $c->where('name', 'like', '%medicine%'))
                    ->orWhere('effects', 'like', '%heal%')->orWhere('effects', 'like', '%brute%')
                    ->orWhere('effects', 'like', '%burn%')->orWhere('effects', 'like', '%poison%');
            })
            ->when($q !== '', fn ($query) => $query->where(fn ($w) => $w->where('name', 'like', "%{$q}%")->orWhere('effects', 'like', "%{$q}%")))
            ->orderBy('name')->paginate(24)->withQueryString();
        return view('tools.medbay', compact('reagents', 'q'));
    }

    public function trees(Request $request): View
    {
        $reagents = $this->baseReagents($request)->with('category')->orderBy('name')->get(['id','name','category_id','source_fork']);
        $selectedId = $request->integer('reagent') ?: null;
        $selected = $selectedId ? $reagents->firstWhere('id', $selectedId) : null;
        $tree = [];
        $visited = [];
        if ($selected) $this->buildTree($selected, 0, $tree, $visited, $request, 5);
        return view('tools.trees', compact('reagents', 'selected', 'tree'));
    }

    private function buildTree(Reagent $reagent, int $depth, array &$tree, array &$visited, Request $request, int $maxDepth): void
    {
        if ($depth > $maxDepth || isset($visited[$reagent->id])) return;
        $visited[$reagent->id] = true;
        $recipe = Reaction::where('status', 'published')->where('result_reagent_id', $reagent->id)->visibleInFork(ForkContext::selected($request))->first();
        $row = ['name' => $reagent->name, 'depth' => $depth, 'ingredients' => []];
        if ($recipe) {
            $items = Reagent::whereIn('id', array_keys($recipe->ingredients ?? []))->get()->keyBy('id');
            foreach ($recipe->ingredients ?? [] as $id => $amount) {
                if ($items->has($id)) {
                    $row['ingredients'][] = ['name' => $items[$id]->name, 'amount' => $amount];
                    $this->buildTree($items[$id], $depth + 1, $tree, $visited, $request, $maxDepth);
                }
            }
        }
        $tree[] = $row;
    }

    public function maps(): View
    {
        return view('tools.maps');
    }

    public function ordnance(Request $request): View
    {
        $data = $request->validate(['reagent' => ['nullable', 'integer'], 'amount' => ['nullable', 'numeric', 'min:0.01', 'max:100000']]);
        $reagents = $this->baseReagents($request)->with('category')
            ->where(function ($q) {
                $q->whereHas('category', fn ($c) => $c->where('name', 'like', '%pyrotechnic%'))
                    ->orWhere('name', 'like', '%ANFO%')->orWhere('name', 'like', '%octogen%')
                    ->orWhere('name', 'like', '%cyclonite%')->orWhere('name', 'like', '%explos%');
            })->orderBy('name')->get();
        $selected = isset($data['reagent']) ? $reagents->firstWhere('id', (int) $data['reagent']) : null;
        $amount = (float) ($data['amount'] ?? 1);
        $plan = [];
        $steps = [];
        if ($selected) {
            $this->expandRecipe($selected->id, $amount, $request, $plan, $steps, [], 0);
        }
        return view('tools.ordnance', compact('reagents', 'selected', 'amount', 'plan', 'steps'));
    }

    private function expandRecipe(int $reagentId, float $needed, Request $request, array &$plan, array &$steps, array $path, int $depth): void
    {
        if ($depth > 12 || in_array($reagentId, $path, true)) {
            $item = Reagent::find($reagentId);
            if ($item) $plan[$item->name] = ($plan[$item->name] ?? 0) + $needed;
            return;
        }
        $recipe = Reaction::where('status', 'published')->where('result_reagent_id', $reagentId)->visibleInFork(ForkContext::selected($request))->first();
        $item = Reagent::find($reagentId);
        if (! $item || ! $recipe || (float) $recipe->product_amount <= 0) {
            if ($item) $plan[$item->name] = ($plan[$item->name] ?? 0) + $needed;
            return;
        }
        $steps[] = ['product' => $item->name, 'needed' => $needed, 'recipe' => $recipe->name];
        $path[] = $reagentId;
        foreach ($recipe->ingredients ?? [] as $ingredientId => $perProduct) {
            $this->expandRecipe((int) $ingredientId, $needed * (float) $perProduct / (float) $recipe->product_amount, $request, $plan, $steps, $path, $depth + 1);
        }
    }

    public function antag(Request $request): View
    {
        $reagents = $this->baseReagents($request)->with('category')
            ->where(function ($q) {
                $q->whereHas('category', fn ($c) => $c->whereIn('name', ['Toxins', 'Narcotics', 'Pyrotechnic']))
                    ->orWhere('effects', 'like', '%poison%')->orWhere('effects', 'like', '%toxin%');
            })->orderBy('name')->paginate(24)->withQueryString();
        return view('tools.antag', compact('reagents'));
    }
}
