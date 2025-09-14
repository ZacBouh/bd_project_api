<?php

namespace App\Enum;

enum BookScanPart: string
{
    case FRONT_COVER = "FRONT_COVER";
    case BACK_COVER = "BACK_COVER";
    case SPINE = "SPINE";
    case INSIDE_FRONT_COVER = "INSIDE_FRONT_COVER";
    case INSIDE_BACK_COVER = "INSIDE_BACK_COVER";
    case TITLE_PAGE = "TITLE_PAGE";
    case INTERIOR_PAGE = "INTERIOR_PAGE";

    /**
     * @return non-empty-string[]
     */
    public function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
