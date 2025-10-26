<?php

declare(strict_types=1);

namespace App\Tests\Service;

use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\PayoutTask;
use App\Entity\User;
use App\Enum\OrderItemStatus;
use App\Enum\OrderPaymentStatus;
use App\Enum\PayoutTaskPaymentType;
use App\Enum\PriceCurrency;
use App\Repository\PayoutTaskRepository;
use App\Service\MailerService;
use App\Service\OrderService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

final class OrderServiceTest extends TestCase
{
    public function testConfirmOrderItemUpdatesStatusAndSendsEmail(): void
    {
        $_ENV['PAYOUT_NOTIFICATION_EMAIL'] = '';

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $storedTask = null;
        $entityManager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(static function ($entity) use (&$storedTask): void {
                $storedTask = $entity;
            });
        $entityManager->expects(self::once())->method('flush');

        $payoutTaskRepository = $this->createMock(PayoutTaskRepository::class);
        $payoutTaskRepository->expects(self::once())
            ->method('findOneByOrderAndSeller')
            ->willReturn(null);

        $mailer = $this->createMock(MailerService::class);
        $mailer->expects(self::once())
            ->method('sendMail');

        $logger = $this->createMock(LoggerInterface::class);

        $service = new OrderService(
            $entityManager,
            $payoutTaskRepository,
            $mailer,
            $logger,
        );

        $buyer = $this->createUser('buyer@example.com', 'Buyer');
        $seller = $this->createUser('seller@example.com', 'Seller');

        $order = (new Order())
            ->setOrderRef('o_TEST')
            ->setUser($buyer)
            ->setStatus(OrderPaymentStatus::PAID_PENDING_HANDOVER)
            ->setCurrency(PriceCurrency::EURO);

        $item = (new OrderItem())
            ->setSeller($seller)
            ->setPrice(1500)
            ->setCurrency(PriceCurrency::EURO);

        $order->addItem($item);

        $service->confirmOrderItem($order, $item, $buyer);

        self::assertSame(OrderPaymentStatus::COMPLETED, $order->getStatus());
        self::assertInstanceOf(PayoutTask::class, $storedTask);
        self::assertSame(PayoutTaskPaymentType::ORDER, $storedTask->getPaymentType());
    }

    public function testConfirmingMultipleItemsDoesNotSendDuplicateEmails(): void
    {
        $_ENV['PAYOUT_NOTIFICATION_EMAIL'] = '';

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $storedTask = null;
        $entityManager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(static function ($task) use (&$storedTask): void {
                $storedTask = $task;
            });
        $entityManager->expects(self::exactly(2))
            ->method('flush');

        $payoutTaskRepository = $this->createMock(PayoutTaskRepository::class);
        $payoutTaskRepository->expects(self::exactly(2))
            ->method('findOneByOrderAndSeller')
            ->willReturnCallback(static function () use (&$storedTask) {
                return $storedTask;
            });

        $mailer = $this->createMock(MailerService::class);
        $mailer->expects(self::once())
            ->method('sendMail');

        $logger = $this->createMock(LoggerInterface::class);

        $service = new OrderService(
            $entityManager,
            $payoutTaskRepository,
            $mailer,
            $logger,
        );

        $buyer = $this->createUser('buyer@example.com', 'Buyer');
        $seller = $this->createUser('seller@example.com', 'Seller');

        $order = (new Order())
            ->setOrderRef('o_TEST2')
            ->setUser($buyer)
            ->setStatus(OrderPaymentStatus::PAID_PENDING_HANDOVER)
            ->setCurrency(PriceCurrency::EURO);

        $firstItem = (new OrderItem())
            ->setSeller($seller)
            ->setPrice(1000)
            ->setCurrency(PriceCurrency::EURO);

        $secondItem = (new OrderItem())
            ->setSeller($seller)
            ->setPrice(2000)
            ->setCurrency(PriceCurrency::EURO);

        $order->addItem($firstItem);
        $order->addItem($secondItem);

        $service->confirmOrderItem($order, $firstItem, $buyer);
        self::assertSame(OrderPaymentStatus::IN_PROGRESS_PARTIAL, $order->getStatus());

        $service->confirmOrderItem($order, $secondItem, $buyer);
        self::assertSame(OrderPaymentStatus::COMPLETED, $order->getStatus());
        self::assertInstanceOf(PayoutTask::class, $storedTask);
        self::assertSame(PayoutTaskPaymentType::ORDER, $storedTask->getPaymentType());
    }

