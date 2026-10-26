<?php

namespace App\Console\Commands;

use App\Models\Category;
use App\Models\Reaction;
use App\Models\Reagent;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Throwable;

#[Signature('chemistry:sync {--file= : Import an existing extractor JSON instead of scanning the forks}')]
#[Description('Fetch current chemistry prototypes from the forks configured in config/game_sources.php and update the catalog')]
class SyncChemistry extends Command
{
    public function handle(): int
    {
        $path = $this->option('file');
        if (! $path) {
            $path = base_path('data.json');
            $python = env('SS14_PYTHON', PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3');
            $process = new Process([$python, base_path('ss14_chem_extractor.py'), '--chemistry-only'], base_path());
            $process->setTimeout(3600);
            try {
                $process->mustRun();
            } catch (Throwable $exception) {
                $this->error('Сканирование не завершилось: '.$exception->getMessage());

                return self::FAILURE;
            }
        }

        $data = is_string($path) && is_file($path) ? json_decode(file_get_contents($path), true) : null;
        if (! is_array($data) || ! isset($data['reagents'], $data['reactions'])
            || ! is_array($data['reagents']) || ! is_array($data['reactions'])
            || count($data['reagents']) < ($this->option('file') ? 1 : 200)
            || count($data['reactions']) < ($this->option('file') ? 1 : 100)) {
            $this->error('Некорректный или неполный результат сканирования; база не изменена.');

            return self::FAILURE;
        }

        try {
            [$reagentCount, $reactionCount, $skipped] = DB::transaction(function () use ($data): array {
                $author = User::firstOrCreate(
                    ['email' => 'ss14-import@example.invalid'],
                    ['name' => 'Импорт SS14', 'password' => Hash::make(Str::random(48)), 'role' => 'user'],
                );
                $ids = [];
                $reagentCount = 0;
                foreach ($data['reagents'] as $upstreamId => $reagent) {
                    if (! is_array($reagent) || ! is_string($upstreamId) || ! is_string($reagent['name'] ?? null)
                        || ! is_string($reagent['source'] ?? null) || strlen($upstreamId) > 255) {
                        continue;
                    }
                    $category = Category::firstOrCreate([
                        'name' => Str::limit((string) ($reagent['category'] ?? 'Other'), 255, ''),
                        'type' => 'reagent',
                    ]);
                    $model = Reagent::updateOrCreate(['upstream_id' => $upstreamId], [
                        'name' => Str::limit(is_string($reagent['nameRu'] ?? null) && $reagent['nameRu'] !== '' ? $reagent['nameRu'] : $reagent['name'], 255, ''),
                        'description' => is_string($reagent['descRu'] ?? null) ? $reagent['descRu'] : (is_string($reagent['desc'] ?? null) ? $reagent['desc'] : null),
                        'color' => is_string($reagent['color'] ?? null) && preg_match('/^#[0-9a-fA-F]{6}$/', $reagent['color']) ? $reagent['color'] : null,
                        'effects' => $this->normalizeEffects($reagent),
                        'category_id' => $category->id, 'source_fork' => $reagent['source'],
                        'fork_status' => is_array($reagent['forkStatus'] ?? null) ? $reagent['forkStatus'] : null,
                        'also_in' => is_array($reagent['alsoIn'] ?? null) ? array_values($reagent['alsoIn']) : null,
                        'recipe_data' => is_array($reagent['recipe'] ?? null) ? $reagent['recipe'] : null,
                        'extra_data' => array_diff_key($reagent, array_flip(['id', 'name', 'nameRu', 'desc', 'descRu', 'category', 'source', 'color', 'recipe', 'effects', 'forkStatus', 'alsoIn'])),
                        'user_id' => $author->id, 'status' => 'published',
                    ]);
                    $ids[$upstreamId] = $model->id;
                    $reagentCount++;
                }

                $reactionCount = $skipped = 0;
                foreach ($data['reactions'] as $upstreamId => $reaction) {
                    $products = $reaction['products'] ?? [];
                    $reactants = $reaction['reactants'] ?? [];
                    if (! is_array($products) || count($products) !== 1 || ! is_array($reactants)
                        || ! is_string($upstreamId) || strlen($upstreamId) > 255) {
                        $skipped++;
                        continue;
                    }
                    $productId = array_key_first($products);
                    $amount = $products[$productId];
                    if (! isset($ids[$productId]) || ! is_numeric($amount) || (float) $amount <= 0 || (float) $amount > 10000) {
                        $skipped++;
                        continue;
                    }
                    $ingredients = [];
                    foreach ($reactants as $id => $reactant) {
                        if (! isset($ids[$id]) || ! is_array($reactant) || ! empty($reactant['catalyst'])
                            || ! is_numeric($reactant['amount'] ?? null) || (float) $reactant['amount'] <= 0) {
                            $ingredients = [];
                            break;
                        }
                        $ingredients[$ids[$id]] = (float) $reactant['amount'];
                    }
                    if ($ingredients === []) {
                        $skipped++;
                        continue;
                    }
                    Reaction::updateOrCreate(['upstream_id' => $upstreamId], [
                        'name' => Str::limit(is_string($data['reagents'][$productId]['nameRu'] ?? null) && $data['reagents'][$productId]['nameRu'] !== '' ? $data['reagents'][$productId]['nameRu'] : (string) ($data['reagents'][$productId]['name'] ?? $upstreamId), 255, ''),
                        'description' => is_string($reaction['effects'] ?? null) && $reaction['effects'] !== '' ? $reaction['effects'] : 'Рецепт из исходных YAML игрового билда.',
                        'ingredients' => $ingredients, 'result_reagent_id' => $ids[$productId],
                        'product_amount' => (float) $amount,
                        'source_fork' => is_string($reaction['source'] ?? null) ? $reaction['source'] : null,
                        'fork_status' => is_array($reaction['forkStatus'] ?? null) ? $reaction['forkStatus'] : null,
                        'fork_notes' => is_array($reaction['forkNotes'] ?? null) ? $reaction['forkNotes'] : null,
                        'also_in' => is_array($reaction['alsoIn'] ?? null) ? array_values($reaction['alsoIn']) : null,
                        'extra_data' => array_intersect_key($reaction, array_flip(['minTemp', 'maxTemp', 'mixer', 'impact', 'priority', 'products', 'effects'])),
                        'user_id' => $author->id, 'status' => 'published',
                    ]);
                    $reactionCount++;
                }

                return [$reagentCount, $reactionCount, $skipped];
            });
        } catch (Throwable $exception) {
            $this->error('Импорт отменён: '.$exception->getMessage());

            return self::FAILURE;
        }

        $this->info("Импортировано реагентов: {$reagentCount}; реакций: {$reactionCount}; пропущено: {$skipped}.");

        return self::SUCCESS;
    }

    /** Build a readable effect summary from structured extractor output when legacy effects is empty. */
    private function normalizeEffects(array $reagent): ?string
    {
        $parts = [];
        $raw = $reagent['effects'] ?? null;
        if (is_string($raw) && trim($raw) !== '') $parts[] = trim($raw);

        foreach (($reagent['verifiedMechanics'] ?? []) as $mechanic) {
            if (is_string($mechanic) && trim($mechanic) !== '') $parts[] = trim($mechanic);
            elseif (is_array($mechanic)) {
                foreach (['summary', 'description', 'effect', 'text', 'label'] as $key) {
                    if (is_string($mechanic[$key] ?? null) && trim($mechanic[$key]) !== '') { $parts[] = trim($mechanic[$key]); break; }
                }
            }
        }
        foreach (($reagent['plantEffects'] ?? []) as $effect) {
            if (is_string($effect) && trim($effect) !== '') $parts[] = 'Ботаника: '.trim($effect);
            elseif (is_array($effect)) {
                $label = $effect['summary'] ?? $effect['description'] ?? $effect['effect'] ?? $effect['name'] ?? null;
                if (is_string($label) && trim($label) !== '') $parts[] = 'Ботаника: '.trim($label);
            }
        }
        foreach (($reagent['effectTags'] ?? []) as $tag) {
            if (is_string($tag) && trim($tag) !== '') $parts[] = 'Эффект: '.preg_replace('/[_-]+/', ' ', trim($tag));
        }
        $parts = array_values(array_unique(array_filter($parts, fn ($v) => is_string($v) && trim($v) !== '')));
        return $parts ? mb_substr(implode(' · ', $parts), 0, 5000) : null;
    }

}
