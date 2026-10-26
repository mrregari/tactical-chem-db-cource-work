<?php

namespace App\Http\Controllers;

use App\Support\ForkContext;

use App\Models\Reaction;
use App\Models\Reagent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReactionController extends Controller
{
    public function index(Request $request): View
    {
        $data = $request->validate([
            'reagent' => ['nullable', 'integer', 'exists:reagents,id'],
            'quantity' => ['nullable', 'numeric', 'min:0.01', 'max:10000'],
        ]);
        $source = ForkContext::selected($request);
        $reagents = Reagent::where('status', 'published')->visibleInFork($source)->orderBy('name')->get();
        $selected = isset($data['reagent']) ? $reagents->firstWhere('id', (int) $data['reagent']) : null;
        abort_if(isset($data['reagent']) && ! $selected, 404);
        $reaction = $selected?->reactions()->where('status', 'published')->visibleInFork($source)->orderByRaw('CASE WHEN source_fork = ? THEN 0 WHEN source_fork = ? THEN 1 ELSE 2 END', [$source ?? '', 'vanilla'])->first();
        $quantity = (float) ($data['quantity'] ?? 1);
        $ingredients = $reaction ? Reagent::where('status', 'published')->visibleInFork($source)->whereIn('id', array_keys($reaction->ingredients))
            ->get()->map(fn ($ingredient) => [
                'name' => $ingredient->name,
                'quantity' => round((float) $reaction->ingredients[$ingredient->id] * $quantity / (float) $reaction->product_amount, 4),
            ]) : collect();
        if ($reaction && $ingredients->count() !== count($reaction->ingredients)) {
            $reaction = null;
            $ingredients = collect();
        }

        return view('reactions.index', compact('reagents', 'selected', 'reaction', 'quantity', 'ingredients'));
    }

    public function create(): View
    {
        $source = ForkContext::selected(request());
        $reagents = Reagent::where('status', 'published')->visibleInFork($source)->orderBy('name')->get();

        return view('reactions.create', compact('reagents'));
    }

    public function store(Request $request): RedirectResponse
    {
        if (is_array($request->input('ingredients'))) {
            $request->merge(['ingredients' => array_filter($request->input('ingredients'), fn ($value) => $value !== null && $value !== '')]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'result_reagent_id' => ['required', Rule::exists('reagents', 'id')->where('status', 'published')],
            'ingredients' => ['required', 'array', 'min:1', 'max:20'],
            'ingredients.*' => ['required', 'numeric', 'gt:0', 'max:10000'],
        ]);

        $ids = array_keys($data['ingredients']);
        $selectedSource = ForkContext::selected($request);
        if ($selectedSource && Reagent::where('status', 'published')->visibleInFork($selectedSource)->where('id', $data['result_reagent_id'])->doesntExist()) {
            return back()->withErrors(['result_reagent_id' => 'Результат должен принадлежать выбранному форку.'])->withInput();
        }
        if (collect($ids)->contains(fn ($id) => ! ctype_digit((string) $id) || (string) (int) $id !== (string) $id || (int) $id < 1)
            || count($ids) !== Reagent::where('status', 'published')->visibleInFork(ForkContext::selected($request))->whereIn('id', $ids)->count()) {
            return back()->withErrors(['ingredients' => 'Выберите только опубликованные ингредиенты.'])->withInput();
        }

        Reaction::create([...$data, 'user_id' => $request->user()->id, 'status' => 'draft']);

        return redirect()->route('reactions.index')->with('success', 'Рецепт отправлен на модерацию.');
    }
}
