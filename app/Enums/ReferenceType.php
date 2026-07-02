<?php

namespace App\Enums;

enum ReferenceType: string
{
    case Professional = 'professional';
    case Character = 'character';
    case Academic = 'academic';

    public function label(): string
    {
        return match ($this) {
            self::Professional => 'Professional',
            self::Character => 'Character',
            self::Academic => 'Academic',
        };
    }
}
