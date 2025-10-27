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

    /**
     * @return array<string, int>
     */
    private static function qualityRanking(): array
    {
        return [
            self::MINT->value => 8,
            self::NEAR_MINT->value => 7,
            self::VERY_FINE->value => 6,
            self::FINE->value => 5,
            self::VERY_GOOD->value => 4,
            self::GOOD->value => 3,
            self::FAIR->value => 2,
            self::POOR->value => 1,
        ];
    }

    public function label(): string
    {
        return self::LABELS[$this->value] ?? $this->value;
    }

    public function qualityRank(): int
    {
        return self::qualityRanking()[$this->value];
    }

    /**
     * @return list<CopyCondition>
     */
    public function sameOrBetter(): array
    {
        $ranking = self::qualityRanking();
        $minimumRank = $ranking[$this->value];

        return array_values(array_filter(
            self::cases(),
            static fn (CopyCondition $condition): bool => $ranking[$condition->value] >= $minimumRank
        ));
    }

    /**
     * @return non-empty-string[]
     */
    public function sameOrBetterValues(): array
    {
        return array_map(
            static fn (CopyCondition $condition): string => $condition->value,
            $this->sameOrBetter()
        );
    }

    /**
     * @return non-empty-string[]
     */
    public static function values(): array
    {
        return array_map(static fn($case) => $case->value, self::cases());
    }
}
