<?php

namespace App\DTO\Copy;

use App\Enum\CopyCondition;
use App\Enum\PriceCurrency;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class CopyWriteDTO
{
    public function __construct(
        public int $owner,
        public int $title,
        public CopyCondition $copyCondition,
        public ?int $id,
        public ?float $price,
        public ?PriceCurrency $currency,
        public ?float $boughtForPrice,
        public ?PriceCurrency $boughtForCurrency,
        public ?UploadedFile $coverImageFile,
        /**
         * @var array<UploadedFile>
         */
        public ?array $uploadedImages,
    ) {}
}
