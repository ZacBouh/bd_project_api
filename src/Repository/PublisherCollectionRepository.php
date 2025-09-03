<?php

namespace App\Repository;

use App\Entity\PublisherCollection;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<PublisherCollection>
 */
class PublisherCollectionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, PublisherCollection::class);
    }

    /**
     * @return PublisherCollection[]
     */
    public function findAllWithPublisherAndImages(): array
    {
        /** @var array<PublisherCollection> $collections */
        $collections =  $this->createQueryBuilder('pc')
            ->leftJoin('pc.publisher', 'p')->addSelect('p')
            ->leftJoin('pc.coverImage', 'ci')->addSelect('ci')
            ->leftJoin('pc.uploadedImages', 'ui')->addSelect('ui')
            ->distinct()
            ->getQuery()
            ->getResult();
        return $collections;
    }
    //    /**
    //     * @return PublisherCollection[] Returns an array of PublisherCollection objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?PublisherCollection
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
