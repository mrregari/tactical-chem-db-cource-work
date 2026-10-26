<?php

namespace App\Http\Controllers;

use App\Support\ForkContext;

use App\Models\Category;
use App\Models\Reagent;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ReagentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'source' => ['nullable', 'string', 'max:255'],
            'unique_only' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:name,effects,category'],
            'direction' => ['nullable', 'in:asc,desc'],
        ]);

        $selectedSource = ForkContext::selected($request);
        $reagentsQuery = Reagent::where('status', 'published')->with('category')
            ->when($filters['q'] ?? null, function ($query, $search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%'.$search.'%')
                        ->orWhere('description', 'like', '%'.$search.'%')
                        ->orWhere('effects', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('category_id', $category))
            ->visibleInFork($selectedSource)
            ->when(($filters['unique_only'] ?? false) && $selectedSource && $selectedSource !== 'vanilla', function ($query) {
                $query->whereNotIn('upstream_id', Reagent::query()->select('upstream_id')->where('source_fork', 'vanilla')->whereNotNull('upstream_id'));
                $query->where('source_fork', '!=', 'vanilla');
            });
        $sort = $filters['sort'] ?? 'name';
        $direction = $filters['direction'] ?? 'asc';
        $reagents = $reagentsQuery
            ->when($selectedSource, fn ($query, $source) => $query->orderByRaw('CASE WHEN source_fork = ? THEN 0 WHEN source_fork = ? THEN 1 ELSE 2 END', [$source, 'vanilla']))
            ->orderBy($sort === 'category' ? 'category_id' : $sort, $direction)->paginate(12)->withQueryString();

        $categories = Category::where('type', 'reagent')->orderBy('name')->get();

        // Build a source directory from the imported catalog. The database can contain
        // either a registry key (e.g. "vanilla") or the display name from data.json.
        $registry = config('game_sources', []);
        $sourceLabels = [];
        $sourceColors = [];
        foreach ($registry as $key => $definition) {
            $label = is_array($definition) && is_string($definition['name'] ?? null)
                ? $definition['name']
                : ucfirst((string) $key);
            $sourceLabels[(string) $key] = $label;
            $sourceLabels[$label] = $label;
            $sourceColors[(string) $key] = is_string($definition['color'] ?? null) ? $definition['color'] : '#7bd6df';
            $sourceColors[$label] = $sourceColors[(string) $key];
        }

        $reagentCounts = Reagent::query()
            ->where('status', 'published')->whereNotNull('source_fork')
            ->selectRaw('source_fork, COUNT(*) as total')
            ->groupBy('source_fork')->pluck('total', 'source_fork');
        $reactionCounts = \App\Models\Reaction::query()
            ->where('status', 'published')->whereNotNull('source_fork')
            ->selectRaw('source_fork, COUNT(*) as total')
            ->groupBy('source_fork')->pluck('total', 'source_fork');

        $sourceKeys = $reagentCounts->keys()->merge($reactionCounts->keys())->unique()->sort()->values();
        $sources = $sourceKeys->map(function ($source) use ($sourceLabels, $sourceColors) {
            $label = $sourceLabels[$source] ?? $source;
            return [
                'key' => $source,
                'label' => $label,
                'reagents' => (int) Reagent::query()->where('status', 'published')->visibleInFork($source)->count(),
                'reactions' => (int) \App\Models\Reaction::query()->where('status', 'published')->visibleInFork($source)->count(),
                'color' => $sourceColors[$source] ?? '#7bd6df',
            ];
        })->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->values();
        $selectedSourceLabel = $selectedSource ? ($sourceLabels[$selectedSource] ?? $selectedSource) : null;

        return view('reagents.index', compact('reagents', 'categories', 'sources', 'selectedSource', 'selectedSourceLabel'));
    }

    public function create(): View
    {
        $categories = Category::where('type', 'reagent')->orderBy('name')->get();

        return view('reagents.create', compact('categories'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'effects' => ['nullable', 'string', 'max:5000'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('type', 'reagent')],
        ]);

        Reagent::create([...$data, 'user_id' => Auth::id(), 'status' => 'draft']);

        return redirect()->route('reagents.index')->with('success', 'Химикат отправлен на модерацию.');
    }

    public function show(Reagent $reagent): View
    {
        abort_unless($reagent->status === 'published' || Auth::user()?->isAdmin() || Auth::id() === $reagent->user_id, 404);

        $source = ForkContext::selected(request());
        abort_unless(Reagent::query()->where('status', 'published')->visibleInFork($source)->whereKey($reagent->id)->exists() || Auth::user()?->isAdmin() || Auth::id() === $reagent->user_id, 404);
        $reagent->load(['category', 'user', 'comments.user', 'reactions' => fn ($query) => $query->where('status', 'published')->visibleInFork($source)->with('resultReagent')]);
        $ingredientNames = Reagent::whereIn('id', $reagent->reactions->flatMap(fn ($reaction) => array_keys($reaction->ingredients ?? []))->unique())
            ->pluck('name', 'id');
        $recipeIngredientNames = Reagent::query()->whereIn('upstream_id', array_keys($reagent->recipe_data['reactants'] ?? []))->pluck('name', 'upstream_id');

        $selectedSource = $source;
        return view('reagents.show', compact('reagent', 'ingredientNames', 'recipeIngredientNames', 'selectedSource'));
    }
}
