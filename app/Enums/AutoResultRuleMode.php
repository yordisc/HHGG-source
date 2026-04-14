<?php

namespace App\Enums;

enum AutoResultRuleMode: string
{
    case NONE = 'none';
    case NAME_RULE = 'name_rule';

    public static function values(): array
    {
        return array_map(static fn(self $mode) => $mode->value, self::cases());
    }
}
