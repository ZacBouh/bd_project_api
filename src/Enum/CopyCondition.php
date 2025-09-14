<?php

namespace App\Enum;

enum CopyCondition: string
{
    case MINT = 'mint';           // 10.0
    case NEAR_MINT = 'near_mint'; // 9.4+
    case VERY_FINE = 'very_fine';
    case FINE = 'fine';
    case VERY_GOOD = 'very_good';
    case GOOD = 'good';
    case FAIR = 'fair';
    case POOR = 'poor';

    private const LABELS = [
        'new' => 'New',
        'like_new' => 'Like New',
        'good' => 'Good',
        'used' => 'Used',
        'very_used' => 'Very Used',
        'poor' => 'Poor',
    ];

    public function label(): string
    {
        return self::LABELS[$this->value] ?? $this->value;
    }

    /**
     * @return non-empty-string[]
     */
    public function values(): array
    {
        return array_map(fn($case) => $case->value, self::cases());
    }
}
