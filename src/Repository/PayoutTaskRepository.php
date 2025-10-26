<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use App\Entity\PayoutTask;
use App\Entity\User;
use App\Enum\PayoutTaskPaymentType;
use App\Enum\PayoutTaskStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PayoutTask>
 */
class PayoutTaskRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PayoutTask::class);
    }

    public function findOneByOrderAndSeller(
        Order $order,
        User $seller,
        PayoutTaskPaymentType $paymentType = PayoutTaskPaymentType::ORDER
    ): ?PayoutTask
    {
        return $this->findOneBy([
            'order' => $order,
            'seller' => $seller,
            'paymentType' => $paymentType,
        ]);
    }

    public function findOneByOrderAndPaymentType(Order $order, PayoutTaskPaymentType $paymentType): ?PayoutTask
    {
        return $this->findOneBy([
            'order' => $order,
            'paymentType' => $paymentType,
        ]);
    }

    /**
     * @param PayoutTaskStatus[]|null $statuses
     *
     * @return PayoutTask[]
     */
    public function findByFilters(?User $seller = null, ?array $statuses = null): array
    {
        $qb = $this->createQueryBuilder('task')
            ->leftJoin('task.order', 'o')->addSelect('o')
            ->leftJoin('task.seller', 'seller')->addSelect('seller')
            ->orderBy('task.createdAt', 'DESC');

        if ($seller instanceof User) {
            $qb->andWhere('task.seller = :seller')
                ->setParameter('seller', $seller);
        }

        if (is_array($statuses) && $statuses !== []) {
            $qb->andWhere('task.status IN (:statuses)')
                ->setParameter('statuses', array_map(static fn (PayoutTaskStatus $status): string => $status->value, $statuses));
        }

        return $qb->getQuery()->getResult();
    }

    public function findOneWithAssociations(int $taskId): ?PayoutTask
    {
        return $this->createQueryBuilder('task')
            ->leftJoin('task.order', 'o')->addSelect('o')
            ->leftJoin('task.seller', 'seller')->addSelect('seller')
            ->where('task.id = :id')
            ->setParameter('id', $taskId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
