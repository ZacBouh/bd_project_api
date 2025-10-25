<?php

namespace App\DTO\PublisherCollection;

use App\DTO\UploadedImage\UploadedImageReadDTO;
use App\Entity\Trait\HasDefaultNormalizeCallback;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

/**
 * @phpstan-import-type NormalizeCallbackDefaultReturn from HasDefaultNormalizeCallback
 */
#[OA\Schema(description: 'Données d\'une collection d\'éditeur.')] 
class PublisherCollectionReadDTO
{
    /**
     * @param array<NormalizeCallbackDefaultReturn> $titles
     * @param NormalizeCallbackDefaultReturn $publisher
     */
    public function __construct(
        #[OA\Property(example: 12)]
        public int $id,

        #[OA\Property(example: 'Collection Signature')]
        public string $name,

        #[OA\Property(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string', nullable: true),
            ]
        )]
        public array $publisher,

        #[OA\Property(example: 'fr')]
        public string $language,

        #[OA\Property(format: 'date-time')]
        public string $createdAt,

        #[OA\Property(format: 'date-time')]
        public string $updatedAt,

        #[OA\Property(
            nullable: true,
            type: 'array',
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(property: 'id', type: 'integer'),
                    new OA\Property(property: 'name', type: 'string', nullable: true),
                ]
            )
        )]
        public ?array $titles,

        #[OA\Property(nullable: true, example: 'Anthologie dédiée aux classiques du manga.')]
        public ?string $description,

        #[OA\Property(nullable: true, example: '1995-01-01')]
        public ?string $birthDate,

        #[OA\Property(nullable: true, example: '2005-12-31')]
        public ?string $deathDate,

        #[OA\Property(ref: new Model(type: UploadedImageReadDTO::class), nullable: true)]
        public ?UploadedImageReadDTO $coverImage,
    ) {}
}
