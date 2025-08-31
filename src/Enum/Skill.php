<?php

namespace App\Enum;


enum Skill: string
{
    case COLORIST = 'COLORIST';
    case COVER_ARTIST = 'COVER ARTIST';
    case CREATOR = 'CREATOR';
    case EDITOR = 'EDITOR';
    case INKER = 'INKER';
    case LETTERER = 'LETTERER';
    case PENCILLER = 'PENCILLER';
    case WRITER = 'WRITER';

    /** @return array<non-empty-string>  */
    public static function values(): array
    {
        return array_map(static fn(self $case): string => $case->value, self::cases());
    }
}
