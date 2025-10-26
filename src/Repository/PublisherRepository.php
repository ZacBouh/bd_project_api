<?php

namespace App\Repository;

use App\Entity\Publisher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Publisher>
 */
class PublisherRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publisher::class);
    }

    /**
     * @return array<Publisher>
     */
    public function findWithAllRelations(int $limit = 200): array
    {
        /**
         * @var array<Publisher> $publishers
         */
        $publishers =  $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL')
            ->leftJoin('p.titles', 't')->addSelect('t')
            ->leftJoin('p.uploadedImages', 'uimg')->addSelect('uimg')
            ->leftJoin('p.coverImage', 'cimg')->addSelect('cimg')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $publishers;
    }

    /**
     * @return array<Publisher>
     */
    public function searchPublisher(string $query, int $limit, int $offset): array
    {
        $qb = $this->createQueryBuilder('p')
            ->andWhere('p.deletedAt IS NULL')
            ->addSelect('MATCH(p.name) AGAINST(:q IN BOOLEAN MODE) AS HIDDEN score')
            ->having('score > 0')
            ->setParameter('q', $query)
            ->orderBy('score', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        /** @var array<Publisher> $publishers */
        $publishers = $qb->getQuery()->getResult();

        return $publishers;
    }
}
