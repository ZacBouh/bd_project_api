<?php

declare(strict_types=1);

namespace App\Enum;

enum PayoutTaskStatus: string
{
    case PENDING_PAYMENT_INFORMATION = 'PENDING_PAYMENT_INFORMATION';
    case PENDING_TO_PAY = 'PENDING_TO_PAY';
    case PAID = 'PAID';
    case ARCHIVED = 'ARCHIVED';
}
