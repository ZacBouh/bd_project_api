<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\CheckoutSessionEmail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CheckoutSessionEmail>
 */
class CheckoutSessionEmailRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CheckoutSessionEmail::class);
    }

    public function findOneBySessionId(string $sessionId): ?CheckoutSessionEmail
    {
        return $this->findOneBy(['sessionId' => $sessionId]);
    }

    public function existsForSession(string $sessionId): bool
    {
        return (bool) $this->createQueryBuilder('cse')
            ->select('1')
            ->andWhere('cse.sessionId = :sessionId')
            ->setParameter('sessionId', $sessionId)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
