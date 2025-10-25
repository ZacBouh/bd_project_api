<?php

namespace App\DTO\Publisher;

use App\DTO\Title\TitleReadDTO;
use App\DTO\UploadedImage\UploadedImageReadDTO;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Informations publiques d’un éditeur.')] 
class PublisherReadDTO
{
    /**
     * @param array<UploadedImageReadDTO>  $uploadedImages
     * @param array<TitleReadDTO>  $titles
     */
    public function __construct(
        #[OA\Property(example: 5)]
        public int $id,

        #[OA\Property(example: 'Kana')]
        public string $name,

        #[OA\Property(nullable: true, example: 'Maison d’édition spécialisée dans le manga.')]
        public ?string $description,

        #[OA\Property(ref: new Model(type: UploadedImageReadDTO::class), nullable: true)]
        public ?UploadedImageReadDTO $coverImage,

        #[OA\Property(
            nullable: true,
            type: 'array',
            items: new OA\Items(ref: new Model(type: UploadedImageReadDTO::class))
        )]
        public ?array $uploadedImages,

        #[OA\Property(
            nullable: true,
            type: 'array',
            items: new OA\Items(ref: new Model(type: TitleReadDTO::class))
        )]
        public ?array $titles,

        #[OA\Property(nullable: true, example: '1988-01-01')]
        public ?string $birthDate,

        #[OA\Property(nullable: true, example: '2002-12-31')]
        public ?string $deathDate,

        #[OA\Property(format: 'date-time')]
        public string $createdAt,

        #[OA\Property(format: 'date-time')]
        public string $updatedAt,
    ) {}
}
