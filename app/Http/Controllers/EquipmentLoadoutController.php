<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\EquipmentLoadout;
use App\Models\EquipmentItem;
use App\Support\GameDataPresenter;
use App\Support\ForkContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EquipmentLoadoutController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:100']]);
        $loadouts = EquipmentLoadout::where('status', 'published')->with('category')
            ->when($filters['q'] ?? null, fn ($query, $search) => $query->where('name', 'like', '%'.$search.'%'))
            ->orderBy('name')->paginate(12)->withQueryString();

        return view('loadouts.index', compact('loadouts'));
    }

    public function create(): View
    {
        $categories = Category::where('type', 'equipment')->orderBy('name')->get();
        $equipmentCount = EquipmentItem::query()
            ->when(session('selected_equipment_source'), fn ($query, $source) => $query->where('source_fork', $source))
            ->count();

        return view('loadouts.create', compact('categories', 'equipmentCount'));
    }

    public function catalog(Request $request): View
    {
        $source = ForkContext::selected($request, 'equipment');
        $visibleForks = $this->visibleEquipmentForks($source);
        $base = EquipmentItem::query()->when($visibleForks !== null, fn ($query) => $query->whereIn('source_fork', $visibleForks));
        $totalItems = (clone $base)->count();
        $categoryCounts = (clone $base)->select('category', DB::raw('COUNT(*) as total'))->groupBy('category')->orderBy('category')->pluck('total', 'category');
        $forkCounts = EquipmentItem::query()->select('source_fork', DB::raw('COUNT(*) as total'))->groupBy('source_fork')->orderBy('source_fork')->pluck('total', 'source_fork');
        return view('loadouts.catalog', compact('totalItems', 'categoryCounts', 'forkCounts', 'source'));
    }

    public function searchItems(Request $request)
    {
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:50'],
            'sort' => ['nullable', 'in:name,category,prototype_id'],
            'direction' => ['nullable', 'in:asc,desc'],
            'unique_only' => ['nullable', 'boolean'],
        ]);
        $source = ForkContext::selected($request, 'equipment');
        if (($filters['q'] ?? '') === '' && ($filters['category'] ?? '') === '') return response()->json(['items' => []]);
        $visibleForks = $this->visibleEquipmentForks($source);
        $baseQuery = EquipmentItem::query()
            ->when($visibleForks !== null, fn ($query) => $query->whereIn('source_fork', $visibleForks))
            ->when(($filters['unique_only'] ?? false) && $source && $source !== 'vanilla', function ($query) use ($source) {
                $query->whereNotIn('prototype_id', EquipmentItem::query()->select('prototype_id')->where('source_fork', 'vanilla'));
            });
        $total = (clone $baseQuery)
            ->when(($filters['q'] ?? '') !== '', function ($query) use ($filters) {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['q']).'%';
                $query->where(fn ($search) => $search->where('name', 'like', $term)->orWhere('prototype_id', 'like', $term)->orWhere('description', 'like', $term));
            })
            ->when(($filters['category'] ?? '') !== '', fn ($query) => $query->where('category', $filters['category']))->count();
        $sort = $filters['sort'] ?? 'name';
        $direction = $filters['direction'] ?? 'asc';
        $items = $baseQuery
            ->when(($filters['q'] ?? '') !== '', function ($query) use ($filters) {
                $term = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $filters['q']).'%';
                $query->where(function ($search) use ($term) {
                    $search->where('name', 'like', $term)->orWhere('prototype_id', 'like', $term)->orWhere('description', 'like', $term);
                });
            })
            ->when(($filters['category'] ?? '') !== '', fn ($query) => $query->where('category', $filters['category']))
            ->orderByRaw($source ? 'CASE WHEN source_fork = ? THEN 0 ELSE 1 END' : 'CASE WHEN source_fork = \'vanilla\' THEN 0 ELSE 1 END', $source ? [$source] : [])
            ->orderBy('category')->orderBy('name')->limit(60)->get([
                'id', 'source_fork', 'source_name', 'prototype_id', 'name', 'description', 'category', 'sprite_path', 'slots', 'tags', 'components', 'source_url'
            ])->map(function (EquipmentItem $item): EquipmentItem {
                $item->humanized_stats = GameDataPresenter::componentLines($item->components);
                $item->sprite_views = ['item' => $this->spriteCandidatesFor($item, is_string($item->sprite_path) ? $item->sprite_path : '', $item->components['Sprite'] ?? [])];
                return $item;
            });

        return response()->json(['items' => $items, 'total' => $total, 'shown' => $items->count(), 'source' => $source, 'unique_only' => (bool) ($filters['unique_only'] ?? false)]);
    }


    /** Return the selected fork lineage plus vanilla; null means search every fork. */
    private function visibleEquipmentForks(?string $source): ?array
    {
        if (!$source || $source === 'all') return null;
        $registry = config('game_sources', []);
        $forks = [];
        $cursor = $source;
        $seen = [];
        while ($cursor && isset($registry[$cursor]) && !isset($seen[$cursor])) {
            $seen[$cursor] = true;
            $forks[] = $cursor;
            $cursor = $registry[$cursor]['parent_fork'] ?? null;
        }
        if (!in_array('vanilla', $forks, true)) $forks[] = 'vanilla';
        return array_values(array_unique($forks));
    }

    private function spriteCandidatesFor(EquipmentItem $item, string $spritePath, array $spriteComponent): array
    {
        $sourceUrl = (string) $item->source_url;
        $spritePath = ltrim($spritePath, '/');
        if ($sourceUrl === '' || $spritePath === '' || !str_starts_with($sourceUrl, 'https://raw.githubusercontent.com/')) return [];
        if (!preg_match('#^https://raw\.githubusercontent\.com/([^/]+)/([^/]+)/([^/]+)/#', $sourceUrl, $match)) return [];
        $base = 'https://raw.githubusercontent.com/'.$match[1].'/'.$match[2].'/'.$match[3].'/Resources/Textures/';
        $texturePath = preg_replace('#^Textures/#i', '', $spritePath);
        $texturePath = str_replace('..', '', (string) $texturePath);
        if (preg_match('/\.(png|webp|jpg)$/i', $texturePath)) return [$base.$texturePath];
        if (!str_ends_with(strtolower($texturePath), '.rsi')) return [];

        $states = [];
        $state = $spriteComponent['state'] ?? null;
        if (is_array($state)) $state = $state['state'] ?? $state['name'] ?? null;
        if (is_string($state)) $states[] = $state;
        foreach (($spriteComponent['layers'] ?? []) as $layer) {
            if (is_array($layer) && is_string($layer['state'] ?? null)) $states[] = $layer['state'];
        }
        $states = array_values(array_unique(array_filter($states, fn ($v) => is_string($v) && preg_match('/^[a-zA-Z0-9_.-]{1,100}$/', $v))));
        $candidates = [$base.$texturePath.'/meta.json'];
        foreach ($states as $state) $candidates[] = $base.$texturePath.'/'.$state.'.png';
        return array_slice(array_values(array_unique($candidates)), 0, 10);
    }

    public function store(Request $request): RedirectResponse
    {
        if (is_array($request->input('items'))) {
            $request->merge(['items' => array_values(array_filter($request->input('items'), fn ($item) => ! is_array($item) || filled($item['equipment_item_id'] ?? null) || filled($item['item_name'] ?? null)))]);
        }
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'role' => ['required', 'string', 'max:100'],
            'category_id' => ['required', Rule::exists('categories', 'id')->where('type', 'equipment')],
            'items' => ['required', 'array', 'min:1', 'max:20'],
            'items.*.equipment_item_id' => ['required', 'integer', 'exists:equipment_items,id'],
            'items.*.item_name' => ['required', 'string', 'max:255'],
            'items.*.slot' => ['required', 'string', 'max:100'],
            'items.*.quantity' => ['required', 'integer', 'min:1', 'max:100'],
            'items.*.notes' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data): void {
            $loadout = EquipmentLoadout::create([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'role' => $data['role'],
                'category_id' => $data['category_id'],
                'user_id' => Auth::id(),
                'status' => 'draft',
            ]);
            foreach ($data['items'] as $itemData) {
                if (! empty($itemData['equipment_item_id'])) {
                    $catalogItem = EquipmentItem::find($itemData['equipment_item_id']);
                    if (! $catalogItem) throw ValidationException::withMessages(['items' => 'Один из предметов больше не существует в каталоге.']);
                    $selectedSource = ForkContext::selected(request(), 'equipment');
                    if ($selectedSource && $catalogItem->source_fork !== $selectedSource) {
                        throw ValidationException::withMessages(['items' => 'Предмет не относится к выбранному игровому форку. Обнови страницу и выбери предмет заново.']);
                    }
                    $itemData['item_name'] = $catalogItem->name.' ['.$catalogItem->prototype_id.']';
                }
                $loadout->items()->create($itemData);
            }
        });

        return redirect()->route('loadouts.index')->with('success', 'Билд отправлен на модерацию.');
    }

    public function show(EquipmentLoadout $loadout): View
    {
        abort_unless($loadout->status === 'published' || Auth::user()?->isAdmin() || Auth::id() === $loadout->user_id, 404);
        $loadout->load(['category', 'user', 'items.equipmentItem', 'comments.user']);

        return view('loadouts.show', compact('loadout'));
    }
}
