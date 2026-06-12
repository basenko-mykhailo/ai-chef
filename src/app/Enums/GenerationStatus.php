<?php

namespace App\Enums;

/**
 * Lifecycle of the async recipe-generation job (ticket 3.8). Backing values are
 * stored in `recipes.generation_status`; the frontend polls for them.
 */
enum GenerationStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

    /** Short Ukrainian label for display. */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'У черзі',
            self::Processing => 'Генерується',
            self::Completed => 'Готово',
            self::Failed => 'Помилка',
        };
    }
}
