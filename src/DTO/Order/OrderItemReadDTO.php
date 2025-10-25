<?php

namespace App\DTO\Order;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Détail d\'un article de commande.')]
class OrderItemReadDTO
{
    /**
     * @param array{id: int|null, name?: string|null}|null $copy
     * @param array{id: int|null, pseudo?: string|null}|null $seller
     */
    public function __construct(
        #[OA\Property(example: 42)]
        public ?int $id,

        #[OA\Property(
            nullable: true,
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer', nullable: true),
                new OA\Property(property: 'name', type: 'string', nullable: true),
            ]
        )]
        public ?array $copy,

        #[OA\Property(
            nullable: true,
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer', nullable: true),
                new OA\Property(property: 'pseudo', type: 'string', nullable: true),
            ]
        )]
        public ?array $seller,

        #[OA\Property(example: 1299, description: 'Prix de l\'article en centimes.')]
        public int $price,

        #[OA\Property(example: 'euro')]
        public string $currency,

        #[OA\Property(example: 'PENDING_HANDOVER')]
        public string $status,

        #[OA\Property(nullable: true, format: 'date-time')]
        public ?string $buyerConfirmedAt,
    ) {
    }
}
