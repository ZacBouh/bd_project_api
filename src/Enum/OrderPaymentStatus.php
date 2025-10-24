<?php

declare(strict_types=1);

namespace App\Enum;

enum OrderPaymentStatus: string
{
    case PENDING = 'PENDING';
    case PAID = 'PAID';
    case REFUNDED = 'REFUNDED';
}
