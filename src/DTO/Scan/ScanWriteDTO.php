<?php

namespace App\DTO\Scan;

use App\Entity\UploadedImage;
use App\Enum\BookScanPart;
use Symfony\Component\HttpFoundation\File\File;

class ScanWriteDTO
{
    public function __construct(
        public BookScanPart $bookPart,
        public string $bookPartLabel,
        public File $imageFile,
        public int $user,
        public ?int $id
    ) {}
}
