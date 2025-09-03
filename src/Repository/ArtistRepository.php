<?php

namespace App\Repository;

use App\Entity\Artist;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Artist>
 */
class ArtistRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Artist::class);
    }


    /**
     * @return array<Artist>
     */
    public function findWithAllRelations(int $limit = 200): array
    {
        /** @var array<Artist> $artists */
        $artists = $this->createQueryBuilder('a')
            ->leftJoin('a.skills', 's')->addSelect('s')
            ->leftJoin('a.titlesContributions', 'tc')->addSelect('tc')
            ->leftJoin('tc.skill', 'skill')->addSelect('skill')
            ->leftJoin('tc.title', 'title')->addSelect('title')
            ->leftJoin('a.uploadedImages', 'ui')->addSelect('ui')
            ->leftJoin('a.coverImage', 'ci')->addSelect('ci')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
        return $artists;
    }
    //    /**
    //     * @return Artist[] Returns an array of Artist objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('a.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Artist
    //    {
    //        return $this->createQueryBuilder('a')
    //            ->andWhere('a.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
