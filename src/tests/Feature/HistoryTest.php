<?php

namespace Tests\Feature;

use App\Enums\GenerationStatus;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('history.index'))->assertRedirect('/login');
    }

    public function test_lists_only_own_completed_recipes(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        Recipe::factory()->for($user)->completed()->create(['name' => 'Мій борщ']);
        Recipe::factory()->for($other)->completed()->create(['name' => 'Чужий суп']);
        Recipe::factory()->for($user)->create([
            'name' => 'Ще генерується',
            'generation_status' => GenerationStatus::Pending,
        ]);

        $this->actingAs($user)->get(route('history.index'))
            ->assertOk()
            ->assertSee('Мій борщ')
            ->assertDontSee('Чужий суп')        // isolation
            ->assertDontSee('Ще генерується');  // non-completed excluded
    }

    public function test_cooked_filter_shows_only_cooked(): void
    {
        $user = User::factory()->create();
        Recipe::factory()->for($user)->completed()->create([
            'name' => 'Готували це', 'status' => 'cooked', 'cooked_at' => now(),
        ]);
        Recipe::factory()->for($user)->completed()->create([
            'name' => 'Лише згенеровано', 'status' => 'generated',
        ]);

        $this->actingAs($user)->get(route('history.index', ['filter' => 'cooked']))
            ->assertOk()
            ->assertSee('Готували це')
            ->assertDontSee('Лише згенеровано');
    }

    public function test_favorites_filter_shows_only_favorites(): void
    {
        $user = User::factory()->create();
        Recipe::factory()->for($user)->completed()->create(['name' => 'Улюблений', 'is_favorite' => true]);
        Recipe::factory()->for($user)->completed()->create(['name' => 'Звичайний', 'is_favorite' => false]);

        $this->actingAs($user)->get(route('history.index', ['filter' => 'favorites']))
            ->assertOk()
            ->assertSee('Улюблений')
            ->assertDontSee('Звичайний');
    }

    public function test_empty_state_shown_when_no_recipes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('history.index'))
            ->assertOk()
            ->assertSee('Історія порожня.');
    }
}
