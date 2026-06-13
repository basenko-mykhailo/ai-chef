<?php

namespace Tests\Feature;

use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyMemberIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $response = $this->get('/family');

        $response->assertRedirect('/login');
    }

    public function test_authed_user_sees_only_own_family_members(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        FamilyMember::factory()->create([
            'user_id' => $user->id,
            'name' => 'Мама',
            'favorite_products' => 'Салати, риба',
        ]);
        FamilyMember::factory()->create([
            'user_id' => $other->id,
            'name' => 'Стороння Особа',
        ]);

        $response = $this->actingAs($user)->get('/family');

        $response->assertOk();
        $response->assertSee('Мама');
        $response->assertSee('Салати, риба');
        $response->assertDontSee('Стороння Особа');
    }

    public function test_user_with_no_members_sees_empty_state(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/family');

        $response->assertOk();
        $response->assertSee('Ще немає членів сім\'ї', false);
        $response->assertSee('Додати члена сім\'ї', false);
    }
}
