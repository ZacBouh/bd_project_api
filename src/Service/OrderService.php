<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\PayoutTask\PayoutTaskWriteDTO;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\PayoutTask;
use App\Entity\User;
use App\Enum\OrderItemStatus;
use App\Enum\OrderPaymentStatus;
use App\Enum\PayoutTaskPaymentType;
use App\Enum\PayoutTaskStatus;
use App\Enum\PriceCurrency;
use App\Repository\PayoutTaskRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\PersistentCollection;
use LogicException;
use Psr\Log\LoggerInterface;

class OrderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PayoutTaskRepository $payoutTaskRepository,
        private MailerService $mailerService,
        private LoggerInterface $logger,
    ) {
    }

    public function confirmOrderItem(Order $order, OrderItem $item, User $buyer): void
    {
        $orderBuyer = $order->getUser();
        if ($orderBuyer === null || $orderBuyer->getId() !== $buyer->getId()) {
            throw new LogicException('This order item does not belong to the provided buyer.');
        }

        if ($item->getOrder()?->getId() !== $order->getId()) {
            throw new LogicException('Order item does not belong to the given order.');
        }

        if ($item->getStatus() === OrderItemStatus::BUYER_CONFIRMED) {
            return;
        }

        $item->markBuyerConfirmed(new DateTimeImmutable());

        $this->updateOrderStatus($order);
        $this->updatePayoutTaskForItem($item, $buyer);

        $this->entityManager->flush();
    }

    public function cancelOrderItem(Order $order, OrderItem $item, User $buyer): void
    {
        $orderBuyer = $order->getUser();
        if ($orderBuyer === null || $orderBuyer->getId() !== $buyer->getId()) {
            throw new LogicException('This order item does not belong to the provided buyer.');
        }

        if ($item->getOrder()?->getId() !== $order->getId()) {
            throw new LogicException('Order item does not belong to the given order.');
        }

        if (!$this->cancelOrderItemInternal($item)) {
            return;
        }

        $this->updateOrderStatus($order);
        $this->updateRefundPayoutTask($order);

        $this->entityManager->flush();
    }

    public function cancelOrder(Order $order, User $buyer): void
    {
        $orderBuyer = $order->getUser();
        if ($orderBuyer === null || $orderBuyer->getId() !== $buyer->getId()) {
            throw new LogicException('This order does not belong to the provided buyer.');
        }

        $items = $order->getItems();
        if ($items instanceof PersistentCollection && !$items->isInitialized()) {
            $items->initialize();
        }

        $hasConfirmedItem = false;
        $hasUpdatedItem = false;
        foreach ($items as $orderItem) {
            if ($orderItem->getStatus() === OrderItemStatus::BUYER_CONFIRMED) {
                $hasConfirmedItem = true;

                break;
            }
        }

        if ($hasConfirmedItem) {
            throw new LogicException('Cannot cancel an order that already has confirmed items.');
        }

        foreach ($items as $orderItem) {
            if ($this->cancelOrderItemInternal($orderItem)) {
                $hasUpdatedItem = true;
            }
        }

        if (!$hasUpdatedItem) {
            return;
        }

        $this->updateOrderStatus($order);
        $this->updateRefundPayoutTask($order);

        $this->entityManager->flush();
    }

    public function findOrderItem(Order $order, int $itemId): ?OrderItem
    {
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getId() === $itemId) {
                return $orderItem;
            }
        }

        return null;
    }

    /**
     * @param PayoutTaskStatus[]|null $statuses
     *
     * @return PayoutTask[]
     */
    public function getPayoutTasks(?User $seller = null, ?array $statuses = null): array
    {
        return $this->payoutTaskRepository->findByFilters($seller, $statuses);
    }

    public function findPayoutTask(int $taskId): ?PayoutTask
    {
        return $this->payoutTaskRepository->findOneWithAssociations($taskId);
    }

    public function updatePayoutTask(PayoutTask $task, PayoutTaskWriteDTO $dto): PayoutTask
    {
        if ($dto->paymentInformation !== null) {
            $task->setPaymentInformation($dto->paymentInformation);
            if ($task->getStatus() === PayoutTaskStatus::PENDING_PAYMENT_INFORMATION) {
                $task->setStatus(PayoutTaskStatus::PENDING_TO_PAY);
            }
        }

        if ($dto->status !== null) {
            $task->setStatus($dto->status);
        }

        if ($task->getStatus() === PayoutTaskStatus::PAID) {
            if ($task->getPaidAt() === null) {
                $task->setPaidAt(new DateTimeImmutable());
            }
        } elseif ($dto->status !== null) {
            $task->setPaidAt(null);
        }

        $this->entityManager->persist($task);
        $this->entityManager->flush();

        return $task;
    }

    private function cancelOrderItemInternal(OrderItem $item): bool
    {
        if ($item->getStatus() === OrderItemStatus::BUYER_CONFIRMED) {
            throw new LogicException('Cannot cancel an order item that has already been confirmed.');
        }

        if ($item->getStatus() === OrderItemStatus::CANCELED) {
            return false;
        }

        $item->markBuyerCanceled();

        return true;
    }

    private function updateOrderStatus(Order $order): void
    {
        $items = $order->getItems();
        if ($items instanceof PersistentCollection && !$items->isInitialized()) {
            $items->initialize();
        }

        if ($items->isEmpty()) {
            $order->setStatus(OrderPaymentStatus::PAID_PENDING_HANDOVER);

            return;
        }

        $pendingCount = 0;
        $confirmedCount = 0;
        $canceledCount = 0;

        foreach ($items as $orderItem) {
            $status = $orderItem->getStatus();

            if ($status === OrderItemStatus::BUYER_CONFIRMED) {
                ++$confirmedCount;

                continue;
            }

            if ($status === OrderItemStatus::CANCELED) {
                ++$canceledCount;

                continue;
            }

            ++$pendingCount;
        }

        if ($pendingCount > 0) {
            $order->setStatus(($confirmedCount > 0 || $canceledCount > 0) ? OrderPaymentStatus::IN_PROGRESS_PARTIAL : OrderPaymentStatus::PAID_PENDING_HANDOVER);

            return;
        }

        if ($confirmedCount === 0) {
            $order->setStatus($canceledCount > 0 ? OrderPaymentStatus::CANCELED : OrderPaymentStatus::PAID_PENDING_HANDOVER);

            return;
        }

        $order->setStatus(OrderPaymentStatus::COMPLETED);
    }

    private function updatePayoutTaskForItem(OrderItem $item, User $buyer): void
    {
        $order = $item->getOrder();
        $seller = $item->getSeller();

        if ($order === null || $seller === null) {
            return;
        }

        $payoutTask = $this->payoutTaskRepository->findOneByOrderItem($item);

        $isNewTask = false;
        if ($payoutTask === null) {
            $payoutTask = (new PayoutTask())
                ->setOrder($order)
                ->setOrderItem($item)
                ->setSeller($seller)
                ->setStatus(PayoutTaskStatus::PENDING_PAYMENT_INFORMATION)
                ->setCurrency($item->getCurrency())
                ->setPaymentType(PayoutTaskPaymentType::ORDER);
            $order->addPayoutTask($payoutTask);
            $this->entityManager->persist($payoutTask);
            $isNewTask = true;
        } else {
            if ($payoutTask->getSeller()?->getId() !== $seller->getId()) {
                $payoutTask->setSeller($seller);
            }
            if ($payoutTask->getPaymentType() !== PayoutTaskPaymentType::ORDER) {
                $payoutTask->setPaymentType(PayoutTaskPaymentType::ORDER);
            }
        }

        $payoutTask->setAmount($item->getPrice());

        if ($payoutTask->getCurrency() !== $item->getCurrency()) {
            $payoutTask->setCurrency($item->getCurrency());
        }

        if ($isNewTask) {
            $this->notifySellerForPaymentInformation($order, $item, $buyer, $payoutTask);
        }
    }

    private function updateRefundPayoutTask(Order $order): void
    {
        $canceledAmount = 0;
        $currency = $order->getCurrency();
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getStatus() !== OrderItemStatus::CANCELED) {
                continue;
            }

            $canceledAmount += $orderItem->getPrice();
            $currency = $orderItem->getCurrency();
        }

        $existingTask = $this->payoutTaskRepository->findOneByOrderAndPaymentType($order, PayoutTaskPaymentType::REFUND);

        if ($canceledAmount === 0) {
            if ($existingTask !== null) {
                $this->entityManager->remove($existingTask);
            }

            return;
        }

        $currency ??= PriceCurrency::EURO;

        if ($existingTask === null) {
            $existingTask = (new PayoutTask())
                ->setOrder($order)
                ->setStatus(PayoutTaskStatus::PENDING_TO_PAY)
                ->setCurrency($currency)
                ->setPaymentType(PayoutTaskPaymentType::REFUND);
            $order->addPayoutTask($existingTask);
            $this->entityManager->persist($existingTask);
        } elseif ($existingTask->getCurrency() !== $currency) {
            $existingTask->setCurrency($currency);
        }

        $existingTask->setAmount($canceledAmount);
    }

    private function notifySellerForPaymentInformation(Order $order, OrderItem $item, User $buyer, PayoutTask $task): void
    {
        $seller = $item->getSeller();
        if ($seller === null) {
            return;
        }

        $sellerEmail = $seller->getEmail();
        if ($sellerEmail === null) {
            $this->logger->warning('Seller has no email address, unable to request payout information', [
                'sellerId' => $seller->getId(),
                'orderRef' => $order->getOrderRef(),
            ]);

            return;
        }

        $subject = sprintf('Confirmation de remise - Commande %s', $order->getOrderRef());
        $copyTitle = $item->getCopy()?->getTitle()?->getName() ?? 'un exemplaire';
        $buyerName = $buyer->getPseudo() ?? $buyer->getEmail() ?? 'un acheteur';
        $currencyLabel = $task->getCurrency()->label();
        $amount = number_format($task->getAmount() / 100, 2, ',', ' ');
        $itemAmount = number_format($item->getPrice() / 100, 2, ',', ' ');

        $content = <<<HTML
<p>Bonjour {$seller->getPseudo()},</p>
<p>{$buyerName} vient de confirmer la remise de {$copyTitle} pour un montant de {$itemAmount} {$currencyLabel}. Afin que nous puissions procéder à votre versement de {$amount} {$currencyLabel}, merci de répondre à cet e-mail en nous communiquant vos informations de paiement (IBAN, titulaire, etc.).</p>
<p>Référence de commande : <strong>{$order->getOrderRef()}</strong></p>
<p>Merci !</p>
HTML;

        try {
            $this->mailerService->sendMail($sellerEmail, $subject, $content);
        } catch (\Throwable $exception) {
            $this->logger->error('Unable to send payout information request email', [
                'orderRef' => $order->getOrderRef(),
                'sellerId' => $seller->getId(),
                'error' => $exception->getMessage(),
            ]);
        }

        $adminEmail = $_ENV['PAYOUT_NOTIFICATION_EMAIL'] ?? null;
        if (is_string($adminEmail) && $adminEmail !== '') {
            $adminCurrency = $task->getCurrency()->label();
            $adminItemAmount = number_format($item->getPrice() / 100, 2, ',', ' ');
            $adminContent = <<<TEXT
Le vendeur {$seller->getPseudo()} ({$sellerEmail}) a reçu une demande d'informations de paiement suite à la confirmation de la commande {$order->getOrderRef()} par {$buyerName} pour {$copyTitle} ({$adminItemAmount} {$adminCurrency}).
Montant total estimé : {$amount} {$adminCurrency}.
TEXT;

            try {
                $this->mailerService->sendMail($adminEmail, $subject, nl2br($adminContent));
            } catch (\Throwable $exception) {
                $this->logger->error('Unable to notify payout manager by email', [
                    'orderRef' => $order->getOrderRef(),
                    'adminEmail' => $adminEmail,
                    'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
