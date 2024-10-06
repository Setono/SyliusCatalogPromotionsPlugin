<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Doctrine\ORM;

use DateTime;
use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Setono\SyliusCatalogPromotionPlugin\Repository\PromotionRepositoryInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class PromotionRepository extends EntityRepository implements PromotionRepositoryInterface
{
    use HasAnyBeenUpdatedSinceTrait;

    public function findForProcessing(): array
    {
        $dt = new DateTime();

        $res = $this->createQueryBuilder('o')
            ->andWhere('o.enabled = true')
            ->andWhere('SIZE(o.channels) > 0')
            ->andWhere('o.startsAt is null OR o.startsAt <= :date')
            ->andWhere('o.endsAt is null OR o.endsAt >= :date')
            ->addOrderBy('o.exclusive', 'ASC')
            ->addOrderBy('o.priority', 'ASC')
            ->setParameter('date', $dt)
            ->getQuery()
            ->getResult()
        ;

        Assert::isArray($res);
        Assert::allIsInstanceOf($res, PromotionInterface::class);

        return $res;
    }
}
