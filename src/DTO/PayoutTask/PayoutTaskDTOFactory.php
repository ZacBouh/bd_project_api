<?php

namespace App\DTO\PayoutTask;

use App\DTO\Builder\AbstractDTOFactory;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\PayoutTask;
use App\Entity\User;
use App\Enum\PayoutTaskStatus;
use DateTimeInterface;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * @extends AbstractDTOFactory<PayoutTask, PayoutTaskReadDTO, PayoutTaskWriteDTO>
 */
class PayoutTaskDTOFactory extends AbstractDTOFactory
{
    public function readDtoFromEntity(object $entity): object
    {
        if (!($entity instanceof PayoutTask)) {
            throw new InvalidArgumentException(sprintf('Expected instance of %s, got %s', PayoutTask::class, $entity::class));
        }

        $seller = $entity->getSeller();
        $sellerSummary = null;
        if ($seller instanceof User) {
            $sellerSummary = [
                'id' => $seller->getId(),
                'pseudo' => $seller->getPseudo(),
            ];
        }

        $orderRef = null;
        $order = $entity->getOrder();
        if ($order instanceof Order) {
            $orderRef = $order->getOrderRef();
        }

        $orderItemId = null;
        $orderItemName = null;
        $orderItem = $entity->getOrderItem();
        if ($orderItem instanceof OrderItem) {
            $orderItemId = $orderItem->getId();
            $orderItemName = $orderItem->getCopy()?->getTitle()?->getName();
        }

        return new PayoutTaskReadDTO(
            $entity->getId(),
            $orderRef,
            $orderItemId,
            $orderItemName,
            $sellerSummary,
            $entity->getAmount(),
            $entity->getCurrency()->value,
            $entity->getStatus()->value,
            $entity->getPaymentType()->value,
            $entity->getPaymentInformation(),
            $entity->getCreatedAt()->format(DateTimeInterface::ATOM),
            $entity->getUpdatedAt()->format(DateTimeInterface::ATOM),
            $entity->getPaidAt()?->format(DateTimeInterface::ATOM),
        );
    }

    public function writeDtoFromInputBag(InputBag $inputBag, ?FileBag $files = null): object
    {
        $statusValue = $inputBag->get('status');
        $status = null;
        if (is_string($statusValue) && $statusValue !== '') {
            $status = PayoutTaskStatus::from($statusValue);
        } elseif ($statusValue !== null) {
            throw new InvalidArgumentException('status must be a string or null');
        }

        $paymentInformation = $inputBag->get('paymentInformation');
        if ($paymentInformation === null) {
            $paymentInformation = $inputBag->all('paymentInformation');
            if ($paymentInformation === []) {
                $paymentInformation = null;
            }
        }

        if ($paymentInformation !== null && !is_array($paymentInformation)) {
            throw new InvalidArgumentException('paymentInformation must be an array or null');
        }

        return new PayoutTaskWriteDTO($status, $paymentInformation);
    }
}
