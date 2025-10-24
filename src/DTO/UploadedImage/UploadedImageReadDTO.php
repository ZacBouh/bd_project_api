<?php

namespace App\DTO\UploadedImage;

use App\Entity\UploadedImage;
use OpenApi\Attributes as OA;

/**
 *  @phpstan-import-type UploadedImageDimensions from UploadedImage
 *  @phpstan-property UploadedImageDimensions|null $imageDimensions
 */
#[OA\Schema(description: 'Informations sur une image téléversée')]
class UploadedImageReadDTO
{
    /**
     * @phpstan-param UploadedImageDimensions|null $imageDimensions
     */
    public function __construct(
        #[OA\Property(example: 101)]
        public int $id,

        #[OA\Property(example: 'Cover front')]
        public string $imageName,

        #[OA\Property(example: 'cover-front.jpg')]
        public string $fileName,

        #[OA\Property(example: 'https://cdn.example.com/images/cover-front.jpg')]
        public string $url,

        #[OA\Property(format: 'date-time')]
        public string $createdAt,

        #[OA\Property(format: 'date-time')]
        public string $updatedAt,

        #[OA\Property(nullable: true, example: 204800)]
        public ?int $fileSize,

        #[OA\Property(nullable: true, example: 'front-original.png')]
        public ?string $originalFileName,

        #[OA\Property(nullable: true, example: 'image/png')]
        public ?string $fileMimeType,

        #[OA\Property(
            nullable: true,
            type: 'object',
            properties: [
                new OA\Property(property: 'width', type: 'integer', example: 600),
                new OA\Property(property: 'height', type: 'integer', example: 900),
            ]
        )]
        public ?array $imageDimensions,
    ) {}
}
