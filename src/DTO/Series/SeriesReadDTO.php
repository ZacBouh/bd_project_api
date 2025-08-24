<?php

namespace App\DTO\Series;

use App\DTO\Publisher\PublisherReadDTO;

class SeriesReadDTO
{
    /** @param int[]|null $titles */
    public function __construct(
        public int $id,

        public string $name,

        public array $publisher,

        public string $language,

        public string $createdAt,

        public string $updatedAt,

        public ?array $titles,

        public ?string $onGoingStatus,

        public ?array $coverImage
    ) {}
}
