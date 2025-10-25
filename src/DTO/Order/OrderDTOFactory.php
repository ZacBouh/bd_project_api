<?php

namespace App\DTO\Order;

use App\DTO\Builder\AbstractDTOFactory;
use App\DTO\Order\OrderReadDTO;
use App\DTO\Order\OrderWriteDTO;
use App\DTO\PayoutTask\PayoutTaskDTOFactory;
use App\DTO\PayoutTask\PayoutTaskReadDTO;
use App\Entity\Copy;
use App\Entity\Order;
use App\Entity\OrderItem;
use App\Entity\User;
use App\Enum\OrderPaymentStatus;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\FileBag;
use Symfony\Component\HttpFoundation\InputBag;

/**
 * @extends AbstractDTOFactory<Order, OrderReadDTO, OrderWriteDTO>
 */
class OrderDTOFactory extends AbstractDTOFactory
{
    public function __construct(
        private PayoutTaskDTOFactory $payoutTaskDTOFactory,
    ) {
        parent::__construct();
    }

    public function readDtoFromEntity(object $entity): object
    {
        if (!($entity instanceof Order)) {
            throw new InvalidArgumentException(sprintf('Expected instance of %s, got %s', Order::class, $entity::class));
        }

        $items = [];
        foreach ($entity->getItems() as $item) {
            $items[] = $this->mapOrderItem($item);
        }

        $payoutTasks = [];
        foreach ($entity->getPayoutTasks() as $task) {
            /** @var PayoutTaskReadDTO $taskDto */
            $taskDto = $this->payoutTaskDTOFactory->readDtoFromEntity($task);
            $payoutTasks[] = $taskDto;
        }

        return new OrderReadDTO(
            $entity->getOrderRef(),
            $entity->getStatus()->value,
            $entity->getAmountTotal(),
            $entity->getCurrency()?->value,
            $items,
            $payoutTasks,
            $entity->getCreatedAt()?->format(DATE_ATOM),
            $entity->getUpdatedAt()?->format(DATE_ATOM),
        );
    }

    public function writeDtoFromInputBag(InputBag $inputBag, ?FileBag $files = null): object
    {
        $id = $this->getIdInput($inputBag);
        $statusValue = $inputBag->get('status');
        $status = null;

        if (is_string($statusValue) && $statusValue !== '') {
            $status = OrderPaymentStatus::from($statusValue);
        } elseif ($statusValue !== null) {
            throw new InvalidArgumentException('status must be a string or null');
        }

        return new OrderWriteDTO($id, $status);
    }

    private function mapOrderItem(OrderItem $item): OrderItemReadDTO
    {
        $copySummary = null;
        $copy = $item->getCopy();
        if ($copy instanceof Copy) {
            $copySummary = [
                'id' => $copy->getId(),
            ];

            $title = $copy->getTitle();
            if ($title !== null) {
                $copySummary['name'] = $title->getName();
            }
        }

        $sellerSummary = null;
        $seller = $item->getSeller();
        if ($seller instanceof User) {
            $sellerSummary = [
                'id' => $seller->getId(),
                'pseudo' => $seller->getPseudo(),
            ];
        }

        return new OrderItemReadDTO(
            $item->getId(),
            $copySummary,
            $sellerSummary,
            $item->getPrice(),
            $item->getCurrency()->value,
            $item->getStatus()->value,
            $item->getBuyerConfirmedAt()?->format(DATE_ATOM),
        );
    }
}
