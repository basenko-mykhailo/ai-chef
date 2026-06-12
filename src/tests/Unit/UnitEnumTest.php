<?php

namespace Tests\Unit;

use App\Enums\Unit;
use PHPUnit\Framework\TestCase;

class UnitEnumTest extends TestCase
{
    public function test_has_the_eight_supported_units(): void
    {
        $this->assertCount(8, Unit::cases());
        $this->assertSame(
            ['g', 'kg', 'ml', 'l', 'pcs', 'tbsp', 'tsp', 'cup'],
            Unit::values(),
        );
    }

    public function test_each_unit_has_a_ukrainian_label(): void
    {
        $this->assertSame('кг', Unit::Kilogram->label());
        $this->assertSame('шт', Unit::Piece->label());
        $this->assertSame('ст.л.', Unit::Tablespoon->label());
    }

    public function test_options_map_value_to_label(): void
    {
        $options = Unit::options();

        $this->assertSame('г', $options['g']);
        $this->assertSame('склянка', $options['cup']);
        $this->assertCount(8, $options);
    }
}
