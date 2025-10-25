<?php

namespace App\DTO\Title;

use App\DTO\UploadedImage\UploadedImageReadDTO;
use App\Entity\Trait\HasDefaultNormalizeCallback;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

/**
 * @phpstan-import-type NormalizeCallbackDefaultReturn from HasDefaultNormalizeCallback
 */
#[OA\Schema(
    description: 'Représentation en lecture d’un titre.'
)]
class TitleReadDTO
{
    /**
     * @param NormalizeCallbackDefaultReturn $publisher
     * @param array<UploadedImageReadDTO>|null $uploadedImages
     * @param array<mixed> $artistsContributions
     */
    public function __construct(
        #[OA\Property(example: 42)]
        public int $id,

        #[OA\Property(example: 'The Sandman')]
        public string $name,

        #[OA\Property(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string', nullable: true),
            ]
        )]
        public array $publisher,

        #[OA\Property(description: 'Code langue ISO 639-1', example: 'fr')]
        public string $language,

        #[OA\Property(nullable: true, example: 'Un récit épique.')]
        public ?string $description,

        #[OA\Property(
            type: 'array',
            nullable: true,
            items: new OA\Items(
                type: 'object',
                properties: [
                    new OA\Property(
                        property: 'artist',
                        type: 'object',
                        properties: [
                            new OA\Property(property: 'id', type: 'integer'),
                            new OA\Property(property: 'fullName', type: 'string'),
                        ]
                    ),
                    new OA\Property(
                        property: 'skills',
                        type: 'array',
                        items: new OA\Items(type: 'string')
                    )
                ]
            )
        )]
        public ?array $artistsContributions,

        #[OA\Property(ref: new Model(type: UploadedImageReadDTO::class), nullable: true)]
        public ?UploadedImageReadDTO $coverImage,

        #[OA\Property(nullable: true, format: 'date', example: '2023-05-01')]
        public ?string $releaseDate,

        #[OA\Property(
            type: 'array',
            nullable: true,
            items: new OA\Items(ref: new Model(type: UploadedImageReadDTO::class))
        )]
        public ?array $uploadedImages,

        #[OA\Property(nullable: true, example: '9782070413119')]
        public ?string $isbn
    ) {}
}
