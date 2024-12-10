<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Repository;

use Setono\SyliusCatalogPromotionPlugin\Model\PromotionInterface;
use Sylius\Bundle\ResourceBundle\Doctrine\ORM\EntityRepository;
use Webmozart\Assert\Assert;

class PromotionRepository extends EntityRepository implements PromotionRepositoryInterface
{
    public function findForProcessing(): array
    {
        $objs = $this->findAll();
        Assert::allIsInstanceOf($objs, PromotionInterface::class);

        return $objs;
    }

    public function findOneByCode(string $code): ?PromotionInterface
    {
        $obj = $this->findOneBy(['code' => $code]);
        Assert::nullOrIsInstanceOf($obj, PromotionInterface::class);

        return $obj;
    }
}
