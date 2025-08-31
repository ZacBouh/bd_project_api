<?php

namespace App\DTO\Series;

use App\DTO\Publisher\PublisherReadDTO;
use App\DTO\UploadedImage\UploadedImageReadDTO;
use App\Entity\Trait\HasDefaultNormalizeCallback;

/**
 * @phpstan-import-type NormalizeCallbackDefaultReturn from HasDefaultNormalizeCallback
 */
class SeriesReadDTO
{
    /** 
     * @param int[]|null $titles 
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

        public ?string $onGoingStatus,

        public ?UploadedImageReadDTO $coverImage,

    ) {}
}
