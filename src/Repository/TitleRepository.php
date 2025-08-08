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

    public function findWithAllRelations(): array
    {
        $qb = $this->createQueryBuilder('t')
            ->leftJoin('t.publisher', 'p')->addSelect('p')
            ->leftJoin('t.artistsContributions', 'c')->addSelect('c')
            ->leftJoin('t.uploadedImages', 'ui')->addSelect('ui')
            ->leftJoin('t.coverImage', 'ci')->addSelect('ci')
            ->leftJoin('c.artist', 'a')->addSelect('a')
            ->leftJoin('c.skill', 's')->addSelect('s');

        return $qb->getQuery()->getResult();
    }
}
