<?php

namespace Tests\Feature;

use Tests\TestCase;

class GameSourceConfigTest extends TestCase
{
    public function test_all_fork_manifests_are_available_to_laravel_and_extractor(): void
    {
        $sources = config('game_sources');

        $this->assertIsArray($sources);
        $this->assertGreaterThanOrEqual(20, count($sources));
        $this->assertArrayHasKey('vanilla', $sources);
        $this->assertArrayHasKey('rucm', $sources);
        $this->assertSame('cmu', $sources['rucm']['parent_fork']);

        foreach ($sources as $forkId => $source) {
            $this->assertNotEmpty($source['raw_url'] ?? null, "Missing raw URL for {$forkId}");
            $this->assertIsArray($source['reagent_files'] ?? null, "Missing reagent manifest for {$forkId}");
            $this->assertIsArray($source['reaction_files'] ?? null, "Missing reaction manifest for {$forkId}");
        }
    }
}
