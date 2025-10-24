<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\StripeEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<StripeEvent>
 */
class StripeEventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StripeEvent::class);
    }

    public function existsByEventId(string $eventId): bool
    {
        return (bool) $this->createQueryBuilder('se')
            ->select('1')
            ->andWhere('se.eventId = :eventId')
            ->setParameter('eventId', $eventId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
