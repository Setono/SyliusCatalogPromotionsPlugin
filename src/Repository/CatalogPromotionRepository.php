<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Repository;

use Doctrine\ORM\QueryBuilder;
use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class CatalogPromotionRepository extends EntityRepository implements CatalogPromotionRepositoryInterface
{
    public function findForProcessing(array $catalogPromotions = []): array
    {
        $qb = $this->createProcessingQueryBuilder();

        if ([] !== $catalogPromotions) {
            $qb->andWhere('o.code IN (:catalogPromotions)')
                ->setParameter('catalogPromotions', $catalogPromotions)
            ;
        }

        $objs = $qb->getQuery()->getResult();
        Assert::isArray($objs);
        Assert::allIsInstanceOf($objs, CatalogPromotionInterface::class);
        Assert::isList($objs);

        return $objs;
    }

    public function findOneForProcessing(string $code): ?CatalogPromotionInterface
    {
        $obj = $this->createProcessingQueryBuilder()
            ->andWhere('o.code = :code')
            ->setParameter('code', $code)
            ->getQuery()
            ->getOneOrNullResult()
        ;
        Assert::nullOrIsInstanceOf($obj, CatalogPromotionInterface::class);

        return $obj;
    }

    public function findOneByCode(string $code): ?CatalogPromotionInterface
    {
        $obj = $this->findOneBy(['code' => $code]);
        Assert::nullOrIsInstanceOf($obj, CatalogPromotionInterface::class);

        return $obj;
    }

    private function createProcessingQueryBuilder(): QueryBuilder
    {
        return $this->createQueryBuilder('o')
            ->select('o, r')
            ->leftJoin('o.rules', 'r') // important to use left join because we might have 0 rules on a catalog promotion
        ;
    }
}
