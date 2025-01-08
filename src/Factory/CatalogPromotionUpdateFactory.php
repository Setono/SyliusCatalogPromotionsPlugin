<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Factory;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Webmozart\Assert\Assert;

final class CatalogPromotionUpdateFactory implements CatalogPromotionUpdateFactoryInterface
{
    public function __construct(private readonly FactoryInterface $decorated)
    {
    }

    public function createNew(): CatalogPromotionUpdateInterface
    {
        $obj = $this->decorated->createNew();
        Assert::isInstanceOf($obj, CatalogPromotionUpdateInterface::class);

        return $obj;
    }

    public function createWithCatalogPromotionsAndProducts(array $catalogPromotions, array $products): CatalogPromotionUpdateInterface
    {
        $obj = $this->createNew();
        $obj->setCatalogPromotions($catalogPromotions);
        $obj->setProducts($products);

        return $obj;
    }
}
