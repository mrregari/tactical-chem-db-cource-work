<?php

namespace Tests\Feature;

use App\Models\Reagent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ChemistrySyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_is_repeatable_and_scales_by_product_amount(): void
    {
        $path = storage_path('framework/testing/chemistry-test.json');
        file_put_contents($path, json_encode([
            'reagents' => [
                'Water' => ['name' => 'Water', 'category' => 'Elements', 'source' => 'vanilla'],
                'Medicine' => ['name' => 'Medicine', 'category' => 'Medicine', 'source' => 'stories_cm'],
            ],
            'reactions' => [
                'MakeMedicine' => ['source' => 'stories_cm', 'reactants' => ['Water' => ['amount' => 3]], 'products' => ['Medicine' => 2]],
                'Broken' => ['source' => 'stories_cm', 'reactants' => ['Missing' => ['amount' => 1]], 'products' => ['Medicine' => 1]],
            ],
        ]));

        try {
            $this->assertSame(0, Artisan::call('chemistry:sync', ['--file' => $path]));
            $this->assertSame(0, Artisan::call('chemistry:sync', ['--file' => $path]));
            $this->assertDatabaseCount('reagents', 2);
            $this->assertDatabaseCount('reactions', 1);
            $this->assertDatabaseHas('reagents', ['upstream_id' => 'Medicine', 'source_fork' => 'stories_cm']);
            $this->assertDatabaseHas('reactions', ['upstream_id' => 'MakeMedicine', 'product_amount' => 2]);
            $medicine = Reagent::where('upstream_id', 'Medicine')->firstOrFail();
            $this->get(route('reactions.index', ['reagent' => $medicine->id, 'quantity' => 4]))
                ->assertOk()->assertSee('6 ед.');
        } finally {
            unlink($path);
        }
    }

    public function test_incomplete_input_does_not_modify_database(): void
    {
        User::factory()->create();
        $this->assertSame(1, Artisan::call('chemistry:sync', ['--file' => storage_path('framework/testing/missing.json')]));
        $this->assertDatabaseCount('reagents', 0);
    }
}
