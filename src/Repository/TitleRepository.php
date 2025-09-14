<?php

namespace App\Repository;

use App\Entity\Title;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Title>
 */
class TitleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Title::class);
    }

    /**
     * @return array<Title>
     */
    public function findWithAllRelations(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.publisher', 'p')->addSelect('p')
            ->leftJoin('t.artistsContributions', 'c')->addSelect('c')
            ->leftJoin('t.uploadedImages', 'ui')->addSelect('ui')
            ->leftJoin('t.coverImage', 'ci')->addSelect('ci')
            ->leftJoin('c.artist', 'a')->addSelect('a')
            ->leftJoin('c.skill', 's')->addSelect('s');


        /**
         * @var array<Title> $titles
         */
        $titles = $qb->getQuery()->getResult();
        return $titles;
    }

    /**
     * @return array<Title>
     */
    public function searchTitle(string $query, int $limit = 200, int $offset = 0): array
    {

        $qb = $this->createQueryBuilder('t')
            ->addSelect("MATCH (t.name) AGAINST (:q IN BOOLEAN MODE) AS HIDDEN score")
            ->andWhere("MATCH (t.name) AGAINST (:q IN BOOLEAN MODE) > 0")
            ->setParameter("q", $query)
            ->orderBy('score', 'DESC')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        /** @var array<Title> */
        $titles = $qb->getQuery()->getResult();
        return $titles;
    }
}
