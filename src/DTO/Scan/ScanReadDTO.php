<?php

namespace App\DTO\Scan;

use App\Enum\BookScanPart;

class ScanReadDTO
{
    public function __construct(
        public int $id,
        public BookScanPart $bookPart,
        public int $uploadedImage,
        public int $user,
    ) {}
}
