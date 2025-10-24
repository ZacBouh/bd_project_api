<?php

declare(strict_types=1);

namespace App\Tests\Entity;

use App\Entity\Order;
use App\Enum\OrderPaymentStatus;
use PHPUnit\Framework\TestCase;

final class OrderTest extends TestCase
{
    public function testDefaultsToPendingStatus(): void
    {
        $order = new Order();

        self::assertSame(OrderPaymentStatus::PENDING, $order->getStatus());
    }

    public function testStatusCanBeMutated(): void
    {
        $order = new Order();

        $order->setStatus(OrderPaymentStatus::REFUNDED);

        self::assertSame(OrderPaymentStatus::REFUNDED, $order->getStatus());
    }
}
