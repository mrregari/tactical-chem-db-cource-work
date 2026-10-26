<?php

namespace App\Http\Controllers;

use App\Support\ForkContext;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class SourceController extends Controller
{
    public function update(Request $request): RedirectResponse
    {
        $context = $request->input('context', ForkContext::context($request));
        if (! in_array($context, ['chemistry', 'equipment', 'botany'], true)) $context = ForkContext::context($request);
        $table = $context === 'equipment' ? 'equipment_items' : 'reagents';
        $availableQuery = DB::table($table)->whereNotNull('source_fork');
        if ($context === 'botany') {
            $availableQuery->where(function ($query) {
                $query->where('effects', 'like', '%plant%')->orWhere('effects', 'like', '%weed%')
                    ->orWhere('effects', 'like', '%mutation%')->orWhere('effects', 'like', '%potency%')
                    ->orWhere('description', 'like', '%растен%')->orWhere('description', 'like', '%plant%')
                    ->orWhere('extra_data', 'like', '%plantEffects%');
            });
        }
        $available = $availableQuery->distinct()->pluck('source_fork')->all();
        if ($context === 'chemistry') {
            $reactionSources = DB::table('reactions')->whereNotNull('source_fork')->distinct()->pluck('source_fork')->all();
            $available = array_values(array_unique(array_merge($available, $reactionSources)));
        }
        $data = $request->validate([
            'source' => ['nullable', 'string', 'max:255', Rule::in(array_merge(['all'], $available))],
            'context' => ['nullable', Rule::in(['chemistry', 'equipment', 'botany'])],
        ]);
        $key = ForkContext::sessionKey($context);
        if (($data['source'] ?? 'all') === 'all') $request->session()->forget($key);
        else $request->session()->put($key, $data['source']);
        return redirect()->to(url()->previous() ?: route('home'));
    }
}
