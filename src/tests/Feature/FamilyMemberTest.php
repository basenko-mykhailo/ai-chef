<?php

namespace Tests\Feature;

use App\Models\FamilyMember;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FamilyMemberTest extends TestCase
{
    use RefreshDatabase;

    public function test_for_user_scope_filters_by_user_id(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        FamilyMember::factory()->count(3)->create(['user_id' => $userA->id]);
        FamilyMember::factory()->count(2)->create(['user_id' => $userB->id]);

        $aMembers = FamilyMember::forUser($userA->id)->get();

        $this->assertCount(3, $aMembers);
        $this->assertTrue($aMembers->every(fn (FamilyMember $m) => $m->user_id === $userA->id));
    }

    public function test_user_has_many_family_members(): void
    {
        $user = User::factory()->create();
        FamilyMember::factory()->count(2)->create(['user_id' => $user->id]);

        $members = $user->familyMembers;

        $this->assertInstanceOf(Collection::class, $members);
        $this->assertCount(2, $members);
        $this->assertInstanceOf(FamilyMember::class, $members->first());
    }

    public function test_factory_creates_valid_family_member(): void
    {
        $member = FamilyMember::factory()->create();

        $this->assertDatabaseHas('family_members', ['id' => $member->id]);
        $this->assertNotNull($member->user_id);
        $this->assertNotEmpty($member->name);
        $this->assertInstanceOf(User::class, $member->user);
    }
}
