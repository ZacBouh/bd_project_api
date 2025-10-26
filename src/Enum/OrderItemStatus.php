<?php

declare(strict_types=1);

namespace App\Enum;

enum OrderItemStatus: string
{
    case PENDING_HANDOVER = 'PENDING_HANDOVER';
    case BUYER_CONFIRMED = 'BUYER_CONFIRMED';
    case CANCELED = 'CANCELED';
}
