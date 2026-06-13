<?php

namespace Tests\Unit;

use App\Services\RecipeCacheKeyBuilder;
use PHPUnit\Framework\TestCase;

/**
 * Чиста логіка побудови cache-ключа (тікет 3.9) — без контейнера/БД.
 */
class RecipeCacheKeyBuilderTest extends TestCase
{
    private RecipeCacheKeyBuilder $builder;

    /** @var list<array{name: string, quantity: float, unit: string}> */
    private array $pantry;

    /** @var list<array{name: string, favorite_products: ?string, disliked_products: ?string, allergies_and_diets: ?string}> */
    private array $members;

    protected function setUp(): void
    {
        parent::setUp();

        $this->builder = new RecipeCacheKeyBuilder;

        $this->pantry = [
            ['name' => 'Картопля', 'quantity' => 500.0, 'unit' => 'г'],
            ['name' => 'Морква', 'quantity' => 2.0, 'unit' => 'шт'],
        ];

        $this->members = [
            ['name' => 'Мама', 'favorite_products' => null, 'disliked_products' => null, 'allergies_and_diets' => 'горіхи'],
            ['name' => 'Тато', 'favorite_products' => 'мʼясо', 'disliked_products' => null, 'allergies_and_diets' => null],
        ];
    }

    public function test_returns_a_sha256_hex_string(): void
    {
        $key = $this->builder->build($this->pantry, $this->members);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', $key);
    }

    public function test_same_input_yields_same_key(): void
    {
        $this->assertSame(
            $this->builder->build($this->pantry, $this->members),
            $this->builder->build($this->pantry, $this->members),
        );
    }

    public function test_key_is_independent_of_pantry_order(): void
    {
        $reordered = array_reverse($this->pantry);

        $this->assertSame(
            $this->builder->build($this->pantry, $this->members),
            $this->builder->build($reordered, $this->members),
        );
    }

    public function test_key_is_independent_of_member_order(): void
    {
        $reordered = array_reverse($this->members);

        $this->assertSame(
            $this->builder->build($this->pantry, $this->members),
            $this->builder->build($this->pantry, $reordered),
        );
    }

    public function test_changing_a_pantry_quantity_changes_the_key(): void
    {
        $changed = $this->pantry;
        $changed[0]['quantity'] = 600.0;

        $this->assertNotSame(
            $this->builder->build($this->pantry, $this->members),
            $this->builder->build($changed, $this->members),
        );
    }

    public function test_changing_a_pantry_unit_changes_the_key(): void
    {
        $changed = $this->pantry;
        $changed[0]['unit'] = 'кг';

        $this->assertNotSame(
            $this->builder->build($this->pantry, $this->members),
            $this->builder->build($changed, $this->members),
        );
    }

    public function test_changing_a_pantry_name_changes_the_key(): void
    {
        $changed = $this->pantry;
        $changed[0]['name'] = 'Буряк';

        $this->assertNotSame(
            $this->builder->build($this->pantry, $this->members),
            $this->builder->build($changed, $this->members),
        );
    }

    public function test_changing_a_member_constraint_changes_the_key(): void
    {
        $changed = $this->members;
        $changed[0]['allergies_and_diets'] = 'лактоза';

        $this->assertNotSame(
            $this->builder->build($this->pantry, $this->members),
            $this->builder->build($this->pantry, $changed),
        );
    }

    public function test_member_name_does_not_affect_the_key(): void
    {
        $renamed = $this->members;
        $renamed[0]['name'] = 'Бабуся';
        $renamed[1]['name'] = 'Дідусь';

        $this->assertSame(
            $this->builder->build($this->pantry, $this->members),
            $this->builder->build($this->pantry, $renamed),
        );
    }

    public function test_empty_member_selection_yields_a_stable_pantry_only_key(): void
    {
        $this->assertSame(
            $this->builder->build($this->pantry, []),
            $this->builder->build($this->pantry, []),
        );

        $this->assertNotSame(
            $this->builder->build($this->pantry, []),
            $this->builder->build($this->pantry, $this->members),
        );
    }
}
