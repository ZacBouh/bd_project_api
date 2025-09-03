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
            ->leftJoin('p.titles', 't')->addSelect('t')
            ->leftJoin('p.uploadedImages', 'uimg')->addSelect('uimg')
            ->leftJoin('p.coverImage', 'cimg')->addSelect('cimg')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();

        return $publishers;
    }
}
