<?php

namespace App\DTO\Order;

use App\Enum\OrderPaymentStatus;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Payload de mise à jour d\'une commande.')]
class OrderWriteDTO
{
    public function __construct(
        #[OA\Property(nullable: true, example: 12)]
        public ?int $id,

        #[OA\Property(nullable: true, example: 'IN_PROGRESS_PARTIAL')]
        public ?OrderPaymentStatus $status,
    ) {
    }
}
