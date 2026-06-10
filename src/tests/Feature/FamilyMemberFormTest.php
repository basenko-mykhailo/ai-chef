<?php

namespace Tests\Feature;

use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyMemberFormTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_create_form(): void
    {
        $this->get(route('family.create'))->assertRedirect('/login');
    }

    public function test_authed_user_can_view_create_form(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('family.create'))
            ->assertOk()
            ->assertSee('Новий член сім\'ї', false);
    }

    public function test_user_can_create_family_member(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('family.store'), [
            'name' => 'Бабуся',
            'favorite_products' => 'Каші, фрукти',
            'disliked_products' => 'Гострі страви',
            'allergies_and_diets' => 'Без цукру',
        ]);

        $response->assertRedirect(route('family.index'));
        $this->assertDatabaseHas('family_members', [
            'user_id' => $user->id,
            'name' => 'Бабуся',
            'favorite_products' => 'Каші, фрукти',
            'allergies_and_diets' => 'Без цукру',
        ]);
    }

    public function test_name_is_required_when_creating(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('family.store'), [
            'name' => '',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertDatabaseCount('family_members', 0);
    }

    public function test_user_id_cannot_be_spoofed_on_create(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        $this->actingAs($user)->post(route('family.store'), [
            'name' => 'Підставний',
            'user_id' => $other->id, // must be ignored — bound to current user
        ]);

        $this->assertDatabaseHas('family_members', [
            'name' => 'Підставний',
            'user_id' => $user->id,
        ]);
    }

    public function test_user_can_view_edit_form_for_own_member(): void
    {
        $user = User::factory()->create();
        $member = FamilyMember::factory()->create([
            'user_id' => $user->id,
            'name' => 'Тато',
        ]);

        $this->actingAs($user)
            ->get(route('family.edit', $member))
            ->assertOk()
            ->assertSee('Тато');
    }

    public function test_user_cannot_view_edit_form_for_other_users_member(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $member = FamilyMember::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)
            ->get(route('family.edit', $member))
            ->assertForbidden();
    }

    public function test_user_can_update_own_member(): void
    {
        $user = User::factory()->create();
        $member = FamilyMember::factory()->create([
            'user_id' => $user->id,
            'name' => 'Син',
            'allergies_and_diets' => null,
        ]);

        $response = $this->actingAs($user)->patch(route('family.update', $member), [
            'name' => 'Син Олег',
            'favorite_products' => 'Піца',
            'disliked_products' => '',
            'allergies_and_diets' => 'Без глютену',
        ]);

        $response->assertRedirect(route('family.index'));
        $this->assertDatabaseHas('family_members', [
            'id' => $member->id,
            'name' => 'Син Олег',
            'allergies_and_diets' => 'Без глютену',
        ]);
    }

    public function test_user_cannot_update_other_users_member(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $member = FamilyMember::factory()->create([
            'user_id' => $other->id,
            'name' => 'Чужий',
        ]);

        $response = $this->actingAs($user)->patch(route('family.update', $member), [
            'name' => 'Зламано',
        ]);

        $response->assertForbidden();
        $this->assertDatabaseHas('family_members', [
            'id' => $member->id,
            'name' => 'Чужий',
        ]);
    }
}
