<?php

namespace App\DTO\Series;

use App\DTO\UploadedImage\UploadedImageReadDTO;
use App\Entity\Trait\HasDefaultNormalizeCallback;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Attributes as OA;

/**
 * @phpstan-import-type NormalizeCallbackDefaultReturn from HasDefaultNormalizeCallback
 */
#[OA\Schema(description: 'Informations d\'une série.')] 
class SeriesReadDTO
{
    /**
     * @param int[]|null $titles
     * @param NormalizeCallbackDefaultReturn $publisher
     */
    public function __construct(
        #[OA\Property(example: 7)]
        public int $id,

        #[OA\Property(example: 'Fullmetal Alchemist')]
        public string $name,

        #[OA\Property(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string', nullable: true),
            ]
        )]
        public array $publisher,

        #[OA\Property(example: 'ja')]
        public string $language,

        #[OA\Property(format: 'date-time')]
        public string $createdAt,

        #[OA\Property(format: 'date-time')]
        public string $updatedAt,

        #[OA\Property(
            nullable: true,
            type: 'array',
            items: new OA\Items(type: 'integer')
        )]
        public ?array $titles,

        #[OA\Property(nullable: true, example: 'ONGOING')]
        public ?string $onGoingStatus,

        #[OA\Property(ref: new Model(type: UploadedImageReadDTO::class), nullable: true)]
        public ?UploadedImageReadDTO $coverImage,

    ) {}
}
