<?php

namespace App\DTO\Title;

use App\Enum\Language;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TitleWriteDTO
{
    /**
     * @param int[] $artistsContributions
     * @param UploadedFile[] $uploadedImages
     */
    public function __construct(
        public string $name,
        public int $publisher,
        public Language $language,
        public ?int $id,
        public ?string $description,
        public ?UploadedFile $coverImageFile,
        public ?array $artistsContributions,
        public ?string $releaseDate,
        public ?array $uploadedImages,
    ) {}
}
