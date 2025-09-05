<?php

namespace App\DTO\Publisher;

use App\DTO\UploadedImage\UploadedImageReadDTO;
use App\DTO\Title\TitleReadDTO;

class PublisherReadDTO
{
    /**
     * @param array<UploadedImageReadDTO>  $uploadedImages
     * @param array<TitleReadDTO>  $titles
     */
    public function __construct(
        public int $id,
        public string $name,
        public ?string $description,
        public ?UploadedImageReadDTO $coverImage,
        public ?array $uploadedImages,
        public ?array $titles,
        public ?string $birthDate,
        public ?string $deathDate,
        public string $createdAt,
        public string $updatedAt,
    ) {}
}
