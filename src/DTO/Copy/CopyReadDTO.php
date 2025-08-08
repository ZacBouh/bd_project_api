<?php

namespace App\DTO\Copy;

use App\Enum\CopyCondition;
use App\Enum\PriceCurrency;

class CopyReadDTO
{
    public function __construct(
        private int $id,
        private int $ownerId,
        private int $titleId,
        private ?CopyCondition $copyCondition,
        private ?float $price,
        private ?PriceCurrency $currency,
        private ?float $boughtForPrice,
        private ?PriceCurrency $boughtForCurrency,
    ) {}
}
