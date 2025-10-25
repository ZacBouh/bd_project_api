<?php

namespace App\DTO\Copy;

use App\DTO\UploadedImage\UploadedImageReadDTO;
use App\Entity\Trait\HasDefaultNormalizeCallback;
use Nelmio\ApiDocBundle\Model\Model;
use OpenApi\Attributes as OA;

/**
 * @phpstan-import-type NormalizeCallbackDefaultReturn from HasDefaultNormalizeCallback
 */
#[OA\Schema(description: 'Représentation en lecture d\'un exemplaire.')] 
class CopyReadDTO
{
    /**
     * @param NormalizeCallbackDefaultReturn $owner
     * @param NormalizeCallbackDefaultReturn $title
     * @param array<UploadedImageReadDTO> $uploadedImages
     */
    public function __construct(
        #[OA\Property(example: 256)]
        public int $id,

        #[OA\Property(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string', nullable: true),
            ]
        )]
        public array $owner,

        #[OA\Property(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string', nullable: true),
            ]
        )]
        public array $title,

        #[OA\Property(example: 'GOOD')]
        public string $copyCondition,

        #[OA\Property(format: 'date-time')]
        public string $createdAt,

        #[OA\Property(format: 'date-time')]
        public string $updatedAt,

        #[OA\Property(nullable: true, example: 12.5)]
        public ?float $price,

        #[OA\Property(nullable: true, example: 'EUR')]
        public ?string $currency,

        #[OA\Property(nullable: true, example: 9.99)]
        public ?float $boughtForPrice,

        #[OA\Property(nullable: true, example: 'USD')]
        public ?string $boughtForCurrency,

        #[OA\Property(ref: new Model(type: UploadedImageReadDTO::class), nullable: true)]
        public ?UploadedImageReadDTO $coverImage,

        #[OA\Property(
            nullable: true,
            type: 'array',
            items: new OA\Items(ref: new Model(type: UploadedImageReadDTO::class))
        )]
        public ?array $uploadedImages,

        #[OA\Property(nullable: true, example: true)]
        public ?bool $forSale
    ) {}
}
