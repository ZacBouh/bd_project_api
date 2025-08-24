<?php

namespace App\Enum;

enum OnGoingStatus: string
{
    case ONGOING = 'ongoing';
    case CANCELED = 'canceled';
    case COMPLETED = 'completed';
    case PAUSED = 'paused';
}
