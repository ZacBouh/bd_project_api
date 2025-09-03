<?php

namespace App\DTO\UploadedImage;

use App\Entity\UploadedImage;

/**
 *  @phpstan-import-type UploadedImageDimensions from UploadedImage
 *  @phpstan-property UploadedImageDimensions|null $imageDimensions
 */
class UploadedImageReadDTO
{
    /**
     * @phpstan-param UploadedImageDimensions|null $imageDimensions
     */
    public function __construct(
        public int $id,
        public string $imageName,
        public string $fileName,
        public string $url,
        public string $createdAt,
        public string $updatedAt,
        public ?int $fileSize,
        public ?string $originalFileName,
        public ?string $fileMimeType,
        public ?array $imageDimensions,
    ) {}
}
