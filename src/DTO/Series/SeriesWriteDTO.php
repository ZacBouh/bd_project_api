<?php

namespace App\DTO\Series;

use App\Enum\Language;
use App\Enum\OnGoingStatus;

class SeriesWriteDTO
{
    public function __construct(
        public string $name,
        public int $publisherId,
        public Language $language,
        public ?int $id,
        public ?array $titlesId,
        public ?OnGoingStatus $onGoingStatus,
    ) {}
}
