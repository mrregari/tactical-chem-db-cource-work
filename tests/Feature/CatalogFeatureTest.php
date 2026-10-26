<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Reaction;
use App\Models\Reagent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_is_public_and_filters_out_drafts(): void
    {
        $author = User::factory()->create();
        $category = Category::create(['name' => 'Тест', 'type' => 'reagent']);
        Reagent::create(['name' => 'Доступный', 'category_id' => $category->id, 'user_id' => $author->id, 'status' => 'published']);
        $draft = Reagent::create(['name' => 'Секретный', 'category_id' => $category->id, 'user_id' => $author->id, 'status' => 'draft']);

        $this->get('/reagents?q=Доступный')->assertOk()->assertSee('Доступный')->assertDontSee('Секретный');
        $this->get(route('reagents.show', $draft))->assertNotFound();
        $this->actingAs($author)->get(route('reagents.show', $draft))->assertOk();
    }

    public function test_registration_ignores_admin_role_from_input(): void
    {
        $this->post('/register', [
            'name' => 'Участник', 'email' => 'member@example.test',
            'password' => 'StrongPassword123!', 'password_confirmation' => 'StrongPassword123!',
            'role' => 'admin',
        ])->assertRedirect(route('home'));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', ['email' => 'member@example.test', 'role' => 'user']);
        $this->get(route('admin.index'))->assertForbidden();
    }

    public function test_moderation_and_calculator_use_only_published_data(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Тест', 'type' => 'reagent']);
        $source = Reagent::create(['name' => 'Источник', 'category_id' => $category->id, 'user_id' => $author->id, 'status' => 'published']);
        $result = Reagent::create(['name' => 'Результат', 'category_id' => $category->id, 'user_id' => $author->id, 'status' => 'published']);
        $reaction = Reaction::create(['name' => 'Тест', 'result_reagent_id' => $result->id, 'ingredients' => [$source->id => 2], 'user_id' => $author->id, 'status' => 'draft']);

        $this->get(route('reactions.index', ['reagent' => $result->id, 'quantity' => 3]))->assertOk()->assertSee('Опубликованного рецепта пока нет');
        $this->actingAs($author)->post(route('admin.reactions.approve', $reaction))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.reactions.approve', $reaction))->assertRedirect();
        $this->get(route('reactions.index', ['reagent' => $result->id, 'quantity' => 3]))->assertOk()->assertSee('6 ед.');
    }

    public function test_favorites_and_comments_require_published_content(): void
    {
        $author = User::factory()->create();
        $category = Category::create(['name' => 'Тест', 'type' => 'reagent']);
        $draft = Reagent::create(['name' => 'Секрет', 'category_id' => $category->id, 'user_id' => $author->id, 'status' => 'draft']);
        $this->actingAs($author)->post(route('favorites.toggle', ['reagent', $draft->id]))->assertNotFound();
        $this->post(route('comments.store'), ['type' => 'reagent', 'id' => $draft->id, 'content' => 'Привет'])->assertNotFound();
        $draft->update(['status' => 'published']);
        $this->post(route('favorites.toggle', ['reagent', $draft->id]))->assertRedirect();
        $this->get(route('favorites.index'))->assertOk()->assertSee('Секрет');
        $this->post(route('comments.store'), ['type' => 'reagent', 'id' => $draft->id, 'content' => 'Привет'])->assertRedirect();
        $this->get(route('reagents.show', $draft))->assertSee('Привет');
    }

    public function test_forms_admin_and_loadout_submission_render(): void
    {
        $author = User::factory()->create();
        $this->assertSame('user', $author->role);
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Техника', 'type' => 'equipment']);
        $this->get(route('login'))->assertOk();
        $this->get(route('register'))->assertOk();
        $this->get(route('loadouts.index'))->assertOk();
        $this->actingAs($author)->get(route('reagents.create'))->assertOk();
        $this->get(route('loadouts.create'))->assertOk();
        $this->get(route('reactions.create'))->assertOk();
        $this->post(route('loadouts.store'), [
            'name' => 'Тестовый билд', 'role' => 'Стрелок', 'category_id' => $category->id,
            'items' => [['item_name' => 'Шлем', 'slot' => 'helmet', 'quantity' => 1]],
        ])->assertRedirect();
        $this->assertDatabaseHas('equipment_loadouts', ['name' => 'Тестовый билд', 'status' => 'draft']);
        $this->actingAs($admin)->get(route('admin.index'))->assertOk()->assertSee('Тестовый билд');
    }

    public function test_reagent_and_reaction_submissions_need_moderation(): void
    {
        $author = User::factory()->create();
        $admin = User::factory()->create(['role' => 'admin']);
        $category = Category::create(['name' => 'Химия', 'type' => 'reagent']);
        $this->actingAs($author)->post(route('reagents.store'), [
            'name' => 'Новый реагент', 'category_id' => $category->id, 'color' => '#ffaa00',
        ])->assertRedirect();
        $reagent = Reagent::where('name', 'Новый реагент')->firstOrFail();
        $this->assertSame('draft', $reagent->status);
        $this->get(route('reagents.index'))->assertDontSee('Новый реагент');
        $this->actingAs($admin)->post(route('admin.reagents.approve', $reagent))->assertRedirect();
        $this->get(route('reagents.index'))->assertSee('Новый реагент');
        $this->actingAs($author)->post(route('reactions.store'), [
            'name' => 'Новый рецепт', 'result_reagent_id' => $reagent->id,
            'ingredients' => [$reagent->id => 2],
        ])->assertRedirect(route('reactions.index'));
        $this->assertDatabaseHas('reactions', ['name' => 'Новый рецепт', 'status' => 'draft']);
    }

    public function test_invalid_ingredient_keys_and_wrong_category_are_rejected(): void
    {
        $author = User::factory()->create();
        $equipment = Category::create(['name' => 'Броня', 'type' => 'equipment']);
        $reagentCategory = Category::create(['name' => 'Химия', 'type' => 'reagent']);
        $result = Reagent::create(['name' => 'Результат', 'category_id' => $reagentCategory->id, 'user_id' => $author->id, 'status' => 'published']);
        $this->actingAs($author)->post(route('reagents.store'), ['name' => 'Ошибка', 'category_id' => $equipment->id])
            ->assertSessionHasErrors('category_id');
        $this->post(route('reactions.store'), [
            'name' => 'Ошибка', 'result_reagent_id' => $result->id, 'ingredients' => ['01' => 2],
        ])->assertSessionHasErrors('ingredients');
        $this->assertDatabaseCount('reactions', 0);
    }

    public function test_guest_role_is_read_only(): void
    {
        $guest = User::factory()->create(['role' => 'guest']);
        $this->actingAs($guest)->get(route('reagents.index'))->assertOk();
        $this->get(route('reagents.create'))->assertForbidden();
        $this->get(route('favorites.index'))->assertForbidden();
        $this->post(route('comments.store'), ['type' => 'reagent', 'id' => 1, 'content' => 'Тест'])->assertForbidden();
    }

    public function test_seeding_is_repeatable(): void
    {
        $this->seed();
        $this->seed();
        $this->assertDatabaseCount('reactions', 0);
        $this->assertDatabaseCount('reagents', 0);
        $this->get(route('reagents.index'))->assertOk();
    }
}
