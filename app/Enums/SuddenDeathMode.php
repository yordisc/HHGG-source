<?php

namespace App\Enums;

enum SuddenDeathMode: string
{
    case NONE = 'none';
    case FAIL_IF_WRONG = 'fail_if_wrong';
    case PASS_IF_CORRECT = 'pass_if_correct';

    public static function values(): array
    {
        return array_map(static fn(self $mode) => $mode->value, self::cases());
    }
}
