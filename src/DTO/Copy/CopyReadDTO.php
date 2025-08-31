<?php

namespace App\DTO\Copy;

use App\DTO\UploadedImage\UploadedImageReadDTO;
use App\Entity\Trait\HasDefaultNormalizeCallback;

/**
 * @phpstan-import-type NormalizeCallbackDefaultReturn from HasDefaultNormalizeCallback
 */
class CopyReadDTO
{
    /**
     * @param array<UploadedImageReadDTO> $uploadedImages
     * @param array<NormalizeCallbackDefaultReturn> $owner
     * @param array<NormalizeCallbackDefaultReturn> $title
     * @param array<UploadedImageReadDTO> $uploadedImages
     */
    public function __construct(
        public int $id,
        public array $owner,
        public array $title,
        public string $copyCondition,
        public string $createdAt,
        public string $updatedAt,
        public ?float $price,
        public ?string $currency,
        public ?float $boughtForPrice,
        public ?string $boughtForCurrency,
        public ?UploadedImageReadDTO $coverImage,
        public ?array $uploadedImages,
    ) {}
}
