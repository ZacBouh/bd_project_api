<?php

declare(strict_types=1);

namespace App\Enum;

enum OrderPaymentStatus: string
{
    case PENDING = 'PENDING';
    case PAID_PENDING_HANDOVER = 'PAID_PENDING_HANDOVER';
    case IN_PROGRESS_PARTIAL = 'IN_PROGRESS_PARTIAL';
    case COMPLETED = 'COMPLETED';
    case CANCELED = 'CANCELED';
    case REFUNDED = 'REFUNDED';
}
