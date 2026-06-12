<?php

namespace App\Enums;

/**
 * Closed set of pantry / recipe measurement units.
 * Backing values are stored in the DB; labels() are the Ukrainian UI text.
 */
enum Unit: string
{
    case Gram = 'g';
    case Kilogram = 'kg';
    case Milliliter = 'ml';
    case Liter = 'l';
    case Piece = 'pcs';
    case Tablespoon = 'tbsp';
    case Teaspoon = 'tsp';
    case Cup = 'cup';

    /** Short Ukrainian label for display. */
    public function label(): string
    {
        return match ($this) {
            self::Gram => 'г',
            self::Kilogram => 'кг',
            self::Milliliter => 'мл',
            self::Liter => 'л',
            self::Piece => 'шт',
            self::Tablespoon => 'ст.л.',
            self::Teaspoon => 'ч.л.',
            self::Cup => 'склянка',
        };
    }

    /**
     * Backing values — handy for validation rules (`Rule::in(Unit::values())`).
     *
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * value => Ukrainian label map, for building <select> options.
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $unit) {
            $options[$unit->value] = $unit->label();
        }

        return $options;
    }
}
