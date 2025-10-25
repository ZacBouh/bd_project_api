<?php

namespace App\DTO\ArtistTitleContribution;

use App\Entity\Trait\HasDefaultNormalizeCallback;
use OpenApi\Attributes as OA;

/**
 * @phpstan-import-type NormalizeCallbackDefaultReturn from HasDefaultNormalizeCallback
 */
#[OA\Schema(description: 'Association entre un artiste et un titre.')] 
class ArtistTitleContributionReadDTO
{
    /**
     * @param NormalizeCallbackDefaultReturn $artist
     * @param NormalizeCallbackDefaultReturn $title
     */
    public function __construct(
        #[OA\Property(example: 88)]
        public int $id,

        #[OA\Property(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string', nullable: true),
            ]
        )]
        public array $artist,

        #[OA\Property(
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer'),
                new OA\Property(property: 'name', type: 'string', nullable: true),
            ]
        )]
        public array $title,

        #[OA\Property(example: 'WRITER')]
        public string $skill,
    ) {}
}
