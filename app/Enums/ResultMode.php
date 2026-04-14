<?php

namespace App\Enums;

enum ResultMode: string
{
    case BINARY_THRESHOLD = 'binary_threshold';
    case CUSTOM = 'custom';
    case GENERIC = 'generic';

    public static function values(): array
    {
        return array_map(static fn(self $mode) => $mode->value, self::cases());
    }
}
