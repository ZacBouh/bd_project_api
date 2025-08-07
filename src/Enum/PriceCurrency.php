<?php

namespace App\Enum;

enum PriceCurrency: string
{
    case EURO = 'euro';           // 10.0
    private const LABELS = [
        'euro' => 'euro',
    ];

    public function label(): string
    {
        return self::LABELS[$this->value] ?? $this->value;
    }
}
