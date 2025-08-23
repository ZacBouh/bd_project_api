<?php

namespace App\DTO\Copy;

use App\Enum\CopyCondition;
use App\Enum\PriceCurrency;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CopyReadDTO
{
    public function __construct(
        public int $id,
        public ?array $owner,
        public ?array $title,
        public ?CopyCondition $copyCondition,
        public ?float $price,
        public ?PriceCurrency $currency,
        public ?float $boughtForPrice,
        public ?PriceCurrency $boughtForCurrency,
        public ?array $coverImage,
        public ?array $uploadedImages,
        public ?\DateTime $createdAt,
        public ?\DateTime $updatedAt,
    ) {}
}
