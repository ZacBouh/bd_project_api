<?php

namespace App\Repository;

use App\Entity\Copy;
use App\Entity\Title;
use App\Enum\CopyCondition;
use App\Enum\Language;
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

    public function findOneWithRelations(int $id): ?Copy
    {
        $qb = $this->createQueryBuilder('copy')
            ->andWhere('copy.deletedAt IS NULL')
            ->andWhere('copy.id = :id')
            ->setParameter('id', $id)
            ->leftJoin('copy.owner', 'owner')
            ->addSelect('owner')
            ->leftJoin('copy.title', 'title')
            ->addSelect('title')
            ->leftJoin('title.publisher', 'publisher')
            ->addSelect('publisher')
            ->leftJoin('title.artistsContributions', 'contributions')
            ->addSelect('contributions')
            ->leftJoin('contributions.artist', 'artist')
            ->addSelect('artist')
            ->leftJoin('copy.coverImage', 'coverImage')
            ->addSelect('coverImage')
            ->leftJoin('copy.uploadedImages', 'uploadedImage')
            ->addSelect('uploadedImage');

        /** @var Copy|null $copy */
        $copy = $qb->getQuery()->getOneOrNullResult();

        return $copy;
    }

    /**
     * @return array<Copy>
     */
    public function findForSaleWithFilters(
        int $limit,
        int $offset,
        ?CopyCondition $condition,
        ?int $minPrice,
        ?int $maxPrice,
        ?Language $language,
        ?int $publisherId,
        ?string $isbn,
        string $orderDirection,
        string $orderField,
    ): array {
        $qb = $this->createQueryBuilder('copy')
            ->andWhere('copy.deletedAt IS NULL')
            ->andWhere('copy.forSale = :forSale')
            ->setParameter('forSale', true)
            ->leftJoin('copy.title', 'title')
            ->addSelect('title')
            ->leftJoin('title.publisher', 'publisher')
            ->addSelect('publisher')
            ->leftJoin('copy.owner', 'owner')
            ->addSelect('owner')
            ->leftJoin('copy.coverImage', 'coverImage')
            ->addSelect('coverImage')
            ->leftJoin('copy.uploadedImages', 'uploadedImage')
            ->addSelect('uploadedImage')
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        $orderColumn = match ($orderField) {
            'price' => 'copy.price',
            default => 'copy.updatedAt',
        };

        $qb->orderBy($orderColumn, $orderDirection);

        if (!is_null($condition)) {
            $qb
                ->andWhere('copy.copyCondition IN (:conditions)')
                ->setParameter('conditions', $condition->sameOrBetterValues());
        }

        if (!is_null($minPrice)) {
            $qb->andWhere('copy.price >= :minPrice')
                ->setParameter('minPrice', $minPrice);
        }

        if (!is_null($maxPrice)) {
            $qb->andWhere('copy.price <= :maxPrice')
                ->setParameter('maxPrice', $maxPrice);
        }

        if (!is_null($language)) {
            $qb->andWhere('title.language = :language')
                ->setParameter('language', $language);
        }

        if (!is_null($publisherId)) {
            $qb->andWhere('publisher.id = :publisherId')
                ->setParameter('publisherId', $publisherId);
        }

        if (!is_null($isbn)) {
            $qb->andWhere('title.isbn = :isbn')
                ->setParameter('isbn', $isbn);
        }

        /** @var array<Copy> $copies */
        $copies = $qb->getQuery()->getResult();
        return $copies;
    }

    /**
     * @return array<Copy>
     */
    public function findAllWithRelations(): array
    {
        $qb = $this->createQueryBuilder('copy')
            ->andWhere('copy.deletedAt IS NULL');
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

        /** @var array<Copy> $copies  */
        $copies = $qb->getQuery()->getResult();
        return $copies;
    }

    /** 
     * @return Copy[]
     */
    public function searchCopy(string $query, int $limit = 200, int $offset = 0, ?bool $forSale = null): array
    {
        $qb = $this->createQueryBuilder('c')
            ->leftJoin('c.title', 't')
            ->leftjoin('t.publisher', 'tp')
            ->addSelect("MATCH (t.name) AGAINST (:q IN BOOLEAN MODE) AS HIDDEN t_score")
            ->addSelect("MATCH (tp.name) AGAINST (:q IN BOOLEAN MODE) AS HIDDEN tp_score")
            ->having('t_score > 0 OR tp_score > 0')
            ->andWhere('c.deletedAt IS NULL')

            ->orderBy('CASE WHEN t_score > 0 THEN 1 ELSE 0 END', 'DESC')
            ->addOrderBy('t_score', 'DESC')
            ->addOrderBy('tp_score', 'DESC')
            ->addOrderBy('c.updatedAt')

            ->setParameter('q', $query)
            ->setFirstResult($offset)
            ->setMaxResults($limit);

        if (!is_null($forSale)) {
            $qb->andWhere('c.forSale = :fs')
                ->setParameter('fs', $forSale);
        }
        /** @var Copy[] $copies */
        $copies = $qb->getQuery()->getResult();
        return $copies;
    }

    /**
     * @param int[] $copyIds
     */
    public function markAsSold(array $copyIds): int
    {
        if ($copyIds === []) {
            return 0;
        }

        $qb = $this->createQueryBuilder('c');

        return (int) $qb
            ->update()
            ->set('c.forSale', ':sold')
            ->where($qb->expr()->in('c.id', ':ids'))
            ->andWhere($qb->expr()->orX(
                'c.forSale IS NULL',
                'c.forSale = :forSaleTrue'
            ))
            ->setParameter('sold', false)
            ->setParameter('forSaleTrue', true)
            ->setParameter('ids', $copyIds)
            ->getQuery()
            ->execute();
    }

    /**
     * @param int[] $copyIds
     * @return int[]
     */
    public function findNotForSaleIds(array $copyIds): array
    {
        if ($copyIds === []) {
            return [];
        }

        $qb = $this->createQueryBuilder('c');

        $results = $qb
            ->select('c.id')
            ->where($qb->expr()->in('c.id', ':ids'))
            ->andWhere('c.forSale = :forSaleFalse')
            ->setParameter('ids', $copyIds)
            ->setParameter('forSaleFalse', false)
            ->getQuery()
            ->getScalarResult();

        return array_map(
            static fn (array $row): int => (int) array_values($row)[0],
            $results
        );
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
