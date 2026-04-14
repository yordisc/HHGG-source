<?php

namespace App\Enums;

enum QuestionType: string
{
    case MCQ_2 = 'mcq_2';
    case MCQ_4 = 'mcq_4';

    public static function values(): array
    {
        return array_map(static fn(self $type) => $type->value, self::cases());
    }
}
