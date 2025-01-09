<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Repository;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class CatalogPromotionRepository extends EntityRepository implements CatalogPromotionRepositoryInterface
{
    public function findForProcessing(array $catalogPromotions = []): array
    {
        $qb = $this->createQueryBuilder('o');
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

    public function findOneByCode(string $code): ?CatalogPromotionInterface
    {
        $obj = $this->findOneBy(['code' => $code]);
        Assert::nullOrIsInstanceOf($obj, CatalogPromotionInterface::class);

        return $obj;
    }
}
