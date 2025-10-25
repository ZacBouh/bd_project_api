<?php

namespace App\DTO\PayoutTask;

use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Représentation en lecture d\'une tâche de paiement.')]
class PayoutTaskReadDTO
{
    /**
     * @param array{id: int|null, pseudo?: string|null}|null $seller
     */
    public function __construct(
        #[OA\Property(example: 10)]
        public ?int $id,

        #[OA\Property(example: 'o_7G9KX', nullable: true)]
        public ?string $orderRef,

        #[OA\Property(
            nullable: true,
            type: 'object',
            properties: [
                new OA\Property(property: 'id', type: 'integer', nullable: true),
                new OA\Property(property: 'pseudo', type: 'string', nullable: true),
            ]
        )]
        public ?array $seller,

        #[OA\Property(example: 2599, description: 'Montant à verser en centimes.')]
        public int $amount,

        #[OA\Property(example: 'euro')]
        public string $currency,

        #[OA\Property(example: 'PENDING_PAYMENT_INFORMATION')]
        public string $status,

        #[OA\Property(nullable: true, type: 'object')]
        public ?array $paymentInformation,

        #[OA\Property(format: 'date-time')]
        public string $createdAt,

        #[OA\Property(nullable: true, format: 'date-time')]
        public ?string $paidAt,
    ) {
    }
}
