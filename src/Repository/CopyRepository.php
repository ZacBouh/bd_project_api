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
        $qb = $this->createQueryBuilder('c');
        /** @var User */
        $user = $this->security->getUser();
        if (!$this->security->isGranted(Role::ADMIN->value)) {
            $qb->andWhere('c.ownerId = :ownerId')
                ->setParameter('ownerId', $user->getId());
        }
        // Manually join table data on ids, giving the right names
        $qb->leftJoin(User::class, 'owner', 'WITH', 'owner.id = c.ownerId')
            ->addSelect('owner')
            ->leftJoin(Title::class, 't', 'WITH', 't.id = c.titleId')
            ->addSelect('t');

        $qb->leftJoin('c.coverImage', 'coverImage')
            ->addSelect('coverImage')
            ->leftJoin('c.uploadedImages', 'uploadedImage')
            ->addSelect('coverImage');

        return $qb->getQuery()->getResult(Query::HYDRATE_SCALAR);
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
