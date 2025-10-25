<?php

namespace App\DTO\Order;

use App\DTO\PayoutTask\PayoutTaskReadDTO;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Représentation en lecture d\'une commande.')]
class OrderReadDTO
{
    /**
     * @param array<OrderItemReadDTO> $items
     * @param array<PayoutTaskReadDTO> $payoutTasks
     */
    public function __construct(
        #[OA\Property(example: 'o_7G9KX')]
        public string $orderRef,

        #[OA\Property(example: 'PAID_PENDING_HANDOVER')]
        public string $status,

        #[OA\Property(nullable: true, example: 12990, description: 'Montant total de la commande en centimes.')]
        public ?int $amountTotal,

        #[OA\Property(nullable: true, example: 'euro')]
        public ?string $currency,

        #[OA\Property(
            type: 'array',
            items: new OA\Items(ref: new Model(type: OrderItemReadDTO::class))
        )]
        public array $items,

        #[OA\Property(
            type: 'array',
            items: new OA\Items(ref: new Model(type: PayoutTaskReadDTO::class))
        )]
        public array $payoutTasks,

        #[OA\Property(nullable: true, format: 'date-time')]
        public ?string $createdAt,

        #[OA\Property(nullable: true, format: 'date-time')]
        public ?string $updatedAt,
    ) {
    }
}
