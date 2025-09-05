<?php

namespace App\DTO\PublisherCollection;

use App\DTO\UploadedImage\UploadedImageReadDTO;
use App\Entity\Trait\HasDefaultNormalizeCallback;

/**
 * @phpstan-import-type NormalizeCallbackDefaultReturn from HasDefaultNormalizeCallback
 */
class PublisherCollectionReadDTO
{
    /**
     * @param array<NormalizeCallbackDefaultReturn> $titles
     * @param NormalizeCallbackDefaultReturn $publisher
     */
    public function __construct(
        public int $id,
        public string $name,
        public array $publisher,
        public string $language,
        public string $createdAt,
        public string $updatedAt,
        public ?array $titles,
        public ?string $description,
        public ?string $birthDate,
        public ?string $deathDate,
        public ?UploadedImageReadDTO $coverImage,
    ) {}
}
