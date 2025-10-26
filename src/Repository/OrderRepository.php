<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Order>
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    public function findOneByStripeCheckoutSessionId(string $checkoutSessionId): ?Order
    {
        return $this->findOneBy(['stripeCheckoutSessionId' => $checkoutSessionId]);
    }

    public function findOneForBuyer(string $orderRef, User $buyer): ?Order
    {
        return $this->findOneForViewer($orderRef, $buyer);
    }

    public function findOneForViewer(string $orderRef, ?User $buyer = null): ?Order
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.items', 'items')->addSelect('items')
            ->leftJoin('items.copy', 'copy')->addSelect('copy')
            ->leftJoin('items.seller', 'seller')->addSelect('seller')
            ->leftJoin('o.payoutTasks', 'payoutTasks')->addSelect('payoutTasks')
            ->where('o.orderRef = :orderRef')
            ->setParameter('orderRef', $orderRef);

        // Do not add a LIMIT here: Doctrine will silently drop joined rows when using
        // fetch joins on to-many associations with a max result, which would cause
        // partially loaded orders missing some items.

        if ($buyer instanceof User) {
            $qb->andWhere('o.user = :buyer')
                ->setParameter('buyer', $buyer);
        }

        return $qb->getQuery()->getOneOrNullResult();
    }

    /**
     * @return Order[]
     */
    public function findForListing(?User $buyer = null): array
    {
        $qb = $this->createQueryBuilder('o')
            ->leftJoin('o.items', 'items')->addSelect('items')
            ->leftJoin('items.copy', 'copy')->addSelect('copy')
            ->leftJoin('items.seller', 'seller')->addSelect('seller')
            ->leftJoin('o.payoutTasks', 'payoutTasks')->addSelect('payoutTasks')
            ->orderBy('o.createdAt', 'DESC');

        if ($buyer instanceof User) {
            $qb->andWhere('o.user = :buyer')
                ->setParameter('buyer', $buyer);
        }

        return $qb->getQuery()->getResult();
    }
}
