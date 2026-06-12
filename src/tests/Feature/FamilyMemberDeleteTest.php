<?php

namespace Tests\Feature;

use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyMemberDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_delete(): void
    {
        $member = FamilyMember::factory()->create();

        $this->delete(route('family.destroy', $member))->assertRedirect('/login');
        $this->assertDatabaseHas('family_members', ['id' => $member->id]);
    }

    public function test_user_can_delete_own_member(): void
    {
        $user = User::factory()->create();
        $member = FamilyMember::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete(route('family.destroy', $member))
            ->assertRedirect(route('family.index'));

        $this->assertDatabaseMissing('family_members', ['id' => $member->id]);
    }

    public function test_user_cannot_delete_other_users_member(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $member = FamilyMember::factory()->create(['user_id' => $other->id]);

        $this->actingAs($user)->delete(route('family.destroy', $member))->assertForbidden();
        $this->assertDatabaseHas('family_members', ['id' => $member->id]);
    }
}
