<?php

namespace App\DTO\PublisherCollection;

use App\DTO\UploadedImage\UploadedImageReadDTO;

class PublisherCollectionReadDTO
{
    /**
     * @param array{'id':int, 'name':string} $titles
     * @param array{'id':int, 'name':string} $publisher
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
