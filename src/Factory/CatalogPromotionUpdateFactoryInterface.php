<?php

declare(strict_types=1);

namespace Setono\SyliusCatalogPromotionPlugin\Factory;

use Setono\SyliusCatalogPromotionPlugin\Model\CatalogPromotionUpdateInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;

interface CatalogPromotionUpdateFactoryInterface extends FactoryInterface
{
    public function createNew(): CatalogPromotionUpdateInterface;

    /**
     * @param list<string> $catalogPromotions
     * @param list<int> $products
     */
    public function createWithCatalogPromotionsAndProducts(array $catalogPromotions, array $products): CatalogPromotionUpdateInterface;
}
