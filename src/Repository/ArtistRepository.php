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

    /**
     * @return array<Artist>
     */
    public function searchArtist(string $query, int $limit = 200, int $offset = 0): array
    {
        $qb = $this->createQueryBuilder('a')
            ->addSelect("MATCH (a.firstName, a.lastName, a.pseudo) AGAINST (:q IN BOOLEAN MODE) AS HIDDEN score")
            // ->andWhere("MATCH (a.first_name, a.last_name, a.pseudo) AGAINST (:q IN BOOLEAN MODE) > 0")
            ->having('score > 0')
            ->setParameter("q", $query)
            ->orderBy('score', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        /** @var array<Artist> $artists */
        $artists = $qb->getQuery()->getResult();
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
