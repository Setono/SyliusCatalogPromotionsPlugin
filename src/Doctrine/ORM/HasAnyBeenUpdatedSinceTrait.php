<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Doctrine\ORM;

use DateTimeInterface;
use Doctrine\ORM\EntityRepository;

/**
 * @mixin EntityRepository
 */
trait HasAnyBeenUpdatedSinceTrait
{
    public function hasAnyBeenUpdatedSince(DateTimeInterface $dateTime): bool
    {
        $qb = $this->createQueryBuilder('o')
            ->select('count(o)')
            ->setParameter('date', $dateTime)
        ;

        /*
         * These queries has been split into two queries instead of one 'or' query.
         * This is because 'or' queries does not leverage indices as one would expect.
         * Therefore this approach will be much faster on large data sets.
         * Usually you would use UNION in cases like this, but UNION is not supported in Doctrine
         */

        $updated = (int) $qb
                ->andWhere('o.updatedAt is not null', 'o.updatedAt > :date')
                ->getQuery()
                ->getSingleScalarResult() > 0
        ;

        if ($updated) {
            return true;
        }

        $qb->resetDQLPart('where');

        return (int) $qb
                ->andWhere('o.createdAt is not null', 'o.createdAt > :date')
                ->getQuery()
                ->getSingleScalarResult() > 0
            ;
    }
}
