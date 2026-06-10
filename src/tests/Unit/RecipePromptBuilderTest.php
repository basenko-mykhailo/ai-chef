<?php

namespace Tests\Unit;

use App\Models\FamilyMember;
use App\Models\User;
use App\Services\RecipePromptBuilder;
use App\Services\RecipeSchema;
use PHPUnit\Framework\TestCase;

class RecipePromptBuilderTest extends TestCase
{
    private RecipePromptBuilder $builder;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new RecipePromptBuilder;
        $this->user = new User;
    }

    /** @return list<array{name: string, quantity: float|int|string, unit: string}> */
    private function samplePantry(): array
    {
        return [
            ['name' => 'Картопля', 'quantity' => 1.5, 'unit' => 'кг'],
            ['name' => 'Молоко', 'quantity' => 500, 'unit' => 'мл'],
        ];
    }

    public function test_build_returns_system_and_user_prompts(): void
    {
        $prompts = $this->builder->build($this->user, [], $this->samplePantry());

        $this->assertArrayHasKey('system', $prompts);
        $this->assertArrayHasKey('user', $prompts);
        $this->assertNotSame('', $prompts['system']);
        $this->assertNotSame('', $prompts['user']);
    }

    public function test_system_prompt_contains_all_product_rules(): void
    {
        ['system' => $system] = $this->builder->build($this->user, [], $this->samplePantry());

        // JSON-only + структура зі схеми 3.5
        $this->assertStringContainsString('ЛИШЕ валідний JSON', $system);
        $this->assertStringContainsString(RecipeSchema::STRUCTURE, $system);

        // широка інтерпретація алергій з прикладом + сумнів → виключити
        $this->assertStringContainsString('ШИРОКО', $system);
        $this->assertStringContainsString('горіхи', $system);
        $this->assertStringContainsString('арахіс', $system);
        $this->assertStringContainsString('сумнів', $system);

        // AND-комбінація обмежень
        $this->assertStringContainsString('ОДНОЧАСНО', $system);

        // одиниці — закритий enum
        foreach (RecipeSchema::ALLOWED_UNITS as $unit) {
            $this->assertStringContainsString($unit, $system);
        }

        // 1-2 staples з in_pantry: false; КБЖУ на порцію
        $this->assertStringContainsString('1-2 базові інгредієнти', $system);
        $this->assertStringContainsString('"in_pantry": false', $system);
        $this->assertStringContainsString('НА ОДНУ ПОРЦІЮ', $system);
    }

    public function test_user_prompt_lists_pantry_with_quantities_and_units(): void
    {
        ['user' => $user] = $this->builder->build($this->user, [], $this->samplePantry());

        $this->assertStringContainsString('Картопля — 1.5 кг', $user);
        $this->assertStringContainsString('Молоко — 500 мл', $user);
    }

    public function test_user_prompt_includes_member_constraints_with_labels(): void
    {
        $members = [
            new FamilyMember([
                'name' => 'Бабуся',
                'allergies_and_diets' => 'горіхи, безлактозна дієта',
                'disliked_products' => 'селера',
                'favorite_products' => 'гриби',
            ]),
        ];

        ['user' => $user] = $this->builder->build($this->user, $members, $this->samplePantry());

        $this->assertStringContainsString('Бабуся', $user);
        $this->assertStringContainsString('суворо виключити): горіхи, безлактозна дієта', $user);
        $this->assertStringContainsString('уникати): селера', $user);
        $this->assertStringContainsString('бажано врахувати): гриби', $user);
    }

    public function test_member_with_empty_fields_gets_no_empty_constraint_lines(): void
    {
        $members = [new FamilyMember(['name' => 'Син'])];

        ['user' => $user] = $this->builder->build($this->user, $members, $this->samplePantry());

        $this->assertStringContainsString('Син', $user);
        $this->assertStringNotContainsString('суворо виключити', $user);
        $this->assertStringNotContainsString('уникати', $user);
        $this->assertStringNotContainsString('бажано врахувати', $user);
    }

    public function test_no_members_omits_constraints_block(): void
    {
        ['user' => $user] = $this->builder->build($this->user, [], $this->samplePantry());

        $this->assertStringNotContainsString('Готуємо для таких людей', $user);
        $this->assertStringContainsString('Згенеруй один рецепт', $user);
    }

    public function test_empty_pantry_does_not_break_build(): void
    {
        ['user' => $user] = $this->builder->build($this->user, [], []);

        $this->assertStringContainsString('Комора порожня', $user);
        $this->assertStringContainsString('Згенеруй один рецепт', $user);
    }
}
