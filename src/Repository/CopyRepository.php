<?php

namespace App\Repository;

use App\Entity\Copy;
use App\Entity\Title;
use App\Security\Role;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Bundle\SecurityBundle\Security;
use App\Entity\User;
use Doctrine\ORM\Query;

/**
 * @extends ServiceEntityRepository<Copy>
 */
class CopyRepository extends ServiceEntityRepository
{
    public function __construct(
        ManagerRegistry $registry,
        private Security $security,
    ) {
        parent::__construct($registry, Copy::class);
    }

    public function findAllWithRelations()
    {
        $qb = $this->createQueryBuilder('copy');
        /** @var User */
        $user = $this->security->getUser();
        if (!$this->security->isGranted(Role::ADMIN->value)) {
            $qb->andWhere('copy.owner = :owner')
                ->setParameter('owner', $user);
        }
        // Manually join table data on ids, giving the right names
        $qb
            ->leftJoin('copy.owner', 'owner')
            ->addSelect('owner')
            ->leftJoin('copy.title', 'title')
            ->addSelect('title')
            ->leftJoin('title.publisher', 'publisher')
            ->addSelect('publisher')
            ->leftJoin('title.artistsContributions', 'contributions')
            ->addSelect('contributions')
            ->leftJoin('contributions.artist', 'artist')
            ->addSelect('artist');

        $qb->leftJoin('copy.coverImage', 'coverImage')
            ->addSelect('coverImage')
            ->leftJoin('copy.uploadedImages', 'uploadedImage')
            ->addSelect('uploadedImage');

        // return $qb->getQuery()->getResult(Query::HYDRATE_ARRAY);
        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Copy[] Returns an array of Copy objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('c.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Copy
    //    {
    //        return $this->createQueryBuilder('c')
    //            ->andWhere('c.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
