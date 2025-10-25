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
use App\Enum\PayoutTaskStatus;
use App\Repository\PayoutTaskRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
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

    public function confirmOrderItem(OrderItem $item, User $buyer): void
    {
        $order = $item->getOrder();
        if ($order === null) {
            throw new LogicException('Order item must be attached to an order.');
        }

        $orderBuyer = $order->getUser();
        if ($orderBuyer === null || $orderBuyer->getId() !== $buyer->getId()) {
            throw new LogicException('This order item does not belong to the provided buyer.');
        }

        if ($item->getStatus() === OrderItemStatus::BUYER_CONFIRMED) {
            return;
        }

        $item->markBuyerConfirmed(new DateTimeImmutable());

        $this->updateOrderStatus($order);
        $this->updatePayoutTaskForItem($item, $buyer);

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

    private function updateOrderStatus(Order $order): void
    {
        $items = $order->getItems();
        if ($items->isEmpty()) {
            $order->setStatus(OrderPaymentStatus::PAID_PENDING_HANDOVER);

            return;
        }

        $allConfirmed = true;
        $anyConfirmed = false;
        foreach ($items as $orderItem) {
            if ($orderItem->getStatus() === OrderItemStatus::BUYER_CONFIRMED) {
                $anyConfirmed = true;
                continue;
            }

            $allConfirmed = false;
        }

        if ($allConfirmed) {
            $order->setStatus(OrderPaymentStatus::COMPLETED);

            return;
        }

        if ($anyConfirmed) {
            $order->setStatus(OrderPaymentStatus::IN_PROGRESS_PARTIAL);

            return;
        }

        $order->setStatus(OrderPaymentStatus::PAID_PENDING_HANDOVER);
    }

    private function updatePayoutTaskForItem(OrderItem $item, User $buyer): void
    {
        $order = $item->getOrder();
        $seller = $item->getSeller();

        if ($order === null || $seller === null) {
            return;
        }

        $payoutTask = $this->payoutTaskRepository->findOneByOrderAndSeller($order, $seller);
        if ($payoutTask === null) {
            $payoutTask = (new PayoutTask())
                ->setOrder($order)
                ->setSeller($seller)
                ->setStatus(PayoutTaskStatus::PENDING_PAYMENT_INFORMATION)
                ->setCurrency($item->getCurrency());
            $order->addPayoutTask($payoutTask);
            $this->entityManager->persist($payoutTask);
        }

        $confirmedAmount = 0;
        $totalAmount = 0;
        $hasPreviousConfirmation = false;
        foreach ($order->getItems() as $orderItem) {
            if ($orderItem->getSeller()?->getId() !== $seller->getId()) {
                continue;
            }

            $totalAmount += $orderItem->getPrice();
            if ($orderItem->getStatus() === OrderItemStatus::BUYER_CONFIRMED) {
                if ($orderItem !== $item) {
                    $hasPreviousConfirmation = true;
                }
                $confirmedAmount += $orderItem->getPrice();
            }
        }

        if ($totalAmount > 0) {
            $payoutTask->setAmount($totalAmount);
        }

        if ($payoutTask->getCurrency() !== $item->getCurrency()) {
            $payoutTask->setCurrency($item->getCurrency());
        }

        if ($confirmedAmount > 0 && !$hasPreviousConfirmation) {
            $this->notifySellerForPaymentInformation($order, $item, $buyer, $payoutTask);
        }
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

        $content = <<<HTML
<p>Bonjour {$seller->getPseudo()},</p>
<p>{$buyerName} vient de confirmer la remise de {$copyTitle}. Afin que nous puissions procéder à votre versement de {$amount} {$currencyLabel}, merci de répondre à cet e-mail en nous communiquant vos informations de paiement (IBAN, titulaire, etc.).</p>
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
            $adminContent = <<<TEXT
Le vendeur {$seller->getPseudo()} ({$sellerEmail}) a reçu une demande d'informations de paiement suite à la confirmation de la commande {$order->getOrderRef()} par {$buyerName}.
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
