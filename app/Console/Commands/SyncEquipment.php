<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Throwable;

#[Signature('equipment:sync {--file= : Import an existing equipment JSON file} {--fork=all : Fork id from config.py, or all} {--max-files-per-fork=0 : Optional testing limit for YAML files per fork}')] 
#[Description('Extract armor, weapons, ammunition, grenades and other equipment prototypes from configured SS14 forks')]
class SyncEquipment extends Command
{
    public function handle(): int
    {
        // Large equipment JSON temporarily needs much more memory after decoding.
        $currentLimit = ini_get('memory_limit');
        if ($currentLimit !== false && $currentLimit !== '-1') {
            ini_set('memory_limit', '1024M');
        }
        $path = $this->option('file');
        if (! $path) {
            $path = storage_path('app/equipment-items.json');
            $venvPython = PHP_OS_FAMILY === 'Windows' ? base_path('.venv\\Scripts\\python.exe') : base_path('.venv/bin/python');
            $python = env('SS14_PYTHON', is_file($venvPython) ? $venvPython : (PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'));
            $args = [$python, base_path('ss14_equipment_extractor.py'), '--output', $path, '--fork', (string) $this->option('fork')];
            $limit = (int) $this->option('max-files-per-fork');
            if ($limit > 0) array_push($args, '--max-files-per-fork', (string) $limit);
            $process = new Process($args, base_path());
            $process->setTimeout(7200);
            $process->setIdleTimeout(900);
            $process->run(function (string $type, string $buffer): void {
                $this->output->write($buffer);
            });
            if (! $process->isSuccessful()) {
                $this->error('Сканирование экипировки не завершилось. Исходная таблица не изменена.');
                return self::FAILURE;
            }
        }

        if (! is_string($path) || ! is_file($path)) {
            $this->error('JSON экипировки не найден: '.(string) $path);
            return self::FAILURE;
        }
        $data = json_decode((string) file_get_contents($path), true);
        if (! is_array($data) || ! is_array($data['items'] ?? null) || count($data['items']) < 1) {
            $this->error('JSON не содержит массива items. База не изменена.');
            return self::FAILURE;
        }

        $count = 0;
        try {
            DB::transaction(function () use ($data, &$count): void {
                foreach (array_chunk($data['items'], 400) as $chunk) {
                    $rows = [];
                    foreach ($chunk as $item) {
                        if (! is_array($item) || ! is_string($item['source_fork'] ?? null)
                            || ! is_string($item['prototype_id'] ?? null) || ! is_string($item['name'] ?? null)) continue;
                        $rows[] = [
                            'source_fork' => substr($item['source_fork'], 0, 80),
                            'source_name' => $item['source_name'] ?? null,
                            'prototype_id' => substr($item['prototype_id'], 0, 255),
                            'name' => substr($item['name'], 0, 255),
                            'description' => $this->normalizeDescription($item['description'] ?? null),
                            'category' => substr((string) ($item['category'] ?? 'equipment'), 0, 50),
                            'prototype_type' => substr((string) ($item['prototype_type'] ?? 'entity'), 0, 50),
                            'parent_ids' => json_encode($item['parent_ids'] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                            'sprite_path' => $item['sprite_path'] ?? null,
                            'slots' => json_encode($item['slots'] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                            'tags' => json_encode($item['tags'] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                            'components' => json_encode($item['components'] ?? [], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                            'source_file' => $item['source_file'] ?? null,
                            'source_url' => $item['source_url'] ?? null,
                            'created_at' => now(), 'updated_at' => now(),
                        ];
                    }
                    if ($rows) {
                        DB::table('equipment_items')->upsert(
                            $rows,
                            ['source_fork', 'prototype_id'],
                            ['source_name', 'name', 'description', 'category', 'prototype_type', 'parent_ids', 'sprite_path', 'slots', 'tags', 'components', 'source_file', 'source_url', 'updated_at']
                        );
                        $count += count($rows);
                    }
                }
            });
        } catch (Throwable $exception) {
            report($exception);
            $this->error('Импорт экипировки не завершился: '.$exception->getMessage());
            return self::FAILURE;
        }

        $reports = $data['reports'] ?? [];
        $this->info("Импортировано/обновлено прототипов экипировки: {$count}.");
        foreach ($reports as $report) {
            if (is_array($report)) {
                if (! empty($report['error'])) {
                    $this->warn(sprintf('%s: источник пропущен — %s', $report['name'] ?? $report['fork'] ?? '?', $report['error']));
                } else {
                    $this->line(sprintf('%s: %s предметов, %s файлов, ошибок чтения: %s', $report['name'] ?? $report['fork'] ?? '?', $report['items'] ?? 0, $report['files'] ?? 0, $report['failed_files'] ?? 0));
                }
            }
        }
        return self::SUCCESS;
    }

    private function normalizeDescription(mixed $description): ?string
    {
        if (! is_string($description)) return null;
        $description = trim(preg_replace('/\\s+/u', ' ', $description) ?? $description);
        // SS14 prototypes often store a Fluent localization key (e.g. $item-description).
        // Showing that token as prose is misleading; leave it empty until the extractor resolves the locale.
        if ($description === '' || preg_match('/^\$[a-zA-Z0-9_.-]+$/', $description)) return null;
        return mb_substr($description, 0, 5000);
    }

}
