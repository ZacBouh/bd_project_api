<?php

declare(strict_types=1);

namespace App\Enum;

enum PayoutTaskPaymentType: string
{
    case ORDER = 'ORDER';
    case REFUND = 'REFUND';
}