    public function testOrderStaysInProgressUntilAllItemsResolved(): void
    {
        $_ENV['PAYOUT_NOTIFICATION_EMAIL'] = '';

        $refundTask = null;

        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::atLeastOnce())
            ->method('persist')
            ->willReturnCallback(static function ($entity) use (&$refundTask): void {
                if ($entity instanceof PayoutTask && $entity->getPaymentType() === PayoutTaskPaymentType::REFUND) {
                    $refundTask = $entity;
                }
            });
        $entityManager->expects(self::exactly(3))->method('flush');

        $payoutTaskRepository = $this->createMock(PayoutTaskRepository::class);
        $payoutTaskRepository->expects(self::once())
            ->method('findOneByOrderAndSeller')
            ->willReturn(null);
        $payoutTaskRepository->expects(self::exactly(2))
            ->method('findOneByOrderAndPaymentType')
            ->willReturnCallback(static function () use (&$refundTask) {
                return $refundTask;
            });

        $mailer = $this->createMock(MailerService::class);
        $mailer->expects(self::once())
            ->method('sendMail');

        $logger = $this->createMock(LoggerInterface::class);

        $service = new OrderService(
            $entityManager,
            $payoutTaskRepository,
            $mailer,
            $logger,
        );

        $buyer = $this->createUser('buyer@example.com', 'Buyer');
        $seller = $this->createUser('seller@example.com', 'Seller');

        $order = (new Order())
            ->setOrderRef('o_PROGRESS')
            ->setUser($buyer)
            ->setStatus(OrderPaymentStatus::PAID_PENDING_HANDOVER)
            ->setCurrency(PriceCurrency::EURO);

        $firstItem = (new OrderItem())
            ->setSeller($seller)
            ->setPrice(1000)
            ->setCurrency(PriceCurrency::EURO);

        $secondItem = (new OrderItem())
            ->setPrice(1200)
            ->setCurrency(PriceCurrency::EURO);

        $thirdItem = (new OrderItem())
            ->setPrice(800)
            ->setCurrency(PriceCurrency::EURO);

        $order
            ->addItem($firstItem)
            ->addItem($secondItem)
            ->addItem($thirdItem);

        $service->confirmOrderItem($order, $firstItem, $buyer);
        self::assertSame(OrderPaymentStatus::IN_PROGRESS_PARTIAL, $order->getStatus());

        $service->cancelOrderItem($order, $secondItem, $buyer);
        self::assertSame(OrderPaymentStatus::IN_PROGRESS_PARTIAL, $order->getStatus());

        $service->cancelOrderItem($order, $thirdItem, $buyer);
        self::assertSame(OrderPaymentStatus::COMPLETED, $order->getStatus());
    }

    public function testCancelOrderItemCreatesRefundPayoutTask(): void
    {
        /** @var EntityManagerInterface&MockObject $entityManager */
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $storedTask = null;
        $entityManager->expects(self::once())
            ->method('persist')
            ->willReturnCallback(static function ($entity) use (&$storedTask): void {
                $storedTask = $entity;
            });
        $entityManager->expects(self::once())->method('flush');

        $payoutTaskRepository = $this->createMock(PayoutTaskRepository::class);
        $payoutTaskRepository->expects(self::never())
            ->method('findOneByOrderAndSeller');
        $payoutTaskRepository->expects(self::once())
            ->method('findOneByOrderAndPaymentType')
            ->willReturn(null);

        $mailer = $this->createMock(MailerService::class);
        $mailer->expects(self::never())->method('sendMail');

        $logger = $this->createMock(LoggerInterface::class);

        $service = new OrderService(
            $entityManager,
            $payoutTaskRepository,
            $mailer,
            $logger,
        );

        $buyer = $this->createUser('buyer@example.com', 'Buyer');

        $order = (new Order())
            ->setOrderRef('o_CANCEL')
            ->setUser($buyer)
            ->setStatus(OrderPaymentStatus::PAID_PENDING_HANDOVER)
            ->setCurrency(PriceCurrency::EURO);

        $item = (new OrderItem())
            ->setPrice(2500)
            ->setCurrency(PriceCurrency::EURO);

        $order->addItem($item);

        $service->cancelOrderItem($order, $item, $buyer);

        self::assertSame(OrderItemStatus::CANCELED, $item->getStatus());
        self::assertSame(OrderPaymentStatus::CANCELED, $order->getStatus());
        self::assertInstanceOf(PayoutTask::class, $storedTask);
        self::assertSame(PayoutTaskPaymentType::REFUND, $storedTask->getPaymentType());
        self::assertSame(2500, $storedTask->getAmount());
    }

    private function createUser(string $email, string $pseudo): User
    {
        $user = new User();
        $user->setEmail($email);
        $user->setPseudo($pseudo);

        return $user;
    }

}
