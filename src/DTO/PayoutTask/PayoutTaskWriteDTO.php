<?php

namespace App\DTO\PayoutTask;

use App\Enum\PayoutTaskStatus;
use OpenApi\Attributes as OA;

#[OA\Schema(description: 'Payload de mise à jour d\'une tâche de paiement.')]
class PayoutTaskWriteDTO
{
    /**
     * @param array<mixed>|null $paymentInformation
     */
    public function __construct(
        #[OA\Property(nullable: true, example: 'PENDING_TO_PAY')]
        public ?PayoutTaskStatus $status,

        #[OA\Property(nullable: true, type: 'object')]
        public ?array $paymentInformation,
    ) {
    }
}
